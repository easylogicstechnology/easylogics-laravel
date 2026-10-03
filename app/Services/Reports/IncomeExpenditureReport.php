<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Income & Expenditure Statement Details (CakePHP account_reports/account_income_exp_statement_details).
 * Also the "surplus of the period" the Balance Sheet takes for its Income & Expenditure head
 * ($isFromBalance = 1 in CakePHP).
 *
 * A line-by-line port; the sums are read as text and added in CakePHP's order.
 */
class IncomeExpenditureReport
{
    public function __construct(private int $societyId)
    {
    }

    /**
     * @return array{totalOpeningExpenses: mixed, totalOpeningIncome: mixed, totalIncome: mixed, totalExpenses: mixed,
     *               expenseHeadsWithSubCat: array, incomHeadsWithSubCat: array, incomeIdex: int, expIndex: int}
     */
    public function run(?string $from, ?string $to): array
    {
        $totalExpenses = 0;
        $totalIncome = 0;
        $totalOpeningExpenses = 0;
        $totalOpeningIncome = 0;
        $incomeWithSub = [];
        $expenseWithSub = [];
        $expIndex = 0;
        $incomeIdex = 0;

        $opening = $this->openingBalances();
        $heads = DB::select(
            'SELECT a.id, a.title AS title, a.opening_amount, b.title AS sub_category, b.id AS sub_category_id, a.account_category_id '
            . 'FROM society_ledger_heads a INNER JOIN society_head_sub_categories b ON a.society_head_sub_category_id = b.id '
            . "WHERE a.society_id = ? AND a.status = '1' AND a.account_category_id IN (3,4) ORDER BY a.id ASC",
            [$this->societyId]
        );

        foreach ($heads as $h) {
            $index = $h->id;
            $op = $opening[$index] ?? null;
            if ($h->account_category_id == 3) {
                $incomeIdex++;
                $row = ['title' => $h->title, 'openingAmount' => $op, 'sub_category' => $h->sub_category, 'id' => $h->id, 'sub_category_id' => $h->sub_category_id];
                $totalIncome += $this->n($op);
                $totalOpeningIncome += $this->n($op);
                $txn = 0;
                $v = $this->sum('member_bill_generates', 'amount', 'bill_generated_date', $from, $to, ['ledger_head_id' => $index]);
                $txn += $this->n($v);
                $totalIncome += $this->n($v);
                $v = $this->sum('society_other_incomes', 'amount_paid', 'payment_date', $from, $to, ['ledger_head_id' => $index]);
                $txn += $this->n($v);
                $totalIncome += $this->n($v);
                $v = $this->sum('journal_vouchers', 'jv_amount_credited', 'voucher_date', $from, $to, ['jv_credit_ledger_head_id' => $index]);
                $txn += $this->n($v);
                $totalIncome += $this->n($v);
                $v = $this->sum('journal_vouchers', 'jv_amount_debited', 'voucher_date', $from, $to, ['jv_debit_ledger_head_id' => $index]);
                $txn -= $this->n($v);
                $totalIncome -= $this->n($v);
                $v = $this->sum('society_payments', 'total_amount', 'payment_date', $from, $to, ['ledger_head_id' => $index]);
                if (!empty($v)) {
                    $txn -= $v;
                    $totalIncome -= $v;
                }
                $title = strtoupper(str_replace(' ', '', $h->title));
                if ($title == 'INTERESTCOLLECTION') {
                    $v = $this->sum('member_bill_summaries', 'interest_on_due_amount', 'bill_generated_date', $from, $to, []);
                    if (!empty($v)) {
                        $txn += $v;
                        $totalIncome += $v;
                    }
                } elseif ($title == 'GSTCOLLECTION') {
                    $v = $this->sum('member_bill_generates', 'tax_total', 'bill_generated_date', $from, $to, []);
                    if (!empty($v)) {
                        $txn += $v;
                        $totalIncome += $v;
                    }
                }
                $row['txnAmount'] = $txn;
                $incomeWithSub[$h->sub_category_id][] = $row;
            } else {
                $expIndex++;
                $row = ['title' => $h->title, 'openingAmount' => $op, 'sub_category' => $h->sub_category, 'id' => $h->id, 'sub_category_id' => $h->sub_category_id];
                $totalExpenses += $this->n($op);
                $totalOpeningExpenses += $this->n($op);
                $txn = 0;
                $v = $this->sum('society_payments', 'total_amount', 'payment_date', $from, $to, ['ledger_head_id' => $index]);
                $txn += $this->n($v);
                $totalExpenses += $this->n($v);
                $v = $this->sum('journal_vouchers', 'jv_amount_debited', 'voucher_date', $from, $to, ['jv_debit_ledger_head_id' => $index]);
                $txn += $this->n($v);
                $totalExpenses += $this->n($v);
                $v = $this->sum('journal_vouchers', 'jv_amount_credited', 'voucher_date', $from, $to, ['jv_credit_ledger_head_id' => $index]);
                $txn -= $this->n($v);
                $totalExpenses -= $this->n($v);
                if (strtolower($h->title) == 'rebate') {
                    $interest = $this->sum('member_bill_summaries', 'interest_adjusted', 'bill_generated_date', $from, $to, []);
                    $tax = $this->sum('member_bill_summaries', 'tax_adjusted', 'bill_generated_date', $from, $to, []);
                    $principal = $this->sum('member_bill_summaries', 'principal_adjusted', 'bill_generated_date', $from, $to, []);
                    $discount = $this->sum('member_bill_summaries', 'discount', 'bill_generated_date', $from, $to, []);
                    $totalRebate = $this->n($discount) + $this->n($interest) + $this->n($tax) + $this->n($principal);
                    if (!empty($totalRebate)) {
                        $txn += $totalRebate;
                        $totalExpenses += $totalRebate;
                    }
                }
                $row['txnAmount'] = $txn;
                $expenseWithSub[$h->sub_category_id][] = $row;
            }
        }

        return [
            'totalOpeningExpenses' => $totalOpeningExpenses, 'totalOpeningIncome' => $totalOpeningIncome,
            'totalIncome' => $totalIncome, 'totalExpenses' => $totalExpenses,
            'expenseHeadsWithSubCat' => $expenseWithSub, 'incomHeadsWithSubCat' => $incomeWithSub,
            'incomeIdex' => $incomeIdex, 'expIndex' => $expIndex,
        ];
    }

