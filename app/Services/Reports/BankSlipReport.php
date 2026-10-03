<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Bank Slip (CakePHP account_reports/account_bank_slip): the member receipts banked on a slip.
 * Filters are the ones CakePHP builds: bank + date(s) in the AND group, a slip-number range in an
 * OR group of its own (which CakePHP then ANDs with the rest).
 */
class BankSlipReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array{rows: array, bank: ?object} */
    public function run(array $in): array
    {
        $bank = $this->val($in, 'society_bank_id');
        $slip = $this->val($in, 'slip_no');
        $slipTo = $this->val($in, 'slip_no_to');
        $from = $this->val($in, 'payment_date');
        $to = $this->val($in, 'payment_date_to');

        $q = DB::table('member_payments as mp')
            ->leftJoin('members as m', 'm.id', '=', 'mp.member_id')
            ->leftJoin('banks as mb', 'mb.id', '=', 'mp.member_bank_id')
            ->select('mp.*', DB::raw('CAST(mp.amount_paid AS CHAR) as amount_text'), 'm.flat_no', 'mb.bank_name');

        if ($bank !== '') {
            $q->where('mp.society_bank_id', $bank);
        }
        if ($slip !== '' && $slipTo !== '') {
            $q->whereBetween('mp.bank_slip_no', [$slip, $slipTo]);
        } elseif ($slipTo !== '') {
            $q->where('mp.bank_slip_no', $slipTo);
        } elseif ($slip !== '') {
            $q->where('mp.bank_slip_no', $slip);
        }
        if ($from !== '' && $to !== '') {
            $q->whereBetween('mp.payment_date', [$from, $to]);
        } elseif ($from !== '') {
            $q->where('mp.payment_date', $from);
        } elseif ($to !== '') {
            $q->where('mp.payment_date', $to);
        }

        $rows = $q->get()->map(fn ($r) => (array) $r)->all();

        $bankRow = DB::table('society_banks')
            ->where('society_id', $this->societyId)->where('bank_ledger_head_id', $in['society_bank_id'] ?? null)
            ->orderBy('id')->first();

        return ['rows' => $rows, 'bank' => $bankRow];
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
