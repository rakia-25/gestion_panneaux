<?php

namespace App\Controller;

use App\Repository\LocationRepository;
use App\Repository\PanneauRepository;
use App\Repository\ClientRepository;
use App\Repository\FaceRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    private const PERIODES_VALIDES = ['7D', '30D', '90D', '1Y'];

    #[Route('/', name: 'app_dashboard')]
    public function index(
        Request $request,
        PanneauRepository $panneauRepository,
        FaceRepository $faceRepository,
        ClientRepository $clientRepository,
        LocationRepository $locationRepository,
        PaiementRepository $paiementRepository
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

        // Données pour le graphique selon la période sélectionnée
        $periode = $request->query->get('periode', '1Y');
        if (!in_array($periode, self::PERIODES_VALIDES, true)) {
            $periode = '1Y';
        }
        $fin = new \DateTime();
        $debut = clone $fin;
        switch ($periode) {
            case '7D':
                $debut->modify('-6 days'); // 7 jours : aujourd'hui + 6 jours en arrière
                $granularite = 'day';
                break;
            case '30D':
                $debut->modify('-29 days'); // 30 jours : aujourd'hui + 29 jours en arrière
                $granularite = 'day';
                break;
            case '90D':
                $debut->modify('-90 days');
                $granularite = 'week';
                break;
            case '1Y':
            default:
                $debut->modify('-1 year');
                $granularite = 'month';
                break;
        }
        $donneesEncaisses = $paiementRepository->getRevenusEncaissesParPeriode($debut, $fin, $granularite);
        $donneesPrevus = $locationRepository->getRevenusPrevusParPeriode($debut, $fin, $granularite);

        // Activités récentes : locations et paiements fusionnés par date
        $derniersPaiements = $paiementRepository->findDerniersValidesWithLocation(10);
        $activites = [];
        foreach ($dernieresLocations as $loc) {
            $activites[] = [
                'type' => 'location',
                'date' => $loc->getCreatedAt(),
                'location' => $loc,
            ];
        }
        foreach ($derniersPaiements as $p) {
            $activites[] = [
                'type' => 'paiement',
                'date' => $p->getDatePaiement() ?? $p->getCreatedAt(),
                'paiement' => $p,
            ];
        }
        usort($activites, fn($a, $b) => $b['date'] <=> $a['date']);
        $activites = array_slice($activites, 0, 8);
        
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
            'revenusEncaisses' => $donneesEncaisses['values'],
            'revenusPrevus' => $donneesPrevus['values'],
            'chartLabels' => $donneesEncaisses['labels'],
            'periode' => $periode,
            'activites' => $activites,
        ]);
    }
}
