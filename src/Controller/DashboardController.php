<?php

namespace App\Controller;

use App\Repository\LocationRepository;
use App\Repository\PanneauRepository;
use App\Repository\ClientRepository;
use App\Repository\FaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        PanneauRepository $panneauRepository,
        FaceRepository $faceRepository,
        ClientRepository $clientRepository,
        LocationRepository $locationRepository
    ): Response {
        // Statistiques globales
        $totalPanneaux = $panneauRepository->count([]);
        $totalFaces = $faceRepository->count([]);
        $totalClients = $clientRepository->count([]);
        
        // Compter les faces disponibles et occupées
        $faces = $faceRepository->findAll();
        $facesDisponibles = 0;
        $facesOccupees = 0;
        
        foreach ($faces as $face) {
            $locationActive = $face->getLocationActive();
            if ($locationActive) {
                $facesOccupees++;
            } else {
                $facesDisponibles++;
            }
        }
        
        // Revenus
        $locationsActives = $locationRepository->findActive();
        $revenuMensuel = 0;
        foreach ($locationsActives as $location) {
            $revenuMensuel += (float) $location->getMontantMensuel();
        }
        
        // Locations finissant bientôt (dans 30 jours) - charger avec paiements
        $locationsFinissantBientot = $locationRepository->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->where('l.dateFin >= :now')
            ->andWhere('l.dateFin <= :dateLimite')
            ->setParameter('now', new \DateTime())
            ->setParameter('dateLimite', (new \DateTime())->modify('+30 days'))
            ->orderBy('l.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Locations impayées
        $locationsImpayees = $locationRepository->findImpayees();
        
        // Dernières locations - charger avec paiements
        $dernieresLocations = $locationRepository->createQueryBuilder('l')
            ->leftJoin('l.paiements', 'p')
            ->addSelect('p')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('dashboard/index.html.twig', [
            'totalPanneaux' => $totalPanneaux,
            'totalFaces' => $totalFaces,
            'totalClients' => $totalClients,
            'facesDisponibles' => $facesDisponibles,
            'facesOccupees' => $facesOccupees,
            'revenuMensuel' => $revenuMensuel,
            'locationsFinissantBientot' => $locationsFinissantBientot,
            'locationsImpayees' => $locationsImpayees,
            'dernieresLocations' => $dernieresLocations,
        ]);
    }
}
