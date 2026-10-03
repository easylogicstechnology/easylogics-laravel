<?php

namespace App\Services\Tds;

use App\Models\TdsAuditLog;
use App\Models\TdsSection;
use Illuminate\Support\Facades\DB;

/**
 * Port of the calculation / helper part of CakePHP TdsController (TdsController::calculateTds,
 * writeAuditLog, activeSectionsList, deducteeVendorList) plus the CakePHP 2.10 model validation
 * rules of the Tds* models. Business logic is a line-by-line port; nothing is "improved".
 */
class TdsService
{
    /** society (== auth user) id and financial year of the session, kept as the strings Cake's session held */
    public function __construct(
        private $societyId,
        private $financialYearId,
        private $userId,
    ) {
    }

    /**
     * TdsController::calculateTds()
     *
     * Server-side authoritative TDS calculation. Threshold policy: "Aggregate in FY" sections check prior
     * non-reversed transactions for the same vendor+section in the same FY; once the aggregate (including
     * the current transaction) crosses the threshold, TDS applies to the current transaction's gross amount
     * only. "Single Transaction" sections check the current transaction's amount alone.
     */
    public function calculateTds($vendorDetailId, $tdsSectionId, $grossAmount, $deductionDate, $excludeTxnId = null): array
    {
        $result = [
            'ok' => false,
            'message' => '',
            'rate_percent' => 0,
            'tds_amount' => 0,
            'net_amount' => $grossAmount,
            'threshold_met' => false,
            'aggregate_so_far' => 0,
            'tds_payable_ledger_head_id' => null,
        ];

        $section = DB::table('tds_sections')
            ->where('id', $tdsSectionId)
            ->where('society_id', $this->societyId)
            ->where('status', 1)
            ->first();
        if (empty($section)) {
            $result['message'] = 'Invalid or inactive TDS Section.';
            return $result;
        }
        $s = (array) $section;
        if (strtotime($deductionDate) < strtotime($s['effective_from']) || (!empty($s['effective_to']) && strtotime($deductionDate) > strtotime($s['effective_to']))) {
            $result['message'] = 'Selected TDS Section is not effective on this date.';
            return $result;
        }

        $result['tds_payable_ledger_head_id'] = $s['tds_payable_ledger_head_id'] === null ? null : (string) $s['tds_payable_ledger_head_id'];

        $ratePercent = floatval($s['rate_percent']);

        // Lower/nil deduction certificate override
        $deductee = DB::table('tds_deductees')->where('vendor_detail_id', $vendorDetailId)->first();
        if (!empty($deductee) && !empty($deductee->lower_deduction_cert_no)) {
            $d = (array) $deductee;
            $validFrom = !empty($d['lower_deduction_valid_from']) ? strtotime($d['lower_deduction_valid_from']) : null;
            $validUpto = !empty($d['lower_deduction_valid_upto']) ? strtotime($d['lower_deduction_valid_upto']) : null;
            $checkTime = strtotime($deductionDate);
            if ((is_null($validFrom) || $checkTime >= $validFrom) && (is_null($validUpto) || $checkTime <= $validUpto)) {
                $ratePercent = floatval($d['lower_deduction_rate']);
            }
        }

        $financialYearId = $this->financialYearId;
        $aggregateSoFar = floatval($grossAmount);
        if ($s['threshold_basis'] == 'Aggregate in FY') {
            $q = DB::table('tds_transactions')
                ->where('society_id', $this->societyId)
                ->where('vendor_detail_id', $vendorDetailId)
                ->where('tds_section_id', $tdsSectionId)
                ->where('financial_year_id', $financialYearId)
                ->where('is_reversed', 0);
            if (!empty($excludeTxnId)) {
                $q->where('id', '!=', $excludeTxnId);
            }
            $priorTotal = $q->selectRaw('SUM(gross_amount) as prior_total')->value('prior_total');
            $priorTotal = isset($priorTotal) ? floatval($priorTotal) : 0;
            $aggregateSoFar = $priorTotal + floatval($grossAmount);
        }
        $result['aggregate_so_far'] = $aggregateSoFar;

        $thresholdMet = $aggregateSoFar >= floatval($s['threshold_limit']);
        $result['threshold_met'] = $thresholdMet;

        $tdsAmount = 0;
        if ($thresholdMet && $ratePercent > 0) {
            $tdsAmount = round((floatval($grossAmount) * $ratePercent) / 100, 2);
        }

        $result['ok'] = true;
        $result['rate_percent'] = $ratePercent;
        $result['tds_amount'] = $tdsAmount;
        $result['net_amount'] = round(floatval($grossAmount) - $tdsAmount, 2);
        return $result;
    }

