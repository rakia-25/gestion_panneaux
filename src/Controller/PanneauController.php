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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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

        $panneaux = $panneauRepository->findWithFilters($type, $etat, $eclairage, $recherche);

        return $this->render('panneau/index.html.twig', [
            'panneaux' => $panneaux,
            'filters' => [
                'type' => $type,
                'etat' => $etat,
                'eclairage' => $eclairage,
                'recherche' => $recherche,
            ],
        ]);
    }

    #[Route('/new', name: 'app_panneau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $panneau = new Panneau();
        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/panneaux';
                // Créer le dossier s'il n'existe pas
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($uploadDir, $newFilename);
                    $panneau->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo: '.$e->getMessage());
                }
            }

            // Créer les faces selon le type
            if ($panneau->getType() === 'double') {
                $faceA = new Face();
                $faceA->setLettre('A');
                $faceA->setPanneau($panneau);
                $entityManager->persist($faceA);

                $faceB = new Face();
                $faceB->setLettre('B');
                $faceB->setPanneau($panneau);
                $entityManager->persist($faceB);
            } else {
                $faceA = new Face();
                $faceA->setLettre('A');
                $faceA->setPanneau($panneau);
                $entityManager->persist($faceA);
            }

            $entityManager->persist($panneau);
            $entityManager->flush();

            $this->addFlash('success', 'Panneau créé avec succès.');
            return $this->redirectToRoute('app_panneau_index', [], Response::HTTP_SEE_OTHER);
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
    public function edit(Request $request, Panneau $panneau, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $oldPhoto = $panneau->getPhoto();
        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/panneaux';
                // Créer le dossier s'il n'existe pas
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Supprimer l'ancienne photo si elle existe
                if ($oldPhoto) {
                    $oldPhotoPath = $uploadDir.'/'.$oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move($uploadDir, $newFilename);
                    $panneau->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo: '.$e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Panneau modifié avec succès.');
            return $this->redirectToRoute('app_panneau_index', [], Response::HTTP_SEE_OTHER);
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
            $entityManager->remove($panneau);
            $entityManager->flush();
            $this->addFlash('success', 'Panneau supprimé avec succès.');
        }

        return $this->redirectToRoute('app_panneau_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/face/{id}/locations', name: 'app_face_locations', methods: ['GET'])]
    public function faceLocations(Face $face, FaceRepository $faceRepository): Response
    {
        // Charger la face avec toutes ses locations et leurs paiements
        $face = $faceRepository->createQueryBuilder('f')
            ->leftJoin('f.locations', 'l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('l')
            ->addSelect('p')
            ->where('f.id = :id')
            ->setParameter('id', $face->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if (!$face) {
            throw $this->createNotFoundException('Face non trouvée');
        }

        // Récupérer toutes les locations et les trier par date de début (plus récentes en premier)
        $locations = $face->getLocations()->toArray();
        usort($locations, function($a, $b) {
            return $b->getDateDebut() <=> $a->getDateDebut();
        });

        return $this->render('face/locations.html.twig', [
            'face' => $face,
            'locations' => $locations,
        ]);
    }
}
