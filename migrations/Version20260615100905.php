<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615100905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, client_id INT NOT NULL, INDEX IDX_2FB3D0EE19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, project_id INT NOT NULL, INDEX IDX_527EDB25166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE time_sheet_entry (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, hours NUMERIC(4, 2) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, time_sheet_week_id INT NOT NULL, client_id INT NOT NULL, project_id INT NOT NULL, task_id INT NOT NULL, INDEX IDX_96478B30CCC27C1D (time_sheet_week_id), INDEX IDX_96478B3019EB6921 (client_id), INDEX IDX_96478B30166D1F9C (project_id), INDEX IDX_96478B308DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE time_sheet_week (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, status VARCHAR(255) NOT NULL, submitted_at DATETIME DEFAULT NULL, validated_at DATETIME DEFAULT NULL, comment LONGTEXT DEFAULT NULL, user_id INT NOT NULL, validated_by_id INT DEFAULT NULL, INDEX IDX_80BE3530A76ED395 (user_id), INDEX IDX_80BE3530C69DE5E5 (validated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE time_sheet_entry ADD CONSTRAINT FK_96478B30CCC27C1D FOREIGN KEY (time_sheet_week_id) REFERENCES time_sheet_week (id)');
        $this->addSql('ALTER TABLE time_sheet_entry ADD CONSTRAINT FK_96478B3019EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE time_sheet_entry ADD CONSTRAINT FK_96478B30166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE time_sheet_entry ADD CONSTRAINT FK_96478B308DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE time_sheet_week ADD CONSTRAINT FK_80BE3530A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE time_sheet_week ADD CONSTRAINT FK_80BE3530C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE19EB6921');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        $this->addSql('ALTER TABLE time_sheet_entry DROP FOREIGN KEY FK_96478B30CCC27C1D');
        $this->addSql('ALTER TABLE time_sheet_entry DROP FOREIGN KEY FK_96478B3019EB6921');
        $this->addSql('ALTER TABLE time_sheet_entry DROP FOREIGN KEY FK_96478B30166D1F9C');
        $this->addSql('ALTER TABLE time_sheet_entry DROP FOREIGN KEY FK_96478B308DB60186');
        $this->addSql('ALTER TABLE time_sheet_week DROP FOREIGN KEY FK_80BE3530A76ED395');
        $this->addSql('ALTER TABLE time_sheet_week DROP FOREIGN KEY FK_80BE3530C69DE5E5');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE time_sheet_entry');
        $this->addSql('DROP TABLE time_sheet_week');
    }
}
