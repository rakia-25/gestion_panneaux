<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Supprime la colonne etat de la table panneau : l'état est désormais porté par chaque face.
 */
final class Version20260129140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove etat column from panneau (state is now on face only)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE panneau DROP etat');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE panneau ADD etat VARCHAR(50) DEFAULT NULL');
    }
}
