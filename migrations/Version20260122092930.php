<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122092930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD raison_modification_prix LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE panneau ADD quartier VARCHAR(100) DEFAULT NULL, ADD rue VARCHAR(100) DEFAULT NULL, ADD coordonnees_gps VARCHAR(50) DEFAULT NULL, ADD eclairage TINYINT(1) NOT NULL DEFAULT 0, ADD etat VARCHAR(50) DEFAULT NULL, CHANGE taille taille NUMERIC(8, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP raison_modification_prix');
        $this->addSql('ALTER TABLE panneau DROP quartier, DROP rue, DROP coordonnees_gps, DROP eclairage, DROP etat, CHANGE taille taille VARCHAR(100) NOT NULL');
    }
}
