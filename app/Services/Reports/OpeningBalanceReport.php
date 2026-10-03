<?php

namespace App\Services\Reports;

use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * Opening Balance / Members' Closing Balance (CakePHP account_reports/bill_opening_balance): each member's
 * principal / interest / tax balance on their last bill, less the receipts and journal-voucher credits
 * that came after that bill (tax first, then interest, then principal).
 * One query per member, as CakePHP does. Returns the member rows with the three balances.
 */
class OpeningBalanceReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array<int, array{Member: array}> */
    public function run(): array
    {
        $members = DB::table('members')->where('society_id', $this->societyId)->orderBy('id')
            ->get(['id', 'member_name', 'member_prefix', 'flat_no', 'member_phone', DB::raw('CAST(area AS CHAR) as area')])
            ->map(fn ($r) => ['Member' => (array) $r])->all();

        foreach ($members as $k => $m) {
            $id = $m['Member']['id'];
            // CakePHP's second $options['AND'] replaces the first: the last bill of the member, whatever the society
            $summary = DB::table('member_bill_summaries')
                ->select('member_id', 'bill_generated_date', DB::raw('CAST(principal_balance AS CHAR) as principal_balance'), DB::raw('CAST(interest_balance AS CHAR) as interest_balance'),
                    DB::raw('CAST(tax_balance AS CHAR) as tax_balance'), DB::raw('CAST(balance_amount AS CHAR) as balance_amount'))
                ->where('member_id', $id)->orderByDesc('bill_generated_date')->first();

            $bal = ['principal_balance' => 0.00, 'interest_balance' => 0.00, 'tax_balance' => 0.00];
            if ($summary && isset($summary->balance_amount)) {
                foreach (['principal_balance', 'interest_balance', 'tax_balance'] as $f) {
                    $bal[$f] = number_format((float) $summary->$f, 2, '.', '');
                }
            }

            $billDate = $summary->bill_generated_date ?? null;
            $payments = $billDate === null ? collect() : DB::table('member_payments')
                ->where('society_id', $this->societyId)->where('member_id', $id)->where('payment_date', '>', $billDate)
                ->select(DB::raw('CAST(amount_paid AS CHAR) as amount'))->get();
            foreach ($payments as $p) {
                $this->apply($bal, $p->amount);
            }

            $jvCond = DB::table('journal_vouchers')->where('society_id', $this->societyId)->where('jv_credit_member_head_id', $id)
                ->where('cdate', '>', $billDate . ' 00:00:00');
            foreach ($jvCond->select(DB::raw('CAST(jv_amount_credited AS CHAR) as amount'))->get() as $j) {
                $this->apply($bal, $j->amount);
            }

            $members[$k]['Member'] += $bal;
        }

        return $members;
    }

    /** a receipt is taken off the tax, then the interest, then the principal balance */
    private function apply(array &$bal, $amount): void
    {
        foreach (['tax_balance', 'interest_balance', 'principal_balance'] as $f) {
            if ($amount > 0) {
                if ($amount >= $bal[$f]) {
                    $amount = $amount - $bal[$f];
                    $bal[$f] = 0.00;
                } else {
                    $bal[$f] = $bal[$f] - $amount;
                    $amount = 0;
                }
            }
        }
    }
}
