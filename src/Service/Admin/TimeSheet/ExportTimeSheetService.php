<?php

namespace App\Service\Admin\TimeSheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportTimeSheetService
{
    /**
     * @throws \DateMalformedStringException
     */
    public function buildExcelExport(array $entries, \DateTime $fromDate, \DateTime $toDate): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Détail');

        $headers = ['Utilisateur', 'Date', 'Statut', 'Client', 'Projet', 'Tâche', 'Heures'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');

        $byDay = [];
        $byWeek = [];
        $byUser = [];
        $byClient = [];
        $byProject = [];
        $total = 0;

        $row = 2;
        foreach ($entries as $entry) {
            $week = $entry->getTimeSheetWeek();
            $user = $week->getUser();
            $userName = $user->getFirstName() . ' ' . $user->getLastName();
            $hours = (float) $entry->getHours();
            $clientName = $entry->getClient()?->getName();
            $projectName = $entry->getProject()?->getName();

            $sheet->fromArray([
                $userName,
                $entry->getDate()->format('d/m/Y'),
                $week->getStatus()->label(),
                $clientName,
                $projectName,
                $entry->getTask()?->getName() ?? '',
                $hours,
            ], null, "A{$row}");
            ++$row;

            $dayKey = $entry->getDate()->format('Y-m-d');
            $weekKey = $week->getStartDate()->format('Y-m-d');
            $byDay[$dayKey] = ($byDay[$dayKey] ?? 0) + $hours;
            $byWeek[$weekKey] = ($byWeek[$weekKey] ?? 0) + $hours;
            $byUser[$userName] = ($byUser[$userName] ?? 0) + $hours;
            $byClient[$clientName] = ($byClient[$clientName] ?? 0) + $hours;
            $byProject[$projectName] = ($byProject[$projectName] ?? 0) + $hours;
            $total += $hours;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->addSummarySheet(
            $spreadsheet,
            'Total par jour',
            $byDay,
            fn ($k) => new \DateTime($k)->format('d/m/Y')
        );
        $this->addSummarySheet(
            $spreadsheet,
            'Total par semaine',
            $byWeek,
            fn ($k) => 'Semaine du ' . new \DateTime($k)->format('d/m/Y')
        );
        $this->addSummarySheet($spreadsheet, 'Total par utilisateur', $byUser);
        $this->addSummarySheet($spreadsheet, 'Total par client', $byClient);
        $this->addSummarySheet($spreadsheet, 'Total par projet', $byProject);

        // Résumé de la période, en bas de la feuille Détail
        ++$row;
        $sheet->setCellValue("A{$row}", 'Période');
        $sheet->setCellValue("B{$row}", $fromDate->format('d/m/Y') . ' au ' . $toDate->format('d/m/Y'));
        ++$row;
        $sheet->setCellValue("A{$row}", 'Total général');
        $sheet->setCellValue("B{$row}", $total);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'export_timesheets_' . new \DateTime('now', new \DateTimeZone('Europe/Paris'))->format('Y-m-d_H-i') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function addSummarySheet(Spreadsheet $spreadsheet, string $title, array $data, ?callable $labelFormatter = null): void
    {
        ksort($data);
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray(['Libellé', 'Heures'], null, 'A1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');

        $row = 2;
        $total = 0;
        foreach ($data as $key => $hours) {
            $sheet->setCellValue("A{$row}", $labelFormatter ? $labelFormatter($key) : $key);
            $sheet->setCellValue("B{$row}", $hours);
            $total += $hours;
            ++$row;
        }

        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", $total);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);

        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
