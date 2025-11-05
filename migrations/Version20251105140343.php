<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105140343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create weekly_commissions table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE weekly_commissions (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, total_commission DECIMAL(10, 2) NOT NULL, total_revenue_ht DECIMAL(10, 2) NOT NULL, clients_count INT NOT NULL, week_start DATETIME NOT NULL, week_end DATETIME NOT NULL, validated TINYINT(1) NOT NULL, paid TINYINT(1) NOT NULL, validated_at DATETIME DEFAULT NULL, paid_at DATETIME DEFAULT NULL, INDEX IDX_1234567890_employee_id (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE weekly_commissions ADD CONSTRAINT FK_1234567890_employee_id FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE weekly_commissions');
    }
}
