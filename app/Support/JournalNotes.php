<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Debit Note / Credit Note support on journal_vouchers (CakePHP JournalVoucher::hasNoteColumns() /
 * hasPayableColumn() / jvOnlyConditions() and SocietyBillsController::jvNoManualSql() /
 * getManualNoteDeltasByBillNo() / manualNoteDeltasForBill()).
 *
 * Notes live in the same table as Journal Vouchers, split by entry_type ('JV' vs 'DN' / 'CN'). A row with a
 * manual_component (TAX / INTEREST / PRINCIPAL) is a MANUAL note row: the bill engine leaves it out of the
 * automatic Dr / Cr readers and applies it to its pinned bill instead (manualDeltasByBillNo).
 * Until the database has the note columns, every helper answers "nothing special", so an un-migrated database
 * is read exactly as before.
 */
class JournalNotes
{
    /** @var array<string, bool> per database + column, asked of the database once per process */
    private static array $known = [];

    private static function hasColumn(string $column): bool
    {
        $key = DB::connection()->getDatabaseName() . '/' . $column;

        if (!isset(self::$known[$key])) {
            self::$known[$key] = Schema::hasColumn('journal_vouchers', $column);
        }

        return self::$known[$key];
    }

    /** Has the database been migrated for Debit Note / Credit Note (debit_credit_notes_migration.sql)? */
    public static function hasNoteColumns(): bool
    {
        return self::hasColumn('manual_component');
    }

    /** Has the adjust_payable column (debit_credit_notes_adjust_payable.sql) been added? */
    public static function hasPayableColumn(): bool
    {
        return self::hasColumn('adjust_payable');
    }

    /** Forget what was learned about the schema (tests that migrate a scratch database). */
    public static function forget(): void
    {
        self::$known = [];
    }

    /** Leave MANUAL note rows out of a journal_vouchers query (CakePHP jvNoManualSql) */
    public static function excludeManual($query)
    {
        if (self::hasNoteColumns()) {
            $query->whereNull('manual_component');
        }

        return $query;
    }

    /** Keep a journal_vouchers query to ordinary Journal Vouchers (CakePHP jvOnlyConditions) */
    public static function jvOnly($query)
    {
        if (self::hasNoteColumns()) {
            $query->where('entry_type', 'JV');
        }

        return $query;
    }

    /**
     * Manual Debit/Credit Note adjustments of one member, per pinned bill number: a Debit row adds to the chosen
     * component, a Credit row takes from it. The rows are the source of truth, so every rebuild of the bills
     * re-derives this.
     *
     * @return array<int, array{TAX: float, INTEREST: float, PRINCIPAL: float, PAYABLE: float}>
     */
    public static function manualDeltasByBillNo($memberId, $societyId, $memberTransfer, $financialYearId): array
    {
        if (!self::hasNoteColumns()) {
            return [];
        }

        $columns = ['manual_component', 'manual_bill_no', 'jv_type', 'jv_amount_debited', 'jv_amount_credited'];
        if (self::hasPayableColumn()) {
            $columns[] = 'adjust_payable';
        }

        $rows = DB::table('journal_vouchers')
            ->where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->where('member_transfer', $memberTransfer)
            ->whereNotNull('manual_component')
            ->where(function ($q) use ($memberId) {
                $q->where('jv_debit_member_head_id', $memberId)->orWhere('jv_credit_member_head_id', $memberId);
            })
            ->get($columns);

        $deltas = [];
        foreach ($rows as $jv) {
            $billNo = $jv->manual_bill_no;
            $deltas[$billNo] ??= ['TAX' => 0, 'INTEREST' => 0, 'PRINCIPAL' => 0, 'PAYABLE' => 0];

            $amount = ($jv->jv_type === 'Debit') ? (float) $jv->jv_amount_debited : -(float) $jv->jv_amount_credited;
            $deltas[$billNo][$jv->manual_component] += $amount;

            // Only notes saved with "also adjust Amount Payable" move the bill's amount_payable; by default a
            // note changes the balance only.
            if (!empty($jv->adjust_payable)) {
                $deltas[$billNo]['PAYABLE'] += $amount;
            }
        }

        return $deltas;
    }

    /**
     * The transient keys a bill array carries for the balance formulas (CakePHP _dn_tax / _dn_interest /
     * _dn_principal / _dn_payable). They are not table columns, so saving a bill row ignores them.
     */
    public static function billKeys(array $deltasByBillNo, $billType, $billNo): array
    {
        $d = ($billType == 'reg' && isset($deltasByBillNo[$billNo])) ? $deltasByBillNo[$billNo] : null;

        return [
            '_dn_tax' => $d['TAX'] ?? 0,
            '_dn_interest' => $d['INTEREST'] ?? 0,
            '_dn_principal' => $d['PRINCIPAL'] ?? 0,
            '_dn_payable' => $d['PAYABLE'] ?? 0,
        ];
    }
}
