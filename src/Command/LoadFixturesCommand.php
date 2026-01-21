<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\Client;
use App\Entity\Face;
use App\Entity\Location;
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
        $io->success('Admin créé : admin@gestion-panneaux.niamey / admin123');

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
            ['reference' => 'PAN-001', 'emplacement' => 'Avenue de la République, près du rond-point', 'taille' => '4x3m', 'type' => 'double', 'prix' => '150000'],
            ['reference' => 'PAN-002', 'emplacement' => 'Boulevard de la Liberté, face au marché', 'taille' => '3x2m', 'type' => 'simple', 'prix' => '100000'],
            ['reference' => 'PAN-003', 'emplacement' => 'Route de l\'Aéroport, entrée ville', 'taille' => '6x4m', 'type' => 'double', 'prix' => '200000'],
            ['reference' => 'PAN-004', 'emplacement' => 'Place de la Concorde, centre-ville', 'taille' => '4x3m', 'type' => 'double', 'prix' => '180000'],
            ['reference' => 'PAN-005', 'emplacement' => 'Quartier Plateau, route principale', 'taille' => '3x2m', 'type' => 'simple', 'prix' => '90000'],
            ['reference' => 'PAN-006', 'emplacement' => 'Quartier Terminus, sortie ville', 'taille' => '5x3m', 'type' => 'double', 'prix' => '170000'],
            ['reference' => 'PAN-007', 'emplacement' => 'Avenue du Général de Gaulle', 'taille' => '4x3m', 'type' => 'simple', 'prix' => '110000'],
            ['reference' => 'PAN-008', 'emplacement' => 'Boulevard Mali Bero, près du stade', 'taille' => '6x4m', 'type' => 'double', 'prix' => '220000'],
        ];

        foreach ($panneauData as $data) {
            $panneau = new Panneau();
            $panneau->setReference($data['reference']);
            $panneau->setEmplacement($data['emplacement']);
            $panneau->setTaille($data['taille']);
            $panneau->setType($data['type']);
            $panneau->setPrixMensuel($data['prix']);
            $panneau->setDescription('Panneau publicitaire situé à ' . $data['emplacement']);
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
        
        // Location 1 : En cours, payée
        if (isset($faces[0])) {
            $location1 = new Location();
            $location1->setFace($faces[0]);
            $location1->setClient($clients[0]);
            $location1->setDateDebut(new \DateTime('-2 months'));
            $location1->setDateFin(new \DateTime('+1 month'));
            $location1->setMontantMensuel('150000');
            $location1->setEstPaye(true);
            $location1->setNotes('Location en cours pour Orange Niger');
            $this->entityManager->persist($location1);
            $locations[] = $location1;
        }

        // Location 2 : En cours, impayée
        if (isset($faces[2])) {
            $location2 = new Location();
            $location2->setFace($faces[2]);
            $location2->setClient($clients[1]);
            $location2->setDateDebut(new \DateTime('-1 month'));
            $location2->setDateFin(new \DateTime('+2 months'));
            $location2->setMontantMensuel('200000');
            $location2->setEstPaye(false);
            $location2->setNotes('Paiement en attente');
            $this->entityManager->persist($location2);
            $locations[] = $location2;
        }

        // Location 3 : Se termine bientôt
        if (isset($faces[3])) {
            $location3 = new Location();
            $location3->setFace($faces[3]);
            $location3->setClient($clients[2]);
            $location3->setDateDebut(new \DateTime('-3 months'));
            $location3->setDateFin(new \DateTime('+15 days'));
            $location3->setMontantMensuel('180000');
            $location3->setEstPaye(true);
            $this->entityManager->persist($location3);
            $locations[] = $location3;
        }

        // Location 4 : Terminée, payée
        if (isset($faces[5])) {
            $location4 = new Location();
            $location4->setFace($faces[5]);
            $location4->setClient($clients[3]);
            $location4->setDateDebut(new \DateTime('-6 months'));
            $location4->setDateFin(new \DateTime('-1 month'));
            $location4->setMontantMensuel('170000');
            $location4->setEstPaye(true);
            $this->entityManager->persist($location4);
            $locations[] = $location4;
        }

        // Location 5 : À venir
        if (isset($faces[6])) {
            $location5 = new Location();
            $location5->setFace($faces[6]);
            $location5->setClient($clients[4]);
            $location5->setDateDebut(new \DateTime('+1 month'));
            $location5->setDateFin(new \DateTime('+4 months'));
            $location5->setMontantMensuel('110000');
            $location5->setEstPaye(false);
            $this->entityManager->persist($location5);
            $locations[] = $location5;
        }

        // Location 6 : Terminée, impayée
        if (isset($faces[7])) {
            $location6 = new Location();
            $location6->setFace($faces[7]);
            $location6->setClient($clients[5]);
            $location6->setDateDebut(new \DateTime('-4 months'));
            $location6->setDateFin(new \DateTime('-1 week'));
            $location6->setMontantMensuel('220000');
            $location6->setEstPaye(false);
            $location6->setNotes('Location terminée mais impayée');
            $this->entityManager->persist($location6);
            $locations[] = $location6;
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d locations créées', count($locations)));

        $io->success('Données de test chargées avec succès !');
        
        return Command::SUCCESS;
    }
}
