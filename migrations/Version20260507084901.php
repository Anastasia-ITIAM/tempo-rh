<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507084901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE leave_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, max_initial_number INT NOT NULL, current_number DOUBLE PRECISION NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, archived_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_E2BC4391A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE leave_type ADD CONSTRAINT FK_E2BC4391A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE leave_request ADD comment_user LONGTEXT DEFAULT NULL, ADD days_deducted TINYINT NOT NULL, ADD leave_type_id INT DEFAULT NULL, CHANGE rtt rtt DOUBLE PRECISION DEFAULT NULL, CHANGE comment comment_admin LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE leave_request ADD CONSTRAINT FK_7DC8F7788313F474 FOREIGN KEY (leave_type_id) REFERENCES leave_type (id)');
        $this->addSql('CREATE INDEX IDX_7DC8F7788313F474 ON leave_request (leave_type_id)');
        $this->addSql('ALTER TABLE period DROP INDEX IDX_C5B81ECEF2E1C15D, ADD UNIQUE INDEX UNIQ_C5B81ECEF2E1C15D (leave_request_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leave_type DROP FOREIGN KEY FK_E2BC4391A76ED395');
        $this->addSql('DROP TABLE leave_type');
        $this->addSql('ALTER TABLE leave_request DROP FOREIGN KEY FK_7DC8F7788313F474');
        $this->addSql('DROP INDEX IDX_7DC8F7788313F474 ON leave_request');
        $this->addSql('ALTER TABLE leave_request ADD comment LONGTEXT DEFAULT NULL, DROP comment_admin, DROP comment_user, DROP days_deducted, DROP leave_type_id, CHANGE rtt rtt INT DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP INDEX UNIQ_C5B81ECEF2E1C15D, ADD INDEX IDX_C5B81ECEF2E1C15D (leave_request_id)');
    }
}
