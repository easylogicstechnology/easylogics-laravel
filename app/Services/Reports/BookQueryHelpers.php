<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Query helpers shared by the book-style reports (Bank Book, Cash Book ...): the society / bank /
 * financial-year / amount / date constraints, the CakePHP empty() reading of form values, and the
 * previous-year closing balance of a ledger head. Expects the using class to hold
 * $societyId and $financialYearId.
 */
trait BookQueryHelpers
{
    /** The society/bank/(amount)/(date) constraints, kept per source table. */
    protected function filters(string $bankId, bool $financialYear): array
    {
        $f = ['bank' => $bankId, 'fy' => $financialYear ? $this->financialYearId : null, 'amount' => null, 'dates' => null];

        return $f;
    }

    protected function withAmount(array $f, string $operator, string $amount): array
    {
        $f['amount'] = [$operator, $amount];

        return $f;
    }

    protected function withDates(array $f, ?array $between = null, ?string $on = null): array
    {
        $f['dates'] = $between !== null ? ['between', $between] : ['on', $on];

        return $f;
    }

    protected function apply($q, array $f, string $alias, string $bankCol, string $amountCol, bool $hasFy = true, string $dateCol = 'payment_date')
    {
        $q->where("$alias.society_id", $this->societyId);
        if ($f['fy'] !== null && $hasFy) {
            $q->where("$alias.financial_year_id", $f['fy']);
        }
        if ($f['bank'] !== '') {
            $q->where("$alias.$bankCol", $f['bank']);
        }
        if ($f['amount'] !== null) {
            $q->where("$alias.$amountCol", $f['amount'][0], $f['amount'][1]);
        }
        if ($f['dates'] !== null) {
            if ($f['dates'][0] === 'between') {
                $q->whereBetween("$alias.$dateCol", $f['dates'][1]);
            } else {
                $q->where("$alias.$dateCol", $f['dates'][1]);
            }
        }

        return $q;
    }

    protected function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        // CakePHP tests these with empty(): '', null and '0' all mean "not given".
        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }

    /** SocietyBill::getSocietyLedgerOpClosingBal: previous year's closing, latest non-NULL row wins. */
    protected function ledgerOpeningBalance(string $bankId): float|int
    {
        if ($this->financialYearId === null) {
            return 0;
        }
        $row = DB::table('society_ledger_heads_opening_year_wise')
            ->where('ledger_head_id', $bankId)
            ->where('society_id', $this->societyId)
            ->where('financial_year_id', $this->financialYearId - 1)
            ->whereNotNull('balance_amount')
            ->orderByDesc('id')
            ->first();

        return $row ? $row->balance_amount : 0;
    }

    /**
     * CakePHP writes a row into $data[date][group][counter] field by field, so two sources landing on the
     * same slot MERGE (the later one overwrites the fields it sets and leaves the rest). Same here.
     */
    protected function put(array &$data, ?string $date, ?string $group, int $counter, array $row): void
    {
        $date ??= ''; $data[$date][$group ?? ''][$counter] = array_merge($data[$date][$group ?? ''][$counter] ?? [], $row);
    }
}
