<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Payment Register (CakePHP account_reports/account_payment_register).
 *
 * Kept as CakePHP builds it: the payments of the society (no financial-year / status filter),
 * "Both" adds an OR group of Cash/Bank, and the date range sits in that same OR group, so it
 * widens the result instead of narrowing it. A lone From date is compared with the (empty) To date.
 */
class PaymentRegisterReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function run(array $in): array
    {
        $type = $this->val($in, 'payment_type');
        $from = $this->val($in, 'payment_date');
        $to = $this->val($in, 'payment_date_to');

        $q = DB::table('society_payments as sp')
            ->leftJoin('society_ledger_heads as lh', 'lh.id', '=', 'sp.ledger_head_id')
            ->select('sp.*', DB::raw('CAST(sp.amount AS CHAR) as amount_text'), 'lh.title as ledger_title')
            ->where('sp.society_id', $this->societyId);

        if ($type !== '' && $type !== 'Both') {
            $q->where('sp.payment_type', $type);
        }

        // Cake: the type=Both alternatives and the BETWEEN share one OR group.
        $or = [];
        if ($type === 'Both') {
            $or[] = ['sp.payment_type', '=', 'Cash'];
            $or[] = ['sp.payment_type', '=', 'Bank'];
        }
        if ($from !== '' && $to !== '') {
            $or[] = ['between', [$from, $to]];
        } elseif ($from !== '') {
            $q->where('sp.payment_date', $to);
        } elseif ($to !== '') {
            $q->where('sp.payment_date', $to);
        }
        if ($or) {
            $q->where(function ($w) use ($or) {
                foreach ($or as $cond) {
                    if ($cond[0] === 'between') {
                        $w->orWhereBetween('sp.payment_date', $cond[1]);
                    } else {
                        $w->orWhere($cond[0], $cond[1], $cond[2]);
                    }
                }
            });
        }

        return $q->get()->map(fn ($r) => (array) $r)->all();
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
