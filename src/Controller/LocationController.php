<?php

namespace App\Controller;

use App\Entity\Face;
use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\ClientRepository;
use App\Repository\FaceRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location')]
class LocationController extends AbstractController
{
    #[Route('/', name: 'app_location_index', methods: ['GET'])]
    public function index(Request $request, LocationRepository $locationRepository, ClientRepository $clientRepository): Response
    {
        $statut = $request->query->get('statut');
        $estPaye = $request->query->get('estPaye');
        $clientId = $request->query->get('client');
        $recherche = $request->query->get('recherche');

        $clientIdInt = $clientId ? (int) $clientId : null;
        // Inclure les annulées par défaut dans la liste
        $locations = $locationRepository->findWithFilters($statut, $estPaye, $clientIdInt, $recherche, true);
        $clients = $clientRepository->findAll();

        return $this->render('location/index.html.twig', [
            'locations' => $locations,
            'clients' => $clients,
            'filters' => [
                'statut' => $statut,
                'estPaye' => $estPaye,
                'client' => $clientId,
                'recherche' => $recherche,
            ],
        ]);
    }

    #[Route('/new', name: 'app_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FaceRepository $faceRepository, ClientRepository $clientRepository): Response
    {
        $location = new Location();

        // Si une face_id est passée en paramètre, pré-sélectionner la face
        $faceId = $request->query->get('face_id');
        if ($faceId) {
            $face = $faceRepository->find($faceId);
            if ($face) {
                // Empêcher de créer une location sur un panneau archivé
                if (!$face->getPanneau()->isActif()) {
                    $this->addFlash('error', 'Ce panneau est archivé. Vous ne pouvez plus créer de nouvelle location sur ce panneau.');
                    return $this->redirectToRoute('app_panneau_show', ['id' => $face->getPanneau()->getId()], Response::HTTP_SEE_OTHER);
                }

                $location->setFace($face);
                // Pré-remplir le montant mensuel avec le prix du panneau
                $location->setMontantMensuel($face->getPanneau()->getPrixMensuel());
            }
        }

        // Si un client_id est passée en paramètre, pré-sélectionner le client
        $clientId = $request->query->get('client_id');
        $clientPreselectionne = false;
        if ($clientId) {
            $client = $clientRepository->find($clientId);
            if ($client) {
                $location->setClient($client);
                $clientPreselectionne = true;
            }
        }

        $facePreselectionnee = (bool) $faceId;
        $facePreselectionneeObj = $faceId ? $faceRepository->find($faceId) : null;
        $clientPreselectionneObj = $clientId ? $clientRepository->find($clientId) : null;
        
        $form = $this->createForm(LocationType::class, $location, [
            'face_preselectionnee' => $facePreselectionnee,
            'client_preselectionne' => $clientPreselectionne,
        ]);
        $form->handleRequest($request);

        // Restaurer les valeurs présélectionnées si les champs sont désactivés
        if ($form->isSubmitted()) {
            if ($facePreselectionnee && $facePreselectionneeObj && !$location->getFace()) {
                $location->setFace($facePreselectionneeObj);
            }
            if ($clientPreselectionne && $clientPreselectionneObj && !$location->getClient()) {
                $location->setClient($clientPreselectionneObj);
            }
            // Restaurer la date de fin si elle est désactivée
            if ($location->getDateFin() === null && $form->get('dateFin')->getData() === null) {
                // Calculer la date de fin si la durée est fournie
                if ($form->has('dureeMois')) {
                    $dureeMois = $form->get('dureeMois')->getData();
                    if ($dureeMois && $location->getDateDebut()) {
                        $dateDebut = $location->getDateDebut();
                        $dateFin = new \DateTime($dateDebut->format('Y-m-d'));
                        $dateFin->modify('+' . $dureeMois . ' months');
                        $location->setDateFin($dateFin);
                    }
                }
            }
        }

        // Calculer la date de fin si la durée est fournie (pour l'affichage initial)
        if (!$form->isSubmitted() && $form->has('dureeMois')) {
            $dureeMois = $form->get('dureeMois')->getData();
            if ($dureeMois && $location->getDateDebut()) {
                $dateDebut = $location->getDateDebut();
                $dateFin = new \DateTime($dateDebut->format('Y-m-d'));
                $dateFin->modify('+' . $dureeMois . ' months');
                $location->setDateFin($dateFin);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la face est disponible pour la période
            $face = $location->getFace();
            if (!$face) {
                $this->addFlash('error', 'Veuillez sélectionner une face.');
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                    'face_id' => $faceId,
                    'client_id' => $clientId,
                    'face_obj' => $location->getFace(),
                    'client_obj' => $location->getClient(),
                ]);
            }

            if (!$face->isDisponible($location->getDateDebut(), $location->getDateFin())) {
                // Vérifier s'il y a une location active ou future qui cause le conflit
                $locationsConflit = $face->getLocationsActivesOuFutures();
                $message = 'Cette face est déjà louée sur cette période.';
                
                if (!empty($locationsConflit)) {
                    // Trouver la location qui chevauche avec la période demandée
                    $dateDebutNormalisee = new \DateTime($location->getDateDebut()->format('Y-m-d'));
                    $dateFinNormalisee = new \DateTime($location->getDateFin()->format('Y-m-d'));
                    
                    foreach ($locationsConflit as $locConflit) {
                        $locDateDebut = new \DateTime($locConflit->getDateDebut()->format('Y-m-d'));
                        $locDateFin = new \DateTime($locConflit->getDateFin()->format('Y-m-d'));
                        
                        // Vérifier le chevauchement
                        if ($dateDebutNormalisee <= $locDateFin && $dateFinNormalisee >= $locDateDebut) {
                            $dateSuivante = (clone $locDateFin)->modify('+1 day');
                            $message .= ' La face est occupée du ' . $locConflit->getDateDebut()->format('d/m/Y') . 
                                       ' au ' . $locConflit->getDateFin()->format('d/m/Y') . 
                                       '. Vous pouvez réserver à partir du ' . $dateSuivante->format('d/m/Y') . '.';
                            break;
                        }
                    }
                }
                
                $this->addFlash('error', $message);
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                    'face_id' => $faceId,
                    'client_id' => $clientId,
                    'face_obj' => $location->getFace(),
                    'client_obj' => $location->getClient(),
                ]);
            }

            // Vérifier que la date de début est avant la date de fin
            if ($location->getDateDebut() >= $location->getDateFin()) {
                $this->addFlash('error', 'La date de début doit être antérieure à la date de fin.');
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                    'face_id' => $faceId,
                    'client_id' => $clientId,
                    'face_obj' => $location->getFace(),
                    'client_obj' => $location->getClient(),
                ]);
            }

            // Vérifier que si le prix diffère du prix du panneau, les notes sont fournies
            $prixPanneau = $face->getPanneau()->getPrixMensuel();
            $prixLocation = $location->getMontantMensuel();
            $difference = abs(floatval($prixLocation) - floatval($prixPanneau));

            if ($difference > 0.01 && empty($location->getNotes())) {
                $this->addFlash('error', 'Veuillez indiquer une justification car le prix a été modifié.');
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                    'face_id' => $faceId,
                    'client_id' => $clientId,
                    'face_obj' => $location->getFace(),
                    'client_obj' => $location->getClient(),
                ]);
            }

            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Location créée avec succès.');

            // Rediriger vers la page d'origine si on vient d'un panneau ou d'un client
            if ($faceId) {
                $face = $location->getFace();
                return $this->redirectToRoute('app_panneau_show', ['id' => $face->getPanneau()->getId()], Response::HTTP_SEE_OTHER);
            } elseif ($clientId) {
                return $this->redirectToRoute('app_client_show', ['id' => $clientId], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        // Récupérer les objets pour l'affichage dans le template
        $face = $location->getFace();
        $client = $location->getClient();

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form,
            'face_id' => $faceId,
            'client_id' => $clientId,
            'face_obj' => $face,
            'client_obj' => $client,
        ]);
    }

    #[Route('/{id}', name: 'app_location_show', methods: ['GET'])]
    public function show(Location $location, LocationRepository $locationRepository): Response
    {
        // Charger les paiements avec la location
        $location = $locationRepository->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->where('l.id = :id')
            ->setParameter('id', $location->getId())
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$location) {
            throw $this->createNotFoundException('Location non trouvée');
        }
        
        return $this->render('location/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        // Sauvegarder les valeurs originales avant modification
        $originalFace = $location->getFace();
        $originalClient = $location->getClient();
        $originalDateFin = $location->getDateFin();

        // En édition, on garde la face présélectionnée (non modifiable), mais le client doit être modifiable
        $form = $this->createForm(LocationType::class, $location, [
            'face_preselectionnee' => true,
            'client_preselectionne' => false,
        ]);
        // Pré-remplir la durée en mois si les dates existent
        if ($location->getDateDebut() && $location->getDateFin()) {
            $dateDebut = $location->getDateDebut();
            $dateFin = $location->getDateFin();
            $interval = $dateDebut->diff($dateFin);
            $dureeMois = ($interval->y * 12) + $interval->m;
            if ($dureeMois > 0) {
                $form->get('dureeMois')->setData($dureeMois);
            }
        }

        $form->handleRequest($request);

        // Restaurer les valeurs présélectionnées si les champs sont désactivés
        if ($form->isSubmitted()) {
            // Restaurer la face et le client (toujours présélectionnés en édition)
            if (!$location->getFace() && $originalFace) {
                $location->setFace($originalFace);
            }
            // Restaurer la date de fin si elle est désactivée
            if ($location->getDateFin() === null) {
                // Calculer la date de fin si la durée est fournie
                if ($form->has('dureeMois')) {
                    $dureeMois = $form->get('dureeMois')->getData();
                    if ($dureeMois && $location->getDateDebut()) {
                        $dateDebut = $location->getDateDebut();
                        $dateFin = new \DateTime($dateDebut->format('Y-m-d'));
                        $dateFin->modify('+' . $dureeMois . ' months');
                        $location->setDateFin($dateFin);
                    }
                } elseif ($originalDateFin) {
                    // Restaurer la date de fin originale si pas de durée fournie
                    $location->setDateFin($originalDateFin);
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la face est disponible pour la période (en excluant la location actuelle)
            $face = $location->getFace();
            
            if (!$face->isDisponible($location->getDateDebut(), $location->getDateFin(), $location)) {
                $this->addFlash('error', 'Cette face est déjà louée sur cette période. Veuillez choisir une autre période.');
                return $this->render('location/edit.html.twig', [
                    'location' => $location,
                    'form' => $form,
                ]);
            }

            // Vérifier que la date de début est avant la date de fin
            $dateDebut = $location->getDateDebut();
            $dateFin = $location->getDateFin();
            if ($dateDebut >= $dateFin) {
                $this->addFlash('error', 'La date de début doit être antérieure à la date de fin.');
                return $this->render('location/edit.html.twig', [
                    'location' => $location,
                    'form' => $form,
                ]);
            }

            // Vérifier que si le prix diffère du prix du panneau, les notes sont fournies
            $prixPanneau = $face->getPanneau()->getPrixMensuel();
            $prixLocation = $location->getMontantMensuel();
            $difference = abs(floatval($prixLocation) - floatval($prixPanneau));

            if ($difference > 0.01 && empty($location->getNotes())) {
                $this->addFlash('error', 'Veuillez indiquer une justification car le prix a été modifié.');
                return $this->render('location/edit.html.twig', [
                    'location' => $location,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Location modifiée avec succès.');
            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_location_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
            $this->addFlash('success', 'Location supprimée avec succès.');
        }

        return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/annuler', name: 'app_location_annuler', methods: ['GET', 'POST'])]
    public function annuler(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($location->isAnnulee()) {
            $this->addFlash('warning', 'Cette location est déjà annulée.');
            return $this->redirectToRoute('app_location_show', ['id' => $location->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($request->isMethod('POST')) {
            $raison = $request->request->get('raison');
            
            if ($this->isCsrfTokenValid('annuler'.$location->getId(), $request->request->get('_token'))) {
                $location->annuler($raison);
                $entityManager->flush();

                $this->addFlash('success', 'Location annulée avec succès. Tous les paiements associés ont également été annulés.');
                return $this->redirectToRoute('app_location_show', ['id' => $location->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('location/annuler.html.twig', [
            'location' => $location,
        ]);
    }


    #[Route('/get-prix-face/{id}', name: 'app_location_get_prix_face', methods: ['GET'])]
    public function getPrixFace(Face $face): Response
    {
        return $this->json([
            'prix' => $face->getPanneau()->getPrixMensuel()
        ]);
    }
}
