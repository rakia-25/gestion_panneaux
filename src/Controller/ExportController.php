<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\LocationRepository;
use App\Repository\PaiementRepository;
use App\Repository\PanneauRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/export')]
class ExportController extends AbstractController
{
    #[Route('', name: 'app_export_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('export/index.html.twig');
    }

    #[Route('/panneaux', name: 'app_export_panneaux', methods: ['GET'])]
    public function panneaux(Request $request, PanneauRepository $repository): Response
    {
        $panneaux = $repository->findWithFilters(
            $request->query->get('type'),
            $request->query->get('etat'),
            $request->query->get('eclairage'),
            $request->query->get('recherche'),
            $request->query->get('statut_actif')
        );

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Référence', 'Emplacement', 'Quartier', 'Visibilité', 'Coordonnées GPS',
            'Taille (m²)', 'Type', 'Éclairage', 'État global', 'Prix mensuel (FCFA)',
            'Actif', 'Description',
        ], ';');
        foreach ($panneaux as $p) {
            fputcsv($out, [
                $p->getReference() ?? '',
                $p->getEmplacement() ?? '',
                $p->getQuartier() ?? '',
                $p->getVisibilite() ?? '',
                $p->getCoordonneesGps() ?? '',
                $p->getTaille() ?? '',
                $p->getType() ?? '',
                $p->isEclairage() ? 'Oui' : 'Non',
                $p->getEtatGlobal(),
                $p->getPrixMensuel() ?? '',
                $p->isActif() ? 'Oui' : 'Non',
                $p->getDescription() ?? '',
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="panneaux_' . date('Y-m-d_His') . '.csv"',
        ]);

        return $response;
    }

    #[Route('/clients', name: 'app_export_clients', methods: ['GET'])]
    public function clients(ClientRepository $repository): Response
    {
        $clients = $repository->findBy([], ['nom' => 'ASC']);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Nom', 'Type', 'Email', 'Téléphone', 'Adresse', 'Ville', 'Pays', 'Notes',
        ], ';');
        foreach ($clients as $c) {
            fputcsv($out, [
                $c->getNom() ?? '',
                $c->getType() ?? '',
                $c->getEmail() ?? '',
                $c->getTelephone() ?? '',
                $c->getAdresse() ?? '',
                $c->getVille() ?? '',
                $c->getPays() ?? '',
                $c->getNotes() ?? '',
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="clients_' . date('Y-m-d_His') . '.csv"',
        ]);

        return $response;
    }

    #[Route('/locations', name: 'app_export_locations', methods: ['GET'])]
    public function locations(LocationRepository $repository): Response
    {
        $locations = $repository->findBy([], ['dateDebut' => 'DESC']);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Panneau', 'Face', 'Client', 'Date début', 'Date fin', 'Montant mensuel (FCFA)',
            'Statut', 'Payé', 'Notes',
        ], ';');
        foreach ($locations as $loc) {
            $face = $loc->getFace();
            $panneau = $face ? $face->getPanneau() : null;
            fputcsv($out, [
                $panneau ? $panneau->getReference() : '',
                $face ? ('Face ' . $face->getLettre()) : '',
                $loc->getClient() ? $loc->getClient()->getNom() : '',
                $loc->getDateDebut() ? $loc->getDateDebut()->format('d/m/Y') : '',
                $loc->getDateFin() ? $loc->getDateFin()->format('d/m/Y') : '',
                $loc->getMontantMensuel() ?? '',
                $loc->getStatut() ?? '',
                $loc->isEstPaye() ? 'Oui' : 'Non',
                $loc->getNotes() ?? '',
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="locations_' . date('Y-m-d_His') . '.csv"',
        ]);

        return $response;
    }

    #[Route('/paiements', name: 'app_export_paiements', methods: ['GET'])]
    public function paiements(PaiementRepository $repository): Response
    {
        $paiements = $repository->findAllOrderedByDateWithLocation();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Date paiement', 'Montant (FCFA)', 'Type', 'Statut', 'Location (panneau / client)',
            'Notes', 'Annulé le', 'Raison annulation',
        ], ';');
        foreach ($paiements as $p) {
            $loc = $p->getLocation();
            $locLabel = '';
            if ($loc) {
                $face = $loc->getFace();
                $panneau = $face ? $face->getPanneau() : null;
                $client = $loc->getClient();
                $locLabel = ($panneau ? $panneau->getReference() : '') . ' / ' . ($client ? $client->getNom() : '');
            }
            fputcsv($out, [
                $p->getDatePaiement() ? $p->getDatePaiement()->format('d/m/Y') : '',
                $p->getMontant() ?? '',
                $p->getType() ?? '',
                $p->getStatut() ?? '',
                $locLabel,
                $p->getNotes() ?? '',
                $p->getDateAnnulation() ? $p->getDateAnnulation()->format('d/m/Y H:i') : '',
                $p->getRaisonAnnulation() ?? '',
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="paiements_' . date('Y-m-d_His') . '.csv"',
        ]);

        return $response;
    }
}
