<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\LocationRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paiement')]
class PaiementController extends AbstractController
{
    #[Route('/', name: 'app_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository): Response
    {
        return $this->render('paiement/index.html.twig', [
            'paiements' => $paiementRepository->findBy([], ['datePaiement' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LocationRepository $locationRepository): Response
    {
        $paiement = new Paiement();
        
        // Si une location_id est passée en paramètre, pré-sélectionner la location
        $locationId = $request->query->get('location_id');
        if ($locationId) {
            $location = $locationRepository->find($locationId);
            if ($location) {
                $paiement->setLocation($location);
            }
        }
        
        $form = $this->createForm(PaiementType::class, $paiement, [
            'location' => $locationId ? $locationRepository->find($locationId) : null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paiement);
            $entityManager->flush();

            $this->addFlash('success', 'Paiement enregistré avec succès.');
            
            // Rediriger vers la page de la location si on vient de là
            if ($locationId) {
                return $this->redirectToRoute('app_location_show', ['id' => $locationId], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
            'location_id' => $locationId,
            'location_obj' => $locationId ? $locationRepository->find($locationId) : null,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Paiement modifié avec succès.');
            
            if ($paiement->getLocation()) {
                return $this->redirectToRoute('app_location_show', ['id' => $paiement->getLocation()->getId()], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_delete', methods: ['POST'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $locationId = $paiement->getLocation()?->getId();
        
        if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($paiement);
            $entityManager->flush();
            $this->addFlash('success', 'Paiement supprimé avec succès.');
        }

        if ($locationId) {
            return $this->redirectToRoute('app_location_show', ['id' => $locationId], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
    }
}
