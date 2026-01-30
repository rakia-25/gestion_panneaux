<?php

namespace App\Controller;

use App\Entity\Face;
use App\Entity\Panneau;
use App\Form\PanneauType;
use App\Repository\FaceRepository;
use App\Repository\PanneauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panneau')]
class PanneauController extends AbstractController
{
    #[Route('/', name: 'app_panneau_index', methods: ['GET'])]
    public function index(Request $request, PanneauRepository $panneauRepository): Response
    {
        $type = $request->query->get('type');
        $etat = $request->query->get('etat');
        $eclairage = $request->query->get('eclairage');
        $recherche = $request->query->get('recherche');
        $statutActif = $request->query->get('statut_actif'); // '1' = actifs, '0' = archivés, null = tous

        $panneaux = $panneauRepository->findWithFilters($type, $etat, $eclairage, $recherche, $statutActif);

        return $this->render('panneau/index.html.twig', [
            'panneaux' => $panneaux,
            'filters' => [
                'type' => $type,
                'etat' => $etat,
                'eclairage' => $eclairage,
                'recherche' => $recherche,
                'statut_actif' => $statutActif,
            ],
        ]);
    }

    #[Route('/new', name: 'app_panneau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PanneauPhotoUploaderInterface $photoUploader, PanneauRepository $panneauRepository): Response
    {
        $panneau = new Panneau();

        // Pré-remplir la référence (sera générée automatiquement)
        $lastNumber = $panneauRepository->findLastReferenceNumber();
        $newNumber = $lastNumber + 1;
        $panneau->setReference(sprintf('PAN-%03d', $newNumber));

        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer automatiquement la référence si elle n'est pas définie
            if (empty($panneau->getReference())) {
                $lastNumber = $panneauRepository->findLastReferenceNumber();
                $newNumber = $lastNumber + 1;
                $panneau->setReference(sprintf('PAN-%03d', $newNumber));
            }

            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/panneaux';
                try {
                    $newFilename = $photoUploader->upload($photoFile, $uploadDir);
                    $panneau->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo: ' . $e->getMessage());
                }
            }

            $etatFaceA = $form->has('etatFaceA') ? ($form->get('etatFaceA')->getData() ?? 'bon') : 'bon';
            $etatFaceB = $form->has('etatFaceB') ? ($form->get('etatFaceB')->getData() ?? 'bon') : 'bon';
            $this->persistFacesForPanneau($panneau, $entityManager, $etatFaceA, $etatFaceB);
            $entityManager->persist($panneau);
            $entityManager->flush();

            $this->addFlash('success', 'Panneau créé avec succès.');
            return $this->redirectToRoute('app_panneau_show', ['id' => $panneau->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panneau/new.html.twig', [
            'panneau' => $panneau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panneau_show', methods: ['GET'])]
    public function show(Panneau $panneau): Response
    {
        return $this->render('panneau/show.html.twig', [
            'panneau' => $panneau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_panneau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Panneau $panneau, EntityManagerInterface $entityManager, PanneauPhotoUploaderInterface $photoUploader): Response
    {
        $oldPhoto = $panneau->getPhoto();
        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/panneaux';
                try {
                    $newFilename = $photoUploader->upload($photoFile, $uploadDir, $oldPhoto);
                    $panneau->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo: ' . $e->getMessage());
                }
            }

            // Adapter les faces au type (simple ↔ double)
            $faces = $panneau->getFaces()->toArray();
            usort($faces, fn ($a, $b) => strcmp($a->getLettre(), $b->getLettre()));
            $faceA = $faces[0] ?? null;
            $faceB = $faces[1] ?? null;

            if ($panneau->getType() === 'simple') {
                if ($faceB !== null) {
                    if ($faceB->getLocations()->count() > 0) {
                        $this->addFlash('error', 'Impossible de passer en panneau simple : la face B a des locations. Supprimez ou transférez les locations avant de modifier le type.');
                        return $this->render('panneau/edit.html.twig', [
                            'panneau' => $panneau,
                            'form' => $form,
                        ]);
                    }
                    $entityManager->remove($faceB);
                }
            } else {
                // type === 'double'
                if ($faceB === null) {
                    $etatFaceB = $form->has('etatFaceB') ? ($form->get('etatFaceB')->getData() ?? 'bon') : 'bon';
                    $newFaceB = new Face();
                    $newFaceB->setLettre('B');
                    $newFaceB->setPanneau($panneau);
                    $newFaceB->setEtat($etatFaceB);
                    $entityManager->persist($newFaceB);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Panneau modifié avec succès.');
            return $this->redirectToRoute('app_panneau_show', ['id' => $panneau->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panneau/edit.html.twig', [
            'panneau' => $panneau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_panneau_delete', methods: ['POST'])]
    public function delete(Request $request, Panneau $panneau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$panneau->getId(), $request->request->get('_token'))) {
            // Ne plus supprimer définitivement : on désactive le panneau (soft delete)
            $panneau->setActif(false);
            $entityManager->flush();
            $this->addFlash('success', 'Panneau archivé avec succès. Il n\'est plus disponible à la location, mais reste présent dans l\'historique.');
        }

        return $this->redirectToRoute('app_panneau_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/restore', name: 'app_panneau_restore', methods: ['POST'])]
    public function restore(Request $request, Panneau $panneau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$panneau->getId(), $request->request->get('_token'))) {
            $panneau->setActif(true);
            $entityManager->flush();
            $this->addFlash('success', 'Panneau réactivé avec succès. Il est à nouveau disponible à la location.');
        }

        return $this->redirectToRoute('app_panneau_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/face/{id}/locations', name: 'app_face_locations', methods: ['GET'])]
    public function faceLocations(Face $face, FaceRepository $faceRepository): Response
    {
        $face = $faceRepository->findOneWithLocationsAndPaiements($face->getId());
        if (!$face) {
            throw $this->createNotFoundException('Face non trouvée');
        }

        $locations = $face->getLocations()->toArray();
        usort($locations, fn ($a, $b) => $b->getDateDebut() <=> $a->getDateDebut());

        return $this->render('face/locations.html.twig', [
            'face' => $face,
            'locations' => $locations,
        ]);
    }

    /**
     * Crée et persiste les faces du panneau selon son type (simple = face A, double = faces A et B).
     */
    private function persistFacesForPanneau(Panneau $panneau, EntityManagerInterface $entityManager, string $etatFaceA = 'bon', string $etatFaceB = 'bon'): void
    {
        $faceA = new Face();
        $faceA->setLettre('A');
        $faceA->setPanneau($panneau);
        $faceA->setEtat($etatFaceA);
        $entityManager->persist($faceA);

        if ($panneau->getType() === 'double') {
            $faceB = new Face();
            $faceB->setLettre('B');
            $faceB->setPanneau($panneau);
            $faceB->setEtat($etatFaceB);
            $entityManager->persist($faceB);
        }
    }
}
