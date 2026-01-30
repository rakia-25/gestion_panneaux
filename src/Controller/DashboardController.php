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
        
        $revenuMensuel = $locationRepository->getRevenuMensuelActif();
        $locationsFinissantBientot = $locationRepository->findFinissantBientotWithPaiements(30);
        $locationsImpayees = $locationRepository->findImpayees();
        $dernieresLocations = $locationRepository->findDernieresWithPaiements(5);
        
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
