<?php

namespace App\Command;

use App\Repository\LocationRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notifications-rappels',
    description: 'Crée les notifications de rappel : locations se terminant bientôt (30, 15, 7 jours) et locations impayées.',
)]
class NotificationsRappelsCommand extends Command
{
    public function __construct(
        private LocationRepository $locationRepository,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Génération des notifications de rappel');

        $count = 0;

        // Locations se terminant dans les 30 prochains jours (une notif par location : 7, 15 ou 30 j selon la fin)
        $locations = $this->locationRepository->findFinissantBientotWithPaiements(30);
        $now = new \DateTimeImmutable();
        foreach ($locations as $location) {
            if ($location->isAnnulee()) {
                continue;
            }
            $dateFin = $location->getDateFin();
            if (!$dateFin) {
                continue;
            }
            $fin = $dateFin instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($dateFin) : new \DateTimeImmutable($dateFin->format('c'));
            $joursRestants = (int) $now->diff($fin)->days;
            if ($joursRestants <= 0) {
                continue;
            }
            $label = $joursRestants <= 7 ? 7 : ($joursRestants <= 15 ? 15 : 30);
            $this->notificationService->notifyFinLocation($location, $label);
            $count++;
        }

        // Locations impayées (exclure annulées)
        $impayees = $this->locationRepository->findImpayees();
        foreach ($impayees as $location) {
            if ($location->isAnnulee()) {
                continue;
            }
            $this->notificationService->notifyImpaye($location);
            $count++;
        }

        $io->success(sprintf('%d notification(s) de rappel créée(s).', $count));
        return Command::SUCCESS;
    }
}
