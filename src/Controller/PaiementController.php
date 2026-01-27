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
        $location = null;
        if ($locationId) {
            $location = $locationRepository->find($locationId);
            if ($location) {
                // Vérifier si la location est déjà complètement payée
                if ($location->isCompletementPaye()) {
                    $this->addFlash('error', 'Cette location est déjà entièrement payée. Vous ne pouvez pas créer un nouveau paiement.');
                    return $this->redirectToRoute('app_location_show', ['id' => $locationId], Response::HTTP_SEE_OTHER);
                }
                $paiement->setLocation($location);
            }
        }
        
        $form = $this->createForm(PaiementType::class, $paiement, [
            'location' => $locationId ? $locationRepository->find($locationId) : null,
        ]);
        // Sauvegarder la location originale avant handleRequest
        $originalLocation = $paiement->getLocation();
        
        $form->handleRequest($request);
        
        // Restaurer la location si elle est présélectionnée et désactivée
        if ($form->isSubmitted() && $locationId && $location) {
            if (!$paiement->getLocation()) {
                $paiement->setLocation($location);
            }
        } elseif ($form->isSubmitted() && $originalLocation && !$paiement->getLocation()) {
            // En édition, restaurer la location originale
            $paiement->setLocation($originalLocation);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que la location n'est pas complètement payée
            $locationPaiement = $paiement->getLocation();
            if ($locationPaiement && $locationPaiement->isCompletementPaye()) {
                $this->addFlash('error', 'Cette location est déjà entièrement payée. Vous ne pouvez pas créer un nouveau paiement.');
                return $this->render('paiement/new.html.twig', [
                    'paiement' => $paiement,
                    'form' => $form,
                    'location_id' => $locationId,
                    'location_obj' => $locationId ? $locationRepository->find($locationId) : null,
                ]);
            }
            
            // Vérifier que le montant ne dépasse pas le montant restant
            if ($locationPaiement) {
                $montantRestant = floatval($locationPaiement->getMontantRestant());
                $montantPaiement = floatval($paiement->getMontant());
                
                if ($montantPaiement > $montantRestant) {
                    $this->addFlash('error', sprintf(
                        'Le montant du paiement (%.0f FCFA) ne peut pas dépasser le montant restant à payer (%.0f FCFA).',
                        $montantPaiement,
                        $montantRestant
                    ));
                    return $this->render('paiement/new.html.twig', [
                        'paiement' => $paiement,
                        'form' => $form,
                        'location_id' => $locationId,
                        'location_obj' => $locationId ? $locationRepository->find($locationId) : null,
                    ]);
                }
            }
            
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
        // Sauvegarder l'ancien montant avant modification
        $ancienMontant = floatval($paiement->getMontant());
        
        // En édition, la location est toujours présélectionnée (désactivée)
        $form = $this->createForm(PaiementType::class, $paiement, [
            'location' => $paiement->getLocation(),
        ]);
        $form->handleRequest($request);
        
        // Restaurer la location si elle est désactivée
        if ($form->isSubmitted() && !$paiement->getLocation() && $paiement->getLocation()) {
            $paiement->setLocation($paiement->getLocation());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le montant ne dépasse pas le montant restant
            $locationPaiement = $paiement->getLocation();
            if ($locationPaiement) {
                // En édition, exclure le montant du paiement actuel du calcul
                $montantTotalPaye = floatval($locationPaiement->getMontantTotalPaye());
                $montantTotalPayeSansActuel = $montantTotalPaye - $ancienMontant;
                $montantTotal = floatval($locationPaiement->getMontantTotal());
                $montantRestant = $montantTotal - $montantTotalPayeSansActuel;
                $nouveauMontant = floatval($paiement->getMontant());
                
                if ($nouveauMontant > $montantRestant) {
                    $this->addFlash('error', sprintf(
                        'Le montant du paiement (%.0f FCFA) ne peut pas dépasser le montant restant à payer (%.0f FCFA).',
                        $nouveauMontant,
                        $montantRestant
                    ));
                    return $this->render('paiement/edit.html.twig', [
                        'paiement' => $paiement,
                        'form' => $form,
                    ]);
                }
            }
            
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
