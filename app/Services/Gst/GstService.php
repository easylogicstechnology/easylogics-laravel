<?php

namespace App\Services\Gst;

use App\Models\GstAuditLog;
use App\Services\Tds\TdsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Port of the helper / calculation part of CakePHP GstController (GST Management & Return Reporting module):
 * writeAuditLog, getGstMaster, fallbackCombinedRate, computeLiabilityBreakdown, nextSequenceNumber, enrichOutwardRows,
 * the register joins, plus the CakePHP 2.10 model validation rules of the Gst* models.
 * Line-by-line port - nothing is improved.
 */
class GstService
{
    public function __construct(
        private $societyId,
        private $financialYearId,
        private $userId,
    ) {
    }

    private const MONTHS = ['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];

    /**
     * member_bill_summaries.month holds the month NUMBER ('4' = April) while the GST screens speak month NAMES
     * ('April'). Every month value the GST module filters on is therefore matched as name AND number.
     * Anything that is neither (e.g. a quarter such as 'Q1', or free text) is matched exactly as typed.
     */
    public static function monthValues($value): array
    {
        $v = trim((string) $value);
        foreach (self::MONTHS as $n => $name) {
            if (strcasecmp($v, $name) === 0 || $v === (string) $n || $v === str_pad((string) $n, 2, '0', STR_PAD_LEFT)) {
                return [(string) $n, str_pad((string) $n, 2, '0', STR_PAD_LEFT), $name];
            }
        }

        return [$value];
    }
    /** GstController::writeAuditLog() */
    public function writeAuditLog($refTable, $refId, $action, $oldData, $newData): void
    {
        GstAuditLog::create([
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

    /** GstController::getGstMaster() */
    public function getGstMaster()
    {
        return DB::table('gst_master')->where('society_id', $this->societyId)->first();
    }

    /**
     * GstController::fallbackCombinedRate() - the society's flat rate config (society_parameters), the same
     * source the bill generator already uses.
     */
    public function fallbackCombinedRate()
    {
        $p = DB::table('society_parameters')->where('society_id', $this->societyId)->first();
        if (empty($p)) {
            return 0;
        }
        $p = (array) $p;
        $cgst = floatval(isset($p['cgst_tax_per']) ? $p['cgst_tax_per'] : 0);
        $sgst = floatval(isset($p['sgst_tax_per']) ? $p['sgst_tax_per'] : 0);
        $igst = floatval(isset($p['igst_tax_per']) ? $p['igst_tax_per'] : 0);
        return $cgst + $sgst + $igst;
    }

    /* ---------------------------------------------------------------- *
     * Register queries (the joins CakePHP's outwardRegisterJoins() / inputRegisterJoins() built)
     * ---------------------------------------------------------------- */

    /**
     * outwardRegisterJoins() + outwardRegisterFields(). member_bill_summaries stores FLOAT(15,2): the amount
     * columns are read as text (CAST ... AS CHAR) so they come back the way CakePHP's emulated prepares gave them.
     */
    public function outwardQuery(): Builder
    {
        return DB::table('member_bill_summaries as m')
            ->join('members as mem', 'm.member_id', '=', 'mem.id')
            ->leftJoin('member_identifications as mi', function ($j) {
                $j->on('m.member_id', '=', 'mi.member_id')->where('mi.status', '=', 1);
            })
            ->leftJoin('gst_outward_supply_meta as o', 'm.id', '=', 'o.member_bill_summary_id')
            ->leftJoin('gst_hsn_sac_master as h', 'o.hsn_sac_id', '=', 'h.id');
    }

    public function outwardSelect(): array
    {
        return [
            'm.id', 'm.bill_no', 'm.bill_generated_date',
            DB::raw('CAST(m.tax_total AS CHAR) AS tax_total'), DB::raw('CAST(m.cgst_total AS CHAR) AS cgst_total'),
            DB::raw('CAST(m.sgst_total AS CHAR) AS sgst_total'), DB::raw('CAST(m.igst_total AS CHAR) AS igst_total'),
            DB::raw('CAST(m.monthly_bill_amount AS CHAR) AS monthly_bill_amount'), 'm.member_id', 'm.flat_no',
            'mem.member_name', 'mem.flat_no as member_flat_no',
            'mi.GSTIN',
            'o.id as meta_id', 'o.hsn_sac_id', 'o.place_of_supply', 'o.supply_type', 'o.reverse_charge',
            'h.id as hsn_id', 'h.code as hsn_code', 'h.gst_rate as hsn_gst_rate',
        ];
    }

    /** enrichOutwardRows(): adds ->computed = [taxable_value, rate, classified, supply_type] to every row */
    public function enrichOutwardRows($rows, $fallbackRate)
    {
        foreach ($rows as $row) {
            $taxTotal = floatval($row->tax_total);
            $hsnRate = !empty($row->hsn_gst_rate) ? floatval($row->hsn_gst_rate) : 0;
            $rate = $hsnRate > 0 ? $hsnRate : $fallbackRate;
            $row->computed = [
                'taxable_value' => $rate > 0 ? round($taxTotal / ($rate / 100), 2) : 0,
                'rate' => $rate,
                'classified' => !empty($row->meta_id),
                'supply_type' => !empty($row->supply_type) ? $row->supply_type : 'B2C',
            ];
        }
        return $rows;
    }

    /** inputRegisterJoins() */
    public function inputQuery(): Builder
    {
        return DB::table('vendor_bill_details as d')
            ->join('vendor_bills as b', 'd.vendor_bill_id', '=', 'b.id')
            ->leftJoin('society_ledger_heads as l', 'b.vendor_ledger_head_id', '=', 'l.id')
            ->leftJoin('vendor_details as v', 'b.vendor_ledger_head_id', '=', 'v.ledger_head_id')
            ->leftJoin('gst_itc_classification as c', 'd.id', '=', 'c.vendor_bill_detail_id');
    }

    public function inputSelect(): array
    {
        return [
            'd.id', 'd.amount', 'd.sgst_amount', 'd.sgst_rate', 'd.cgst_amount', 'd.cgst_rate', 'd.igst_amount', 'd.igst_rate',
            'd.hsn_sac', 'd.vendor_bill_id',
            'b.id as bill_id', 'b.bill_no', 'b.bill_date', 'b.vendor_ledger_head_id',
            'l.title',
            'v.gst_no',
            'c.id as itc_id', 'c.itc_eligibility', 'c.itc_claim_status',
        ];
    }

    /* ---------------------------------------------------------------- *
     * Liability
     * ---------------------------------------------------------------- */

    /**
     * GstController::computeLiabilityBreakdown() - shared by liability(), prepare_payment(), reconciliation() and return_reports().
     */
    public function computeLiabilityBreakdown($societyId, $financialYearId, $periodType, $periodValue): array
    {
        $out = DB::table('member_bill_summaries as m')
            ->where('m.society_id', $societyId)->where('m.financial_year_id', $financialYearId);
        if ($periodType == 'Month') {
            $out->whereIn('m.month', self::monthValues($periodValue));
        }
        $output = $out->selectRaw('ROUND(SUM(m.cgst_total), 2) as cgst, ROUND(SUM(m.sgst_total), 2) as sgst, ROUND(SUM(m.igst_total), 2) as igst')->first();
        $outputCgst = isset($output->cgst) ? floatval($output->cgst) : 0;
        $outputSgst = isset($output->sgst) ? floatval($output->sgst) : 0;
        $outputIgst = isset($output->igst) ? floatval($output->igst) : 0;

        $input = $this->inputQuery()
            ->where('b.society_id', $societyId)->where('b.financial_year_id', $financialYearId)
            ->selectRaw('SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.cgst_amount END) as cgst, '
                . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.sgst_amount END) as sgst, '
                . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.igst_amount END) as igst')
            ->first();
        $eligibleCgst = isset($input->cgst) ? floatval($input->cgst) : 0;
        $eligibleSgst = isset($input->sgst) ? floatval($input->sgst) : 0;
        $eligibleIgst = isset($input->igst) ? floatval($input->igst) : 0;

        $netCgst = $outputCgst - $eligibleCgst;
        $netSgst = $outputSgst - $eligibleSgst;
        $netIgst = $outputIgst - $eligibleIgst;

        $adjustment = DB::table('gst_period_adjustments')
            ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)
            ->where('period_type', $periodType)->where('period_value', $periodValue)
            ->first();
        $interest = !empty($adjustment->interest) ? floatval($adjustment->interest) : 0;
        $lateFee = !empty($adjustment->late_fee) ? floatval($adjustment->late_fee) : 0;
        $otherAdjustment = !empty($adjustment->other_adjustment) ? floatval($adjustment->other_adjustment) : 0;
        $previousPeriodAdjustment = !empty($adjustment->previous_period_adjustment) ? floatval($adjustment->previous_period_adjustment) : 0;

        $netGstPayable = $netCgst + $netSgst + $netIgst + $interest + $lateFee + $otherAdjustment + $previousPeriodAdjustment;

        return compact('outputCgst', 'outputSgst', 'outputIgst', 'eligibleCgst', 'eligibleSgst', 'eligibleIgst', 'netCgst', 'netSgst', 'netIgst', 'interest', 'lateFee', 'otherAdjustment', 'previousPeriodAdjustment', 'netGstPayable', 'adjustment');
    }

    /** nextSequenceNumber(): MAX(number)+1 per society + financial year (+ extra conditions) */
    public function nextSequenceNumber(string $table, string $numberField, $financialYearId, array $extra = []): int
    {
        $q = DB::table($table)->where('society_id', $this->societyId)->where('financial_year_id', $financialYearId);
        foreach ($extra as $k => $v) {
            $q->where($k, $v);
        }
        $max = $q->selectRaw('MAX(' . $numberField . ') as max_no')->value('max_no');

        return !empty($max) ? intval($max) + 1 : 1;
    }

    /* ---------------------------------------------------------------- *
     * CakePHP 2.10 model validation rules of the Gst* models
     * ---------------------------------------------------------------- */

    /** GstMaster::$validate */
    public static function validateGstMaster(array $d): array
    {
        $e = [];
        if (array_key_exists('gstin', $d) && !($d['gstin'] === null || $d['gstin'] === '')
            && !preg_match('/^([0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1})?$/', (string) $d['gstin'])) {
            $e['gstin'] = 'Invalid GSTIN format. Expected format: 22AAAAA0000A1Z5';
        }
        return $e;
    }

    /** GstHsnSacMaster::$validate */
    public static function validateHsn(array $d): array
    {
        $e = [];
        if (array_key_exists('code', $d) && !TdsService::notBlank($d['code'])) {
            $e['code'] = 'HSN/SAC code is required.';
        }
        if (array_key_exists('description', $d) && !TdsService::notBlank($d['description'])) {
            $e['description'] = 'Description is required.';
        }
        if (array_key_exists('gst_rate', $d) && !TdsService::range($d['gst_rate'], 0, 100)) {
            $e['gst_rate'] = 'Rate must be between 0 and 100.';
        }
        if (array_key_exists('effective_from', $d) && !TdsService::notBlank($d['effective_from'])) {
            $e['effective_from'] = 'Effective From date is required.';
        }
        return $e;
    }

    /** GstCreditDebitNote::$validate */
    public static function validateNote(array $d): array
    {
        $e = [];
        if (array_key_exists('note_date', $d) && !TdsService::notBlank($d['note_date'])) {
            $e['note_date'] = 'Note date is required.';
        }
        if (array_key_exists('taxable_amount', $d) && !TdsService::comparisonGte($d['taxable_amount'], 0)) {
            $e['taxable_amount'] = 'Taxable amount cannot be negative.';
        }
        return $e;
    }
}
