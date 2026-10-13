<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507084902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed leave_type data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO leave_type (
                code,
                max_initial_number,
                current_number,
                start_date,
                end_date,
                archived_at,
                user_id
            ) VALUES
            ('RTT', 12, 12, '2026-01-01', '2026-12-31', NULL, 1),
            ('RTT', 12, 12, '2026-01-01', '2026-12-31', NULL, 2),
            ('RTT', 12, 12, '2026-01-01', '2026-12-31', NULL, 7),
            ('RTT', 12, 12, '2026-01-01', '2026-12-31', NULL, 8),

            ('CP', 25, 25, '2026-06-01', '2027-05-31', NULL, 1),
            ('CP', 25, 25, '2026-06-01', '2027-05-31', NULL, 2),
            ('CP', 25, 25, '2026-06-01', '2027-05-31', NULL, 7),
            ('CP', 25, 25, '2026-06-01', '2027-05-31', NULL, 8)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM leave_type
            WHERE user_id IN (1, 2, 7, 8)
              AND code IN ('RTT', 'CP')
        ");
    }
}
