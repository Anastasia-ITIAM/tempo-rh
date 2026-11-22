<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LeaveRequest\Period;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Period::class)]
final class PeriodTest extends TestCase
{
    /**
     * Le départ est l'heure où le congé commence, la fin est l'heure de reprise du travail.
     */
    public static function periodProvider(): iterable
    {
        yield 'une semaine complète, reprise le lundi matin' => ['2026-11-23 08:00', '2026-11-30 08:00', 5.0];
        yield 'un jour et demi, reprise le mardi à midi' => ['2026-11-23 08:00', '2026-11-24 12:00', 1.5];
        yield 'une demi-journée, départ à midi et reprise le lendemain matin' => ['2026-11-23 12:00', '2026-11-24 08:00', 0.5];
        yield 'le week-end ne compte pas' => ['2026-11-27 08:00', '2026-12-01 08:00', 2.0];
        yield 'un week-end seul vaut zéro jour' => ['2026-11-28 08:00', '2026-11-29 08:00', 0.0];
    }

    #[DataProvider('periodProvider')]
    public function testDurationCountsWorkingDaysAndHalfDays(string $start, string $end, float $expected): void
    {
        $period = (new Period())
            ->setStartedAt(new \DateTimeImmutable($start))
            ->setEndedAt(new \DateTimeImmutable($end));

        self::assertSame($expected, $period->getDuration());
    }

    public function testDurationIsZeroWithoutDates(): void
    {
        self::assertSame(0.0, (float) (new Period())->getDuration());
    }
}
