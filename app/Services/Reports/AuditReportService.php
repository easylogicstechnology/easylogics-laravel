<?php

namespace App\Services\Reports;

use App\Support\AuditReportData;
use Illuminate\Support\Facades\DB;

/**
 * Statutory Audit Report (CakePHP audit_reports/edit and /pdf): loading and saving the auditor's report of a
 * financial year, the approximate financial snapshot taken from the ledger opening balances, and the PDF.
 */
class AuditReportService
{
    /** JSON columns that hold one section's question => answer map (form field name = column name) */
    public const JSON_FIELDS = ['part1_answers', 'nine_point_remarks', 'part_a_answers', 'part_c_compliance', 'schedule_remarks', 'form28_answers', 'part_b_extra'];

    /** plain columns the edit form saves */
    public const FORM_FIELDS = [
        'auditor_name', 'auditor_qualification', 'auditor_panel_no', 'auditor_address', 'registrar_address', 'audit_memo_sr_no',
        'audit_start_date', 'audit_end_date', 'audit_report_date', 'report_place', 'inspection_class', 'audit_grade',
        'previous_auditor_name', 'previous_audit_report_date', 'audit_fee_amount', 'audit_fee_period', 'tds_amount', 'other_charges_amount',
        'general_remarks', 'law_violation_notes',
    ];

    private const DATE_FIELDS = ['audit_start_date', 'audit_end_date', 'audit_report_date', 'previous_audit_report_date'];
    private const NUMBER_FIELDS = ['audit_fee_amount', 'tds_amount', 'other_charges_amount'];

    public function __construct(private int $societyId)
    {
    }

    public function report($financialYearId): ?array
    {
        $row = DB::table('society_audit_reports')->where('society_id', $this->societyId)->where('financial_year_id', $financialYearId)->first();

        return $row ? (array) $row : null;
    }

    public function reportById($id): ?array
    {
        $row = DB::table('society_audit_reports')->where('id', $id)->where('society_id', $this->societyId)->first();

        return $row ? (array) $row : null;
    }

    /** the JSON columns decoded, keyed by the view variable name CakePHP used (part1Answers, ninePointRemarks ...) */
    public function answers(?array $report): array
    {
        $out = [];
        foreach (self::JSON_FIELDS as $field) {
            $var = lcfirst(str_replace('_', '', ucwords($field, '_')));
            $out[$var] = empty($report[$field]) ? [] : (json_decode($report[$field], true) ?: []);
        }

        return $out;
    }

    /** the static question lists the views walk */
    public function questions(): array
    {
        return [
            'part1Questions' => AuditReportData::part1(), 'ninePoints' => AuditReportData::ninePoints(),
            'financialFields' => AuditReportData::financialSummaryFields(), 'partAQuestions' => AuditReportData::partA(),
            'partCQuestions' => AuditReportData::partC(), 'scheduleQuestions' => AuditReportData::statutorySchedules(),
            'form28Questions' => AuditReportData::formNo28PartII(), 'partBFields' => AuditReportData::partBFields(),
        ];
    }

    public function memberCount(): int
    {
        return DB::table('members')->where('society_id', $this->societyId)->where('status', 1)->count();
    }

    /** Saves the form (draft, or finalized) and returns the report id. */
    public function save(?array $existing, $financialYearId, array $input, bool $finalize, ?int $userId): int
    {
        $data = [];
        foreach (self::FORM_FIELDS as $f) {
            $v = $input[$f] ?? null;
            if (in_array($f, self::DATE_FIELDS)) {
                $v = ($v === null || $v === '') ? null : $v;
            } elseif (in_array($f, self::NUMBER_FIELDS)) {
                $v = ($v === null || $v === '') ? null : $v;
            } elseif ($v === '') {
                $v = null;
            }
            $data[$f] = $v;
        }
        $data['society_id'] = $this->societyId;
        $data['financial_year_id'] = $financialYearId;
        $data['status'] = $finalize ? 'finalized' : 'draft';
        $data['udate'] = date('Y-m-d H:i:s');
        $data['financial_snapshot'] = json_encode($this->snapshot());
        foreach (self::JSON_FIELDS as $f) {
            if (isset($input[$f]) && is_array($input[$f])) {
                $data[$f] = json_encode($input[$f], JSON_UNESCAPED_UNICODE);
            }
        }

        if ($existing) {
            DB::table('society_audit_reports')->where('id', $existing['id'])->update($data);

            return (int) $existing['id'];
        }
        $data['created_by'] = $userId;
        $data['cdate'] = date('Y-m-d H:i:s');

        return (int) DB::table('society_audit_reports')->insertGetId($data);
    }

