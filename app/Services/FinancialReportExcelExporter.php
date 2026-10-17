<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Services;

use App\Enums\ChartTotalReportTypeEnum;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportExcelExporter
{
    /**
     * Export a fully configured report to Excel.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to display.
     * @param  string  $periodHeading  The period heading text.
     * @param  string  $filename  The output filename.
     * @return StreamedResponse The streamed Excel download response.
     */
    public function configured(
        array $report,
        string $periodLabel,
        string $periodHeading,
        string $filename,
    ): StreamedResponse {
        return $this->export($report, $periodLabel, $periodHeading, $filename);
    }

    /**
     * Export the configured Profit & Loss rows.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to display.
     * @return StreamedResponse The streamed Excel download response.
     */
    public function profitLoss(array $report, string $periodLabel): StreamedResponse
    {
        return $this->export(
            $report,
            $periodLabel,
            __('financial_reports.labels.period'),
            'profit_loss_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /**
     * Export the configured Balance Sheet rows.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to display.
     * @return StreamedResponse The streamed Excel download response.
     */
    public function balanceSheet(array $report, string $periodLabel): StreamedResponse
    {
        return $this->export(
            $report,
            $periodLabel,
            __('financial_reports.labels.as_of'),
            'balance_sheet_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /**
     * Export the Trial Balance opening, period, and closing columns.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to display.
     * @return StreamedResponse The streamed Excel download response.
     */
    public function trialBalance(array $report, string $periodLabel): StreamedResponse
    {
        $reportName = is_string($report['reportName'] ?? null) ? (string) $report['reportName'] : '';
        $spreadsheet = $this->newSpreadsheet($reportName);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', mb_strtoupper($reportName));
        $sheet->setCellValue('A2', __('financial_reports.labels.period'));
        $sheet->setCellValue('B2', $periodLabel);
        $sheet->mergeCells('A1:H1');

        $headers = [
            __('financial_reports.table.column_account_code'),
            __('financial_reports.table.column_account'),
            __('financial_reports.table.opening_debit'),
            __('financial_reports.table.opening_credit'),
            __('financial_reports.table.period_debit'),
            __('financial_reports.table.period_credit'),
            __('financial_reports.table.closing_debit'),
            __('financial_reports.table.closing_credit'),
        ];

        $sheet->fromArray($headers, null, 'A4');

        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        $rowNumber = 4;
        /** @var array<int, array<string, mixed>> $rows */
        $rows = $report['rows'] ?? [];
        foreach ($rows as $row) {
            $rowNumber++;
            $code = $row['code'] ?? '';
            $name = $row['name'] ?? '';
            $sheet->setCellValueExplicit('A'.$rowNumber, is_string($code) ? $code : '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$rowNumber, is_string($name) ? $name : '', DataType::TYPE_STRING);
            $sheet->fromArray([
                $row['opening_debit'] ?? 0,
                $row['opening_credit'] ?? 0,
                $row['period_debit'] ?? 0,
                $row['period_credit'] ?? 0,
                $row['closing_debit'] ?? 0,
                $row['closing_credit'] ?? 0,
            ], null, 'C'.$rowNumber);
        }

        $rowNumber++;
        $sheet->setCellValue('A'.$rowNumber, __('financial_reports.table.total'));
        $sheet->mergeCells('A'.$rowNumber.':B'.$rowNumber);
        /** @var array<string, float> $totals */
        $totals = $report['totals'] ?? [];
        $sheet->fromArray(array_values($totals), null, 'C'.$rowNumber);
        $sheet->getStyle('A'.$rowNumber.':H'.$rowNumber)->getFont()->setBold(true);
        $sheet->getStyle('A'.$rowNumber.':H'.$rowNumber)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);

        $sheet->getStyle('C5:H'.$rowNumber)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        $sheet->getStyle('C5:H'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(38);
        foreach (range('C', 'H') as $column) {
            $sheet->getColumnDimension($column)->setWidth(18);
        }
        $sheet->freezePane('A5');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(16);

        return $this->download(
            $spreadsheet,
            'trial_balance_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /**
     * Render the common configured report row format into an Excel workbook.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to display.
     * @param  string  $periodHeading  The period heading text.
     * @param  string  $filename  The output filename.
     * @return StreamedResponse The streamed Excel download response.
     */
    private function export(
        array $report,
        string $periodLabel,
        string $periodHeading,
        string $filename
    ): StreamedResponse {
        $reportName = is_string($report['reportName'] ?? null) ? (string) $report['reportName'] : '';
        $amountHeadings = $this->amountHeadings($report, $periodLabel, __('financial_reports.table.column_amount'));
        $lastColumn = Coordinate::stringFromColumnIndex(2 + count($amountHeadings));
        $spreadsheet = $this->newSpreadsheet($reportName);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', mb_strtoupper($reportName));
        $sheet->setCellValue('A2', $periodHeading);
        $sheet->setCellValue('B2', $periodLabel);
        $sheet->mergeCells('A1:'.$lastColumn.'1');

        $rowNumber = 4;
        $this->writeHeaders($sheet, $rowNumber, $amountHeadings);

        /** @var array<int, array<string, mixed>> $reportRows */
        $reportRows = $report['rows'] ?? [];
        foreach ($reportRows as $reportRow) {
            $rowNumber++;
            $this->writeReportRow($sheet, $rowNumber, $reportRow, $lastColumn);
        }

        /** @var array<int, string> $unknownAccounts */
        $unknownAccounts = $report['unknownLedgerAccounts'] ?? [];
        if ($unknownAccounts !== []) {
            $rowNumber += 3;
            $sheet->setCellValue('A'.$rowNumber, __('financial_reports.messages.unmatched_accounts'));
            $sheet->mergeCells('A'.$rowNumber.':'.$lastColumn.$rowNumber);
            $this->styleSectionRow($sheet, $rowNumber, $lastColumn);

            foreach ($unknownAccounts as $reference) {
                $rowNumber++;
                $sheet->setCellValueExplicit('A'.$rowNumber, $reference, DataType::TYPE_STRING);
                $sheet->mergeCells('A'.$rowNumber.':'.$lastColumn.$rowNumber);
            }
        }

        $this->finishSheet($sheet, $lastColumn);

        return $this->download($spreadsheet, $filename);
    }

    private function newSpreadsheet(string $sheetTitle): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $appName = config('app.name');
        $creator = is_string($appName) ? $appName : '';
        $spreadsheet->getProperties()
            ->setCreator($creator)
            ->setTitle($sheetTitle);

        $spreadsheet->getActiveSheet()->setTitle(mb_substr($sheetTitle, 0, 31));

        return $spreadsheet;
    }

    /**
     * Write a single report row to the worksheet.
     *
     * @param  Worksheet  $sheet  The worksheet to write to.
     * @param  int  $rowNumber  The row number to write.
     * @param  array<string, mixed>  $reportRow  The report row data.
     * @param  string  $lastColumn  The last column letter for merging.
     * @return void Returns nothing after writing the row to the worksheet.
     */
    private function writeReportRow(
        Worksheet $sheet,
        int $rowNumber,
        array $reportRow,
        string $lastColumn = 'C',
    ): void {
        $hasComparison = array_key_exists('comparison_amount', $reportRow);
        $periodAmounts = $reportRow['period_amounts'] ?? null;
        /** @var array<int, mixed> $amounts */
        $amounts = is_array($periodAmounts)
            ? $periodAmounts
            : [$reportRow['amount'] ?? null];
        if ($hasComparison) {
            $amounts[] = $reportRow['comparison_amount'];
        }

        $kind = $reportRow['kind'] ?? '';

        if ($kind === 'heading' || $kind === 'group_heading') {
            $sheet->setCellValue('A'.$rowNumber, $reportRow['name'] ?? '');
            $sheet->mergeCells('A'.$rowNumber.':'.$lastColumn.$rowNumber);
            $this->styleSectionRow($sheet, $rowNumber, $lastColumn);

            return;
        }

        if ($kind === 'note' || $kind === 'empty') {
            $sheet->setCellValue('A'.$rowNumber, $reportRow['name'] ?? '');
            $sheet->mergeCells('A'.$rowNumber.':'.$lastColumn.$rowNumber);

            return;
        }

        if ($kind === 'formula') {
            $sheet->setCellValue('A'.$rowNumber, $reportRow['name'] ?? '');
            $sheet->mergeCells('A'.$rowNumber.':B'.$rowNumber);
            foreach ($amounts as $index => $amount) {
                $sheet->setCellValue([$index + 3, $rowNumber], $amount ?? 0);
            }

            $totalType = $reportRow['total_type'] ?? '';
            if ($totalType === ChartTotalReportTypeEnum::GRAND_TOTAL->value) {
                $this->styleGrandTotalRow($sheet, $rowNumber, $lastColumn);
            } else {
                $this->styleTotalRow($sheet, $rowNumber, $lastColumn);
            }

            return;
        }

        $code = $reportRow['code'] ?? '';
        $name = $reportRow['name'] ?? '';
        $sheet->setCellValueExplicit('A'.$rowNumber, is_string($code) ? $code : '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B'.$rowNumber, is_string($name) ? $name : '', DataType::TYPE_STRING);
        foreach ($amounts as $index => $amount) {
            $sheet->setCellValue([$index + 3, $rowNumber], $amount ?? 0);
        }
    }

    /**
     * Write the column headers to the worksheet.
     *
     * @param  Worksheet  $sheet  The worksheet to write to.
     * @param  int  $rowNumber  The row number to write headers at.
     * @param  array<int, string>  $amountHeadings  The amount column heading labels.
     * @return void Returns nothing after writing the headers to the worksheet.
     */
    private function writeHeaders(Worksheet $sheet, int $rowNumber, array $amountHeadings): void
    {
        $sheet->setCellValue('A'.$rowNumber, __('financial_reports.table.column_account_code'));
        $sheet->setCellValue('B'.$rowNumber, __('financial_reports.table.column_account'));
        foreach ($amountHeadings as $index => $heading) {
            $sheet->setCellValue([$index + 3, $rowNumber], $heading);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(2 + count($amountHeadings));
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()->setBold(true);
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function styleSectionRow(Worksheet $sheet, int $rowNumber, string $lastColumn = 'C'): void
    {
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()
            ->setBold(true)
            ->setSize(11);

        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function styleTotalRow(Worksheet $sheet, int $rowNumber, string $lastColumn = 'C'): void
    {
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()->setBold(true);
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getBorders()
            ->getTop()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    private function styleGrandTotalRow(Worksheet $sheet, int $rowNumber, string $lastColumn = 'C'): void
    {
        $this->styleTotalRow($sheet, $rowNumber, $lastColumn);
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()->setSize(11);
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);
    }

    private function finishSheet(Worksheet $sheet, string $lastColumn = 'C'): void
    {
        $sheet->getStyle('C1:'.$lastColumn.$sheet->getHighestRow())
            ->getNumberFormat()
            ->setFormatCode('#,##0.00;[Red]-#,##0.00');

        $sheet->getStyle('C1:'.$lastColumn.$sheet->getHighestRow())
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(42);
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        for ($column = 3; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(16);
        }

        $sheet->freezePane('A5');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $sheet->getStyle('A1:'.$lastColumn.'1')->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('A1:'.$lastColumn.$sheet->getHighestRow())
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * Determine the amount column headings based on report configuration.
     *
     * @param  array<string, mixed>  $report  The configured report data.
     * @param  string  $periodLabel  The period label to use as fallback heading.
     * @param  string  $defaultHeading  The default heading if no comparison or period columns exist.
     * @return array<int, string>
     */
    private function amountHeadings(array $report, string $periodLabel, string $defaultHeading): array
    {
        if (isset($report['periodColumns']) && is_array($report['periodColumns'])) {
            /** @var array<int, string> $periodColumns */
            $periodColumns = $report['periodColumns'];

            return $periodColumns;
        }

        if (isset($report['comparisonLabel'])) {
            $comparisonLabel = is_string($report['comparisonLabel']) ? $report['comparisonLabel'] : '';

            return [$periodLabel, $comparisonLabel];
        }

        return [$defaultHeading];
    }

    private function download(
        Spreadsheet $spreadsheet,
        string $filename
    ): StreamedResponse {
        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }
}
