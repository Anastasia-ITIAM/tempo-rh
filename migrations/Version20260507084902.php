<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Originally seeded leave_type rows for specific production users.
 * Demo leave types (CP / RTT) are now created by the fixtures (AppFixtures),
 * so this migration is intentionally a no-op on a fresh install.
 */
final class Version20260507084902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: leave_type demo data is provided by fixtures';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
