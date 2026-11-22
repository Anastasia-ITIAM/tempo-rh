<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\TimeSheet\TimeSheetWeek;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeSheetWeek::class)]
final class TimeSheetWeekTest extends TestCase
{
    public static function dateProvider(): iterable
    {
        yield 'un lundi reste le même lundi' => ['2026-11-23 15:30', '2026-11-23 00:00'];
        yield 'un mercredi donne le lundi de la semaine' => ['2026-11-25 09:00', '2026-11-23 00:00'];
        yield 'un dimanche donne le lundi précédent' => ['2026-11-29 23:59', '2026-11-23 00:00'];
        yield 'la semaine peut commencer le mois précédent' => ['2026-12-02 10:00', '2026-11-30 00:00'];
    }

    #[DataProvider('dateProvider')]
    public function testGetMondayOfWeek(string $date, string $expectedMonday): void
    {
        $monday = TimeSheetWeek::getMondayOfWeek(new \DateTime($date));

        self::assertSame($expectedMonday, $monday->format('Y-m-d H:i'));
    }

    public function testGetMondayOfWeekDoesNotModifyTheGivenDate(): void
    {
        $date = new \DateTime('2026-11-25 09:00');

        TimeSheetWeek::getMondayOfWeek($date);

        self::assertSame('2026-11-25 09:00', $date->format('Y-m-d H:i'));
    }
}
