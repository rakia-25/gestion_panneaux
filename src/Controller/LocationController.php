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
    public function index(LocationRepository $locationRepository): Response
    {
        return $this->render('location/index.html.twig', [
            'locations' => $locationRepository->findBy([], ['dateDebut' => 'DESC']),
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
                $location->setFace($face);
                // Pré-remplir le montant mensuel avec le prix du panneau
                $location->setMontantMensuel($face->getPanneau()->getPrixMensuel());
            }
        }
        
        // Si un client_id est passée en paramètre, pré-sélectionner le client
        $clientId = $request->query->get('client_id');
        if ($clientId) {
            $client = $clientRepository->find($clientId);
            if ($client) {
                $location->setClient($client);
            }
        }
        
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        // Calculer la date de fin si la durée est fournie
        if ($form->isSubmitted() && $form->has('dureeMois')) {
            $dureeMois = $form->get('dureeMois')->getData();
            if ($dureeMois && $location->getDateDebut()) {
                $dateFin = clone $location->getDateDebut();
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
                $this->addFlash('error', 'Cette face est déjà louée sur cette période. Veuillez choisir une autre période ou une autre face.');
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
    public function show(Location $location): Response
    {
        return $this->render('location/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        
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

        // Calculer la date de fin si la durée est fournie
        if ($form->isSubmitted() && $form->has('dureeMois')) {
            $dureeMois = $form->get('dureeMois')->getData();
            if ($dureeMois && $location->getDateDebut()) {
                $dateFin = clone $location->getDateDebut();
                $dateFin->modify('+' . $dureeMois . ' months');
                $location->setDateFin($dateFin);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la face est disponible pour la période (en excluant la location actuelle)
            $face = $location->getFace();
            $dateDebut = $location->getDateDebut();
            $dateFin = $location->getDateFin();
            
            // Vérifier les conflits avec les autres locations de la même face
            foreach ($face->getLocations() as $otherLocation) {
                if ($otherLocation->getId() !== $location->getId()) {
                    // Vérifier si la location est active ou future
                    $now = new \DateTime();
                    if ($otherLocation->getDateFin() >= $now) {
                        // Vérifier s'il y a un chevauchement
                        if (!($dateFin < $otherLocation->getDateDebut() || $dateDebut > $otherLocation->getDateFin())) {
                            $this->addFlash('error', 'Cette face est déjà louée sur cette période. Veuillez choisir une autre période.');
                            return $this->render('location/edit.html.twig', [
                                'location' => $location,
                                'form' => $form,
                            ]);
                        }
                    }
                }
            }

            // Vérifier que la date de début est avant la date de fin
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

    #[Route('/{id}/toggle-payment', name: 'app_location_toggle_payment', methods: ['POST'])]
    public function togglePayment(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_payment'.$location->getId(), $request->request->get('_token'))) {
            $location->setEstPaye(!$location->isEstPaye());
            $entityManager->flush();
            
            $status = $location->isEstPaye() ? 'marquée comme payée' : 'marquée comme impayée';
            $this->addFlash('success', "Location {$status}.");
        }

        return $this->redirectToRoute('app_location_show', ['id' => $location->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/get-prix-face/{id}', name: 'app_location_get_prix_face', methods: ['GET'])]
    public function getPrixFace(Face $face): Response
    {
        return $this->json([
            'prix' => $face->getPanneau()->getPrixMensuel()
        ]);
    }
}
