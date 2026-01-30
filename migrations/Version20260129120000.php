<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute l'état par face : chaque face d'un panneau peut avoir son propre état (ex: face A bon, face B hors service).
 */
final class Version20260129120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add etat column to face table (state per face)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE face ADD etat VARCHAR(50) NOT NULL DEFAULT \'bon\'');
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (in_array($platform, ['mysql', 'mariadb'], true)) {
            $this->addSql('UPDATE face f INNER JOIN panneau p ON f.panneau_id = p.id SET f.etat = COALESCE(p.etat, \'bon\')');
        } elseif ($platform === 'sqlite') {
            $this->addSql('UPDATE face SET etat = (SELECT COALESCE(p.etat, \'bon\') FROM panneau p WHERE p.id = face.panneau_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE face DROP etat');
    }
}