    /** TdsController::writeAuditLog() */
    public function writeAuditLog($refTable, $refId, $action, $oldData, $newData): void
    {
        TdsAuditLog::create([
            'society_id' => $this->societyId,
            'ref_table' => $refTable,
            'ref_id' => $refId,
            'action' => $action,
            'old_data' => json_encode($oldData),
            'new_data' => json_encode($newData),
            'changed_by' => $this->userId,
            'changed_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /** TdsController::activeSectionsList() - [id => section_code], ordered by section_code */
    public function activeSectionsList()
    {
        return TdsSection::where('society_id', $this->societyId)
            ->where('status', 1)
            ->orderBy('section_code')
            ->pluck('section_code', 'id');
    }

    /** TdsController::deducteeVendorList() - [vendor_detail id => "name (PAN)"] */
    public function deducteeVendorList(): array
    {
        $vendors = DB::table('vendor_details')
            ->where('society_id', $this->societyId)
            ->where('status', 1)
            ->orderBy('contact_person_name')
            ->get(['id', 'contact_person_name', 'pan_no']);
        $list = [];
        foreach ($vendors as $v) {
            $label = $v->contact_person_name;
            if (!empty($v->pan_no)) {
                $label .= ' (' . $v->pan_no . ')';
            }
            $list[$v->id] = $label;
        }
        return $list;
    }

    /** A DB row the way CakePHP handed it to json_encode (PDO emulated prepares = every scalar a string, tinyint(1) = bool). */
    public static function cakeRow($row): ?array
    {
        if ($row === null) {
            return null;
        }
        $out = [];
        foreach ((array) $row as $k => $v) {
            // CakePHP maps tinyint(1) columns to booleans
            $out[$k] = in_array($k, ['status', 'is_reversed', 'reverse_charge', 'reverse_charge_applicable'], true) && $v !== null ? (bool) $v : (is_scalar($v) ? (string) $v : $v);
        }
        return $out;
    }

    /**
     * CakePHP DboSource::value() as create()/update() call it: an empty string written to a NULLABLE non-text
     * column becomes NULL; text columns and NOT NULL columns ({$notNullColumns}) keep '' (MySQL then stores 0 / 0000-00-00).
     */
    public static function cakeWrite(array $data, array $stringColumns, array $notNullColumns = []): array
    {
        foreach ($data as $k => $v) {
            if ($v === '' && !in_array($k, $stringColumns, true) && !in_array($k, $notNullColumns, true)) {
                $data[$k] = null;
            }
        }
        return $data;
    }

    /* ---------------- CakePHP 2.10 Validation rules used by the Tds* models ---------------- */

    public static function notBlank($check): bool
    {
        if (empty($check) && !is_bool($check) && !is_numeric($check)) {
            return false;
        }
        return (bool) preg_match('/[^\s]+/m', (string) $check);
    }

    /** Validation::range($check, $lower, $upper) - lower/upper are EXCLUSIVE in CakePHP 2.10 */
    public static function range($check, $lower, $upper): bool
    {
        if (!is_numeric($check)) {
            return false;
        }
        if ((float) $check != $check) {
            return false;
        }
        return $check > $lower && $check < $upper;
    }

    /** Validation::comparison($check, '>=', $value) */
    public static function comparisonGte($check, $value): bool
    {
        if ((float) $check != $check) {
            return false;
        }
        return $check >= $value;
    }

    /**
     * TdsSection::$validate. Returns [field => message] of the failed fields (Cake only validates fields present in data).
     */
    public static function validateSection(array $d): array
    {
        $e = [];
        if (array_key_exists('section_code', $d) && !self::notBlank($d['section_code'])) {
            $e['section_code'] = 'TDS Section code is required.';
        }
        if (array_key_exists('nature_of_payment', $d) && !self::notBlank($d['nature_of_payment'])) {
            $e['nature_of_payment'] = 'Nature of payment is required.';
        }
        if (array_key_exists('rate_percent', $d) && !self::range($d['rate_percent'], 0, 100)) {
            $e['rate_percent'] = 'Rate must be between 0 and 100.';
        }
        if (array_key_exists('effective_from', $d) && !self::notBlank($d['effective_from'])) {
            $e['effective_from'] = 'Effective From date is required.';
        }
        return $e;
    }

    /** TdsTransaction::$validate */
    public static function validateTransaction(array $d): array
    {
        $e = [];
        if (array_key_exists('tds_section_id', $d) && !self::notBlank($d['tds_section_id'])) {
            $e['tds_section_id'] = 'TDS Section is required.';
        }
        if (array_key_exists('vendor_detail_id', $d) && !self::notBlank($d['vendor_detail_id'])) {
            $e['vendor_detail_id'] = 'Deductee is required.';
        }
        if (array_key_exists('deduction_date', $d) && !self::notBlank($d['deduction_date'])) {
            $e['deduction_date'] = 'Deduction date is required.';
        }
        if (array_key_exists('gross_amount', $d) && !self::comparisonGte($d['gross_amount'], 0)) {
            $e['gross_amount'] = 'Gross amount cannot be negative.';
        }
        if (array_key_exists('pan_no', $d) && !($d['pan_no'] === null || $d['pan_no'] === '') && !preg_match('/^([A-Z]{5}[0-9]{4}[A-Z]{1})?$/', (string) $d['pan_no'])) {
            $e['pan_no'] = 'Invalid PAN format. Expected format: AAAAA9999A';
        }
        return $e;
    }

    /** TdsChallan::$validate */
    public static function validateChallan(array $d): array
    {
        $e = [];
        if (array_key_exists('tan_no', $d) && !preg_match('/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/', (string) $d['tan_no'])) {
            $e['tan_no'] = 'Invalid TAN format. Expected format: AAAA99999A';
        }
        if (array_key_exists('month', $d) && !self::range($d['month'], 1, 12)) {
            $e['month'] = 'Invalid month.';
        }
        return $e;
    }

    /** UtilComponent::getModelValidationError() - the first message of the LAST failed field */
    public static function lastValidationMessage(array $errors): string
    {
        $msg = '';
        foreach ($errors as $m) {
            $msg = $m;
        }
        return $msg;
    }

    /** UtilComponent::getAssesmentYearFromDate() - kept verbatim, including the numeric-string arithmetic */
    public static function assessmentYearFromDate($checkDate): string
    {
        $checkDate = new \DateTime($checkDate);
        $month = $checkDate->format('m');
        $year = $checkDate->format('Y');
        $year2 = $checkDate->format('y');
        $assYear = '';
        if ($month > 3) {
            $newDate = $checkDate->add(new \DateInterval('P2Y'));
            $assYear = ($year + 1) . '-' . ($newDate->format('y'));
        } else {
            $newDate = $checkDate->add(new \DateInterval('P1Y'));
            $assYear = $year . '-' . ($year2 + 1);
        }
        return $assYear;
    }
}
