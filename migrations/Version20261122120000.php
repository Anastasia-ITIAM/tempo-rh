<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index pour le renouvellement des types de congés et la recherche des feuilles de temps.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_leave_type_renewal ON leave_type (archived_at, end_date)');
        $this->addSql('CREATE INDEX idx_timesheet_week_user_start ON time_sheet_week (user_id, start_date)');
        $this->addSql('CREATE INDEX idx_timesheet_week_status_start ON time_sheet_week (status, start_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_leave_type_renewal ON leave_type');
        $this->addSql('DROP INDEX idx_timesheet_week_user_start ON time_sheet_week');
        $this->addSql('DROP INDEX idx_timesheet_week_status_start ON time_sheet_week');
    }
}
