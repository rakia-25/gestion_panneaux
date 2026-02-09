<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table notification pour le système de notifications in-app.
 */
final class Version20260131120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table for in-app notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            destinataire_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            titre VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            route VARCHAR(100) DEFAULT NULL,
            route_params JSON DEFAULT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            lu_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_BF5476CA4F8E2168 (destinataire_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA4F8E2168 FOREIGN KEY (destinataire_id) REFERENCES `admin` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA4F8E2168');
        $this->addSql('DROP TABLE notification');
    }
}
