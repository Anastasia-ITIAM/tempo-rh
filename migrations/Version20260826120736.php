<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260826120736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE time_sheet_week_history (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, time_sheet_week_id INT NOT NULL, action_by_id INT NOT NULL, INDEX IDX_BD428A29CCC27C1D (time_sheet_week_id), INDEX IDX_BD428A294D29ECB8 (action_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE time_sheet_week_history ADD CONSTRAINT FK_BD428A29CCC27C1D FOREIGN KEY (time_sheet_week_id) REFERENCES time_sheet_week (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE time_sheet_week_history ADD CONSTRAINT FK_BD428A294D29ECB8 FOREIGN KEY (action_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE time_sheet_entry CHANGE client_id client_id INT NOT NULL, CHANGE project_id project_id INT NOT NULL, CHANGE task_id task_id INT NOT NULL');
        $this->addSql('ALTER TABLE time_sheet_week DROP FOREIGN KEY `FK_80BE3530C69DE5E5`');
        $this->addSql('DROP INDEX IDX_80BE3530C69DE5E5 ON time_sheet_week');
        $this->addSql('ALTER TABLE time_sheet_week CHANGE validated_at reviewed_at DATETIME DEFAULT NULL, CHANGE validated_by_id reviewed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE time_sheet_week ADD CONSTRAINT FK_80BE3530FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_80BE3530FC6B21F1 ON time_sheet_week (reviewed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_sheet_week_history DROP FOREIGN KEY FK_BD428A29CCC27C1D');
        $this->addSql('ALTER TABLE time_sheet_week_history DROP FOREIGN KEY FK_BD428A294D29ECB8');
        $this->addSql('DROP TABLE time_sheet_week_history');
        $this->addSql('ALTER TABLE time_sheet_entry CHANGE client_id client_id INT DEFAULT NULL, CHANGE project_id project_id INT DEFAULT NULL, CHANGE task_id task_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE time_sheet_week DROP FOREIGN KEY FK_80BE3530FC6B21F1');
        $this->addSql('DROP INDEX IDX_80BE3530FC6B21F1 ON time_sheet_week');
        $this->addSql('ALTER TABLE time_sheet_week CHANGE reviewed_at validated_at DATETIME DEFAULT NULL, CHANGE reviewed_by_id validated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE time_sheet_week ADD CONSTRAINT `FK_80BE3530C69DE5E5` FOREIGN KEY (validated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_80BE3530C69DE5E5 ON time_sheet_week (validated_by_id)');
    }
}
