<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327134023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE expense_report (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, billed_at DATETIME DEFAULT NULL, refunded_at DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, amount INT NOT NULL, path VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, requested_by_id INT NOT NULL, INDEX IDX_280A6914DA1E751 (requested_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE leave_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, rtt INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, requested_by_id INT NOT NULL, reason_id INT NOT NULL, INDEX IDX_7DC8F7784DA1E751 (requested_by_id), INDEX IDX_7DC8F77859BB1592 (reason_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient VARCHAR(255) NOT NULL, flag VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, error LONGTEXT DEFAULT NULL, context JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE period (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME NOT NULL, leave_request_id INT NOT NULL, INDEX IDX_C5B81ECEF2E1C15D (leave_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reason (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, last_name VARCHAR(250) NOT NULL, first_name VARCHAR(250) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_by VARCHAR(255) DEFAULT NULL, updated_by VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE expense_report ADD CONSTRAINT FK_280A6914DA1E751 FOREIGN KEY (requested_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE leave_request ADD CONSTRAINT FK_7DC8F7784DA1E751 FOREIGN KEY (requested_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE leave_request ADD CONSTRAINT FK_7DC8F77859BB1592 FOREIGN KEY (reason_id) REFERENCES reason (id)');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECEF2E1C15D FOREIGN KEY (leave_request_id) REFERENCES leave_request (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE expense_report DROP FOREIGN KEY FK_280A6914DA1E751');
        $this->addSql('ALTER TABLE leave_request DROP FOREIGN KEY FK_7DC8F7784DA1E751');
        $this->addSql('ALTER TABLE leave_request DROP FOREIGN KEY FK_7DC8F77859BB1592');
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECEF2E1C15D');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE expense_report');
        $this->addSql('DROP TABLE leave_request');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE period');
        $this->addSql('DROP TABLE reason');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
