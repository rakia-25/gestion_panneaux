<?php

namespace App\Service;

use App\Entity\Admin;
use App\Entity\Location;
use App\Entity\Notification;
use App\Entity\Paiement;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminRepository $adminRepository
    ) {
    }

    /**
     * Crée une notification "Paiement reçu" pour tous les admins.
     */
    public function notifyPaiementRecu(Paiement $paiement): void
    {
        $location = $paiement->getLocation();
        if (!$location) {
            return;
        }
        $montant = number_format((float) $paiement->getMontant(), 0, ',', ' ');
        $titre = 'Paiement reçu';
        $message = sprintf(
            '%s FCFA enregistré pour la location %s — %s.',
            $montant,
            $location->getFace() ? $location->getFace()->getNomComplet() : 'N/A',
            $location->getClient() ? $location->getClient()->getNom() : 'N/A'
        );
        $this->createForAllAdmins(
            Notification::TYPE_PAIEMENT_RECU,
            $titre,
            $message,
            'app_location_show',
            ['id' => $location->getId()]
        );
    }

    /**
     * Crée une notification "Location se termine dans X jours" pour tous les admins.
     */
    public function notifyFinLocation(Location $location, int $joursRestants): void
    {
        $face = $location->getFace();
        $client = $location->getClient();
        $titre = 'Fin de location proche';
        $message = sprintf(
            'La location %s — %s se termine dans %d jour(s). Fin prévue le %s.',
            $face ? $face->getNomComplet() : 'N/A',
            $client ? $client->getNom() : 'N/A',
            $joursRestants,
            $location->getDateFin() ? $location->getDateFin()->format('d/m/Y') : ''
        );
        $this->createForAllAdmins(
            Notification::TYPE_FIN_LOCATION,
            $titre,
            $message,
            'app_location_show',
            ['id' => $location->getId()]
        );
    }

    /**
     * Crée une notification "Location impayée" pour tous les admins.
     */
    public function notifyImpaye(Location $location): void
    {
        $face = $location->getFace();
        $client = $location->getClient();
        $titre = 'Location impayée';
        $message = sprintf(
            'La location %s — %s est impayée ou partiellement payée. Montant mensuel : %s FCFA.',
            $face ? $face->getNomComplet() : 'N/A',
            $client ? $client->getNom() : 'N/A',
            number_format((float) $location->getMontantMensuel(), 0, ',', ' ')
        );
        $this->createForAllAdmins(
            Notification::TYPE_IMPAYE,
            $titre,
            $message,
            'app_location_show',
            ['id' => $location->getId()]
        );
    }

    private function createForAllAdmins(
        string $type,
        string $titre,
        string $message,
        ?string $route = null,
        ?array $routeParams = null
    ): void {
        $admins = $this->adminRepository->findAll();
        foreach ($admins as $admin) {
            if (!$admin instanceof Admin) {
                continue;
            }
            $notification = new Notification();
            $notification->setDestinataire($admin);
            $notification->setType($type);
            $notification->setTitre($titre);
            $notification->setMessage($message);
            $notification->setRoute($route);
            $notification->setRouteParams($routeParams);
            $this->entityManager->persist($notification);
        }
        $this->entityManager->flush();
    }
}