    /**
     * Approximate closing-balance snapshot from the ledger heads' opening amounts, grouped by the account heads a
     * housing society's chart of accounts always has (best-effort keyword split of the fund / cash / bank heads).
     */
    public function snapshot(): array
    {
        $totals = [
            'share_capital' => 0, 'reserve_fund' => 0, 'sinking_fund' => 0, 'repair_fund' => 0, 'education_fund' => 0,
            'other_liabilities' => 0, 'cash_in_hand' => 0, 'bank_balance' => 0, 'investments' => 0, 'fixed_assets' => 0,
        ];
        $heads = DB::table('society_ledger_heads')->where('society_id', $this->societyId)
            ->get(['title', 'account_head_id', DB::raw('CAST(opening_amount AS CHAR) as opening_amount')]);
        foreach ($heads as $head) {
            $title = strtolower((string) $head->title);
            $amount = floatval($head->opening_amount);
            switch (intval($head->account_head_id)) {
                case 1:
                    $totals['share_capital'] += $amount;
                    break;
                case 2:
                    if (strpos($title, 'sink') !== false) {
                        $totals['sinking_fund'] += $amount;
                    } elseif (strpos($title, 'repair') !== false) {
                        $totals['repair_fund'] += $amount;
                    } elseif (strpos($title, 'educat') !== false || strpos($title, 'train') !== false) {
                        $totals['education_fund'] += $amount;
                    } else {
                        $totals['reserve_fund'] += $amount;
                    }
                    break;
                case 4:
                    $totals['other_liabilities'] += $amount;
                    break;
                case 6:
                    $totals['fixed_assets'] += $amount;
                    break;
                case 7:
                    $totals['investments'] += $amount;
                    break;
                case 8:
                    if (strpos($title, 'cash') !== false) {
                        $totals['cash_in_hand'] += $amount;
                    } else {
                        $totals['bank_balance'] += $amount;
                    }
                    break;
            }
        }

        return $totals;
    }

    /**
     * HTML -> PDF with wkhtmltopdf (dompdf cannot shape Devanagari). The binary comes from the WKHTMLTOPDF_BINARY
     * setting (config/audit.php), else "wkhtmltopdf" on the PATH.
     */
    public function renderPdf(string $html): string
    {
        $binary = config('audit.wkhtmltopdf', 'wkhtmltopdf');
        $dir = storage_path('app/audit_reports');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $htmlFile = $dir . DIRECTORY_SEPARATOR . uniqid('audit_') . '.html';
        $pdfFile = $dir . DIRECTORY_SEPARATOR . uniqid('audit_') . '.pdf';
        file_put_contents($htmlFile, $html);
        exec(escapeshellarg($binary) . ' --quiet ' . escapeshellarg($htmlFile) . ' ' . escapeshellarg($pdfFile) . ' 2>&1', $out, $code);
        $pdf = file_exists($pdfFile) ? file_get_contents($pdfFile) : false;
        @unlink($htmlFile);
        @unlink($pdfFile);
        if ($pdf === false) {
            throw new \RuntimeException('PDF generation failed (wkhtmltopdf binary: ' . $binary . '): ' . implode("\n", $out));
        }

        return $pdf;
    }
}
