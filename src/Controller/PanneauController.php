<?php

namespace App\Controller;

use App\Entity\Face;
use App\Entity\Panneau;
use App\Form\PanneauType;
use App\Repository\PanneauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panneau')]
class PanneauController extends AbstractController
{
    #[Route('/', name: 'app_panneau_index', methods: ['GET'])]
    public function index(PanneauRepository $panneauRepository): Response
    {
        return $this->render('panneau/index.html.twig', [
            'panneaux' => $panneauRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_panneau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $panneau = new Panneau();
        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function edit(Request $request, Panneau $panneau, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PanneauType::class, $panneau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
}
