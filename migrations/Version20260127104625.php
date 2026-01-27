<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127104625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location CHANGE montant_mensuel montant_mensuel NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE paiement CHANGE montant montant NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE panneau CHANGE prix_mensuel prix_mensuel NUMERIC(10, 0) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location CHANGE montant_mensuel montant_mensuel NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE paiement CHANGE montant montant NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE panneau CHANGE prix_mensuel prix_mensuel NUMERIC(10, 2) NOT NULL');
    }
}
