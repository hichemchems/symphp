<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251101204831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE statistics (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, period VARCHAR(20) NOT NULL, date DATETIME NOT NULL, revenue_ht NUMERIC(10, 2) NOT NULL, revenue_ttc NUMERIC(10, 2) NOT NULL, charges NUMERIC(10, 2) NOT NULL, commission NUMERIC(10, 2) NOT NULL, profit NUMERIC(10, 2) NOT NULL, clients_count INT NOT NULL, INDEX IDX_E2D38B228C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE statistics ADD CONSTRAINT FK_E2D38B228C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE statistics DROP FOREIGN KEY FK_E2D38B228C03F15C');
        $this->addSql('DROP TABLE statistics');
    }
}
