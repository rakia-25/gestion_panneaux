<?php

namespace App\Controller;

use App\Entity\Face;
use App\Entity\Location;
use App\Form\LocationType;
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
    public function new(Request $request, EntityManagerInterface $entityManager, FaceRepository $faceRepository): Response
    {
        $location = new Location();
        
        // Si une face_id est passée en paramètre, pré-sélectionner la face
        $faceId = $request->query->get('face_id');
        if ($faceId) {
            $face = $faceRepository->find($faceId);
            if ($face) {
                $location->setFace($face);
            }
        }
        
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la face est disponible pour la période
            $face = $location->getFace();
            if (!$face->isDisponible($location->getDateDebut(), $location->getDateFin())) {
                $this->addFlash('error', 'Cette face est déjà louée sur cette période. Veuillez choisir une autre période ou une autre face.');
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                ]);
            }

            // Vérifier que la date de début est avant la date de fin
            if ($location->getDateDebut() >= $location->getDateFin()) {
                $this->addFlash('error', 'La date de début doit être antérieure à la date de fin.');
                return $this->render('location/new.html.twig', [
                    'location' => $location,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Location créée avec succès.');
            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form,
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
        $form->handleRequest($request);

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
}
