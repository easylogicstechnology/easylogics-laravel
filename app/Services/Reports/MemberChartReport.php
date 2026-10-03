<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Member Chart (CakePHP account_reports/member_chart): one line per member with the opening balance, the
 * year's charges per tariff head, interest, GST, total billed, received and the closing balance.
 */
class MemberChartReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** @return array{memberChartData: array, uniqueLedgerHeads: array} */
    public function run(array $in): array
    {
        $billType = $in['bill_type'] ?? 'reg';

        $q = DB::table('members')->where('society_id', $this->societyId)->where('status', 1)->orderBy('flat_no');
        if ($this->val($in, 'building_id') !== '') {
            $q->where('building_id', $this->val($in, 'building_id'));
        }
        if ($this->val($in, 'wing_id') !== '') {
            $q->where('wing_id', $this->val($in, 'wing_id'));
        }
        $from = $this->val($in, 'flat_no');
        $to = $this->val($in, 'flat_no_to');
        if ($from !== '' && $to !== '') {
            $q->whereBetween('flat_no', [$from, $to]);
        } elseif ($to !== '') {
            $q->where('flat_no', $to);
        } elseif ($from !== '') {
            $q->where('flat_no', $from);
        }
        $members = $q->get(['id', 'flat_no', 'member_name', DB::raw('CAST(op_principal AS CHAR) as op_principal'), DB::raw('CAST(op_interest AS CHAR) as op_interest'), DB::raw('CAST(op_tax AS CHAR) as op_tax')]);

        $closing = [];
        $previous = ($this->financialYearId ?? 0) - 1;
        if ($previous > 0) {
            $rows = DB::table('member_year_wise_closing_balance')
                ->select('member_id', DB::raw('CAST(principal_balance AS CHAR) as p'), DB::raw('CAST(interest_balance AS CHAR) as i'), DB::raw('CAST(tax_balance AS CHAR) as t'))
                ->where('society_id', $this->societyId)->where('year_id', $previous)->where('bill_type', $billType)->get();
            foreach ($rows as $r) {
                $closing[$r->member_id] = $r;
            }
        }

        $dateFrom = $this->val($in, 'from_date');
        $dateTo = $this->val($in, 'to_date');
        $ranged = $dateFrom !== '' && $dateTo !== '';

        $heads = DB::table('society_ledger_heads')->where('society_id', $this->societyId)->where('is_in_bill_charges', 1)->orderBy('id')->pluck('title', 'id')->all();

        $data = [];
        foreach ($members as $m) {
            $id = $m->id;
            if (isset($closing[$id])) {
                $opening = floatval($closing[$id]->p) + floatval($closing[$id]->i) + floatval($closing[$id]->t);
            } else {
                $opening = floatval($m->op_principal) + floatval($m->op_interest) + floatval($m->op_tax);
            }

            $bq = DB::table('member_bill_summaries')
                ->select('bill_no', DB::raw('CAST(interest_on_due_amount AS CHAR) as interest_on_due_amount'), DB::raw('CAST(tax_total AS CHAR) as tax_total'))
                ->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)->where('bill_type', $billType)->where('member_id', $id);
            if ($ranged) {
                $bq->where('bill_generated_date', '>=', $dateFrom)->where('bill_generated_date', '<=', $dateTo);
            }
            $tariffTotals = [];
            $totalBilled = 0;
            $interestTotal = 0;
            $gstTotal = 0;
            foreach ($bq->orderBy('bill_no')->get() as $bill) {
                $interestTotal += floatval($bill->interest_on_due_amount);
                $gstTotal += floatval($bill->tax_total);
                $tariffs = DB::table('member_bill_generates')
                    ->select('ledger_head_id', DB::raw('CAST(amount AS CHAR) as amount'))
                    ->where('society_id', $this->societyId)->where('member_id', $id)->where('bill_number', $bill->bill_no)->where('bill_type', $billType)->get();
                foreach ($tariffs as $t) {
                    $amt = floatval($t->amount);
                    if (!isset($tariffTotals[$t->ledger_head_id])) {
                        $tariffTotals[$t->ledger_head_id] = 0;
                    }
                    $tariffTotals[$t->ledger_head_id] += $amt;
                    $totalBilled += $amt;
                }
            }
            $totalBilled += $interestTotal + $gstTotal;

            $pq = DB::table('member_payments')->where('society_id', $this->societyId)->where('member_id', $id);
            if ($ranged) {
                $pq->where('payment_date', '>=', $dateFrom)->where('payment_date', '<=', $dateTo);
            }
            $paid = $pq->select(DB::raw('CAST(SUM(amount_paid) AS CHAR) as s'))->value('s');
            $received = !empty($paid) ? floatval($paid) : 0;

            $data[$id] = [
                'flat_no' => $m->flat_no, 'member_name' => $m->member_name, 'opening_balance' => $opening, 'tariffs' => $tariffTotals,
                'interest' => $interestTotal, 'gst' => $gstTotal, 'total_billed' => $totalBilled, 'received' => $received,
                'closing_balance' => $opening + $totalBilled - $received,
            ];
        }

        return ['memberChartData' => $data, 'uniqueLedgerHeads' => $heads];
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