    /** the surplus of the period as the Balance Sheet uses it */
    public function surplus(?string $from, ?string $to)
    {
        $r = $this->run($from, $to);

        return ($r['totalIncome'] - $r['totalOpeningIncome']) - ($r['totalExpenses'] - $r['totalOpeningExpenses']);
    }

    private function n($v)
    {
        return is_numeric($v) ? $v + 0 : 0;
    }

    /** sum(column) as text between the dates (>= / <=, like CakePHP), null when nothing matches */
    private function sum(string $table, string $column, string $dateCol, ?string $from, ?string $to, array $where)
    {
        $q = DB::table($table)->where('society_id', $this->societyId);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        $q->where($dateCol, '>=', $from)->where($dateCol, '<=', $to);

        return $q->select(DB::raw("CAST(SUM(`$column`) AS CHAR) as s"))->value('s');
    }

    /** SocietyBill::getSocietyLedgerOpClosingBalSocietyWise (session financial year - 1) */
    private function openingBalances(): array
    {
        $out = [];
        $fy = session('fy.year_id');
        $rows = DB::table('society_ledger_heads_opening_year_wise')
            ->select('ledger_head_id', DB::raw('CAST(balance_amount AS CHAR) as balance_amount'))
            ->where('society_id', $this->societyId)->where('financial_year_id', ((int) $fy) - 1)
            ->whereNotNull('balance_amount')->orderBy('id')->get();
        foreach ($rows as $r) {
            $out[$r->ledger_head_id] = $r->balance_amount;
        }

        return $out;
    }
}
