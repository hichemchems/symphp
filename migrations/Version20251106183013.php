<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106183013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make validated_at and paid_at nullable in weekly_commissions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_commissions MODIFY validated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE weekly_commissions MODIFY paid_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_commissions MODIFY validated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE weekly_commissions MODIFY paid_at DATETIME NOT NULL');
    }
}
