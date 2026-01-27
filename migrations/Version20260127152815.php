<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127152815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD statut VARCHAR(20) NOT NULL DEFAULT \'active\', ADD date_annulation DATETIME DEFAULT NULL, ADD raison_annulation LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement ADD statut VARCHAR(20) NOT NULL DEFAULT \'valide\', ADD date_annulation DATETIME DEFAULT NULL, ADD raison_annulation LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP statut, DROP date_annulation, DROP raison_annulation');
        $this->addSql('ALTER TABLE paiement DROP statut, DROP date_annulation, DROP raison_annulation');
    }
}
