<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\Client;
use App\Entity\Face;
use App\Entity\Location;
use App\Entity\Paiement;
use App\Entity\Panneau;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Charge les données de test dans la base de données',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Chargement des données de test...');

        // Nettoyer les données existantes
        $io->section('Nettoyage des données existantes...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Paiement')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Location')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Face')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Panneau')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Client')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Admin')->execute();

        // 1. Créer l'admin par défaut
        $io->section('Création de l\'administrateur...');
        $admin = new Admin();
        $admin->setEmail('admin@gestion-panneaux.niamey');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setNom('Admin');
        $admin->setPrenom('Système');
        $this->entityManager->persist($admin);
        $io->success('Admin créé : admin@gestion-panneaux / admin123');

        // 2. Créer les clients
        $io->section('Création des clients...');
        $clients = [];

        $clientData = [
            ['nom' => 'Orange Niger', 'type' => 'entreprise', 'email' => 'contact@orange.ne', 'telephone' => '+227 20 73 00 00', 'adresse' => 'Avenue de la République', 'ville' => 'Niamey', 'pays' => 'Niger'],
            ['nom' => 'Moov Niger', 'type' => 'entreprise', 'email' => 'info@moov.ne', 'telephone' => '+227 20 73 11 11', 'adresse' => 'Boulevard de la Liberté', 'ville' => 'Niamey', 'pays' => 'Niger'],
            ['nom' => 'SONIDEP', 'type' => 'entreprise', 'email' => 'contact@sonidep.ne', 'telephone' => '+227 20 73 22 22', 'adresse' => 'Route de l\'Aéroport', 'ville' => 'Niamey', 'pays' => 'Niger'],
            ['nom' => 'Banque Atlantique', 'type' => 'entreprise', 'email' => 'contact@banqueatlantique.ne', 'telephone' => '+227 20 73 33 33', 'adresse' => 'Place de la Concorde', 'ville' => 'Niamey', 'pays' => 'Niger'],
            ['nom' => 'Amadou Diallo', 'type' => 'personne', 'email' => 'amadou.diallo@email.ne', 'telephone' => '+227 90 12 34 56', 'adresse' => 'Quartier Plateau', 'ville' => 'Niamey', 'pays' => 'Niger'],
            ['nom' => 'Fatouma Issoufou', 'type' => 'personne', 'email' => 'fatouma.issoufou@email.ne', 'telephone' => '+227 96 78 90 12', 'adresse' => 'Quartier Terminus', 'ville' => 'Niamey', 'pays' => 'Niger'],
        ];

        foreach ($clientData as $data) {
            $client = new Client();
            $client->setNom($data['nom']);
            $client->setType($data['type']);
            $client->setEmail($data['email']);
            $client->setTelephone($data['telephone']);
            $client->setAdresse($data['adresse']);
            $client->setVille($data['ville']);
            $client->setPays($data['pays']);
            $this->entityManager->persist($client);
            $clients[] = $client;
        }
        $io->success(sprintf('%d clients créés', count($clients)));

        // 3. Créer les panneaux
        $io->section('Création des panneaux...');
        $panneaux = [];

        $panneauData = [
            [
                'emplacement' => 'Avenue de la République, près du rond-point',
                'quartier' => 'Plateau',
                'visibilite' => 'Excellente',
                'coordonneesGps' => '13.5123,2.1098',
                'taille' => '12.00',
                'type' => 'double',
                'eclairage' => true,
                'etat' => 'excellent',
                'prix' => '150000'
            ],
            [
                'emplacement' => 'Boulevard de la Liberté, face au marché',
                'quartier' => 'Terminus',
                'visibilite' => 'Bonne',
                'coordonneesGps' => '13.5089,2.1156',
                'taille' => '6.00',
                'type' => 'simple',
                'eclairage' => false,
                'etat' => 'bon',
                'prix' => '100000'
            ],
            [
                'emplacement' => 'Route de l\'Aéroport, entrée ville',
                'quartier' => 'Aéroport',
                'visibilite' => 'Excellente',
                'coordonneesGps' => '13.4815,2.1689',
                'taille' => '24.00',
                'type' => 'double',
                'eclairage' => true,
                'etat' => 'excellent',
                'prix' => '200000'
            ],
            [
                'emplacement' => 'Place de la Concorde, centre-ville',
                'quartier' => 'Centre-ville',
                'visibilite' => 'Bonne',
                'coordonneesGps' => '13.5156,2.1089',
                'taille' => '12.00',
                'type' => 'double',
                'eclairage' => true,
                'etat' => 'bon',
                'prix' => '180000'
            ],
            [
                'emplacement' => 'Quartier Plateau, route principale',
                'quartier' => 'Plateau',
                'visibilite' => 'Moyenne',
                'coordonneesGps' => null, // Optionnel - pas de coordonnées GPS
                'taille' => '6.00',
                'type' => 'simple',
                'eclairage' => false,
                'etat' => 'moyen',
                'prix' => '90000'
            ],
            [
                'emplacement' => 'Quartier Terminus, sortie ville',
                'quartier' => 'Terminus',
                'visibilite' => 'Bonne',
                'coordonneesGps' => '13.5056,2.1189',
                'taille' => '15.00',
                'type' => 'double',
                'eclairage' => true,
                'etat' => 'bon',
                'prix' => '170000'
            ],
            [
                'emplacement' => 'Avenue du Général de Gaulle',
                'quartier' => 'Plateau',
                'visibilite' => 'Moyenne',
                'coordonneesGps' => null, // Optionnel - pas de coordonnées GPS
                'taille' => '12.00',
                'type' => 'simple',
                'eclairage' => false,
                'etat' => 'moyen',
                'prix' => '110000'
            ],
            [
                'emplacement' => 'Boulevard Mali Bero, près du stade',
                'quartier' => 'Mali Bero',
                'visibilite' => 'Excellente',
                'coordonneesGps' => '13.5200,2.1200',
                'taille' => '24.00',
                'type' => 'double',
                'eclairage' => true,
                'etat' => 'excellent',
                'prix' => '220000'
            ],
        ];

        $referenceNumber = 0;

        foreach ($panneauData as $data) {
            $panneau = new Panneau();

            // Générer automatiquement la référence
            $referenceNumber++;
            $panneau->setReference(sprintf('PAN-%03d', $referenceNumber));

            $panneau->setEmplacement($data['emplacement']);
            $panneau->setQuartier($data['quartier']);

            // Visibilité optionnelle
            if (isset($data['visibilite']) && $data['visibilite'] !== null) {
                $panneau->setVisibilite($data['visibilite']);
            }

            // Coordonnées GPS optionnelles
            if (isset($data['coordonneesGps']) && $data['coordonneesGps'] !== null) {
                $panneau->setCoordonneesGps($data['coordonneesGps']);
            }

            $panneau->setTaille($data['taille']);
            $panneau->setType($data['type']);
            $panneau->setEclairage($data['eclairage']);
            $panneau->setEtat($data['etat']);
            $panneau->setPrixMensuel($data['prix']);
            $panneau->setDescription('Panneau publicitaire situé à ' . $data['emplacement'] . ' dans le quartier ' . $data['quartier']);
            $this->entityManager->persist($panneau);
            $panneaux[] = $panneau;

            // Créer les faces selon le type
            if ($data['type'] === 'double') {
                $faceA = new Face();
                $faceA->setLettre('A');
                $faceA->setPanneau($panneau);
                $this->entityManager->persist($faceA);

                $faceB = new Face();
                $faceB->setLettre('B');
                $faceB->setPanneau($panneau);
                $this->entityManager->persist($faceB);
            } else {
                $faceA = new Face();
                $faceA->setLettre('A');
                $faceA->setPanneau($panneau);
                $this->entityManager->persist($faceA);
            }
        }
        $io->success(sprintf('%d panneaux créés', count($panneaux)));

        // 4. Créer les locations
        $io->section('Création des locations...');
        $locations = [];

        // Récupérer toutes les faces
        $faces = $this->entityManager->getRepository(Face::class)->findAll();

        // Location 1 : En cours, complètement payée (paiement complet en une fois)
        if (isset($faces[0])) {
            $location1 = new Location();
            $location1->setFace($faces[0]);
            $location1->setClient($clients[0]);
            $location1->setDateDebut(new \DateTime('-2 months'));
            $location1->setDateFin(new \DateTime('+1 month'));
            $location1->setMontantMensuel('150000');
            $location1->setNotes('Location en cours pour Orange Niger');
            $this->entityManager->persist($location1);
            $locations[] = $location1;

            // Paiement complet (3 mois * 150000 = 450000)
            $paiement1 = new Paiement();
            $paiement1->setLocation($location1);
            $paiement1->setMontant('450000');
            $paiement1->setDatePaiement(new \DateTime('-2 months'));
            $paiement1->setType('paiement_complet');
            $paiement1->setNotes('Paiement complet de la location');
            $this->entityManager->persist($paiement1);
        }

        // Location 2 : En cours, partiellement payée (acompte seulement), avec prix modifié (réduction)
        if (isset($faces[2])) {
            $location2 = new Location();
            $location2->setFace($faces[2]);
            $location2->setClient($clients[1]);
            $location2->setDateDebut(new \DateTime('-1 month'));
            $location2->setDateFin(new \DateTime('+2 months'));
            // Prix réduit de 200000 à 180000 (réduction de 10%)
            $location2->setMontantMensuel('180000');
            $location2->setNotes('Remise pour fidélité client');
            $this->entityManager->persist($location2);
            $locations[] = $location2;

            // Acompte de 200000 sur 540000 (3 mois * 180000)
            $paiement2 = new Paiement();
            $paiement2->setLocation($location2);
            $paiement2->setMontant('200000');
            $paiement2->setDatePaiement(new \DateTime('-1 month'));
            $paiement2->setType('acompte');
            $paiement2->setNotes('Acompte versé au début de la location');
            $this->entityManager->persist($paiement2);
        }

        // Location 3 : Se termine bientôt, complètement payée (acompte + solde)
        if (isset($faces[3])) {
            $location3 = new Location();
            $location3->setFace($faces[3]);
            $location3->setClient($clients[2]);
            $location3->setDateDebut(new \DateTime('-3 months'));
            $location3->setDateFin(new \DateTime('+15 days'));
            $location3->setMontantMensuel('180000');
            $this->entityManager->persist($location3);
            $locations[] = $location3;

            // Acompte de 300000
            $paiement3a = new Paiement();
            $paiement3a->setLocation($location3);
            $paiement3a->setMontant('300000');
            $paiement3a->setDatePaiement(new \DateTime('-3 months'));
            $paiement3a->setType('acompte');
            $paiement3a->setNotes('Acompte de 50%');
            $this->entityManager->persist($paiement3a);

            // Solde de 420000 (4 mois * 180000 = 720000 - 300000 = 420000)
            $paiement3b = new Paiement();
            $paiement3b->setLocation($location3);
            $paiement3b->setMontant('420000');
            $paiement3b->setDatePaiement(new \DateTime('-1 month'));
            $paiement3b->setType('solde');
            $paiement3b->setNotes('Solde de la location');
            $this->entityManager->persist($paiement3b);
        }

        // Location 4 : Terminée, complètement payée (plusieurs paiements mensuels)
        if (isset($faces[5])) {
            $location4 = new Location();
            $location4->setFace($faces[5]);
            $location4->setClient($clients[3]);
            $location4->setDateDebut(new \DateTime('-6 months'));
            $location4->setDateFin(new \DateTime('-1 month'));
            $location4->setMontantMensuel('170000');
            $this->entityManager->persist($location4);
            $locations[] = $location4;

            // 5 paiements mensuels de 170000 chacun (5 mois * 170000 = 850000)
            for ($i = 0; $i < 5; $i++) {
                $paiement4 = new Paiement();
                $paiement4->setLocation($location4);
                $paiement4->setMontant('170000');
                $paiement4->setDatePaiement((new \DateTime('-6 months'))->modify("+{$i} months"));
                $paiement4->setType($i === 0 ? 'acompte' : 'autre');
                $paiement4->setNotes($i === 0 ? 'Premier paiement' : "Paiement mensuel #" . ($i + 1));
                $this->entityManager->persist($paiement4);
            }
        }

        // Location 5 : À venir, impayée, avec remise
        if (isset($faces[6])) {
            $location5 = new Location();
            $location5->setFace($faces[6]);
            $location5->setClient($clients[4]);
            $location5->setDateDebut(new \DateTime('+1 month'));
            $location5->setDateFin(new \DateTime('+4 months'));
            // Prix réduit de 110000 à 95000 (remise de 15%)
            $location5->setMontantMensuel('95000');
            $location5->setNotes('Remise pour location longue durée');
            $this->entityManager->persist($location5);
            $locations[] = $location5;
            // Aucun paiement pour cette location (impayée)
        }

        // Location 6 : Terminée, partiellement payée, avec prix majoré
        if (isset($faces[7])) {
            $location6 = new Location();
            $location6->setFace($faces[7]);
            $location6->setClient($clients[5]);
            $location6->setDateDebut(new \DateTime('-4 months'));
            $location6->setDateFin(new \DateTime('-1 week'));
            // Prix majoré de 220000 à 250000
            $location6->setMontantMensuel('250000');
            $location6->setNotes('Majoration pour emplacement premium');
            $this->entityManager->persist($location6);
            $locations[] = $location6;

            // Un seul paiement partiel de 500000 sur 1000000 (4 mois * 250000)
            $paiement6 = new Paiement();
            $paiement6->setLocation($location6);
            $paiement6->setMontant('500000');
            $paiement6->setDatePaiement(new \DateTime('-3 months'));
            $paiement6->setType('acompte');
            $paiement6->setNotes('Acompte partiel, solde en attente');
            $this->entityManager->persist($paiement6);
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d locations créées', count($locations)));

        // Compter les paiements créés
        $totalPaiements = $this->entityManager->getRepository(Paiement::class)->count([]);
        $io->success(sprintf('%d paiements créés', $totalPaiements));

        $io->success('Données de test chargées avec succès !');

        return Command::SUCCESS;
    }
}
