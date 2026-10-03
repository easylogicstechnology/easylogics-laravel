<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Trial Balance (CakePHP account_reports/account_trial_balance): opening, transactions and closing of every
 * ledger head of the society plus one line per member under "Advances & Dues From Members".
 *
 * A line-by-line port. Every figure CakePHP adds up is read as text (CAST .. AS CHAR) and added in the same
 * order, one query per head and table as CakePHP does, so the totals come out to the same cent.
 * In PHP 7 an empty '' behaved as 0 in these sums and comparisons; here it is simply 0.
 */
class TrialBalanceReport
{
    /** per-table date column */
    private const DATE_COL = [
        'member_payments' => 'payment_date', 'society_payments' => 'payment_date', 'cash_withdraws' => 'payment_date',
        'society_other_incomes' => 'payment_date', 'journal_vouchers' => 'voucher_date',
        'member_bill_summaries' => 'bill_generated_date', 'member_bill_generates' => 'bill_generated_date',
    ];

    private ?string $from = null;
    private ?string $to = null;
    private array $opening = [];

    public function __construct(private int $societyId, private ?int $financialYearId, private ?string $yearStart, private ?string $yearEnd)
    {
    }

    /**
     * @param array $post TrialBalance form fields (payment_date, payment_date_to)
     * @return array{heads: array, subCats: array, bankIds: array, cashIds: array, post: array}
     */
    public function run(array $post): array
    {
        $post['payment_date'] = $post['payment_date'] ?? '';
        $post['payment_date_to'] = $post['payment_date_to'] ?? '';
        if (empty($post['payment_date']) && empty($post['payment_date_to'])) {
            $post['payment_date'] = $this->yearStart;
            $post['payment_date_to'] = $this->yearEnd;
        }
        $this->from = !empty($post['payment_date']) ? $post['payment_date'] : null;
        $this->to = !empty($post['payment_date_to']) ? $post['payment_date_to'] : null;

        $bankIds = [];
        foreach (DB::table('society_banks')->where('society_id', $this->societyId)->pluck('bank_ledger_head_id') as $id) {
            if (!empty($id)) {
                $bankIds[(int) $id] = true;
            }
        }
        $cashIds = [];
        $cashRows = DB::table('society_ledger_heads as h')
            ->leftJoin('society_head_sub_categories as c', 'c.id', '=', 'h.society_head_sub_category_id')
            ->where('h.society_id', $this->societyId)->where('h.status', 1)
            ->where(function ($w) {
                $w->where('h.title', 'LIKE', '%cash%')->orWhere('c.title', 'LIKE', '%cash%');
            })->pluck('h.id');
        foreach ($cashRows as $id) {
            if (!empty($id)) {
                $cashIds[(int) $id] = true;
            }
        }

        $this->opening = $this->headOpeningBalances();

        $accountHeadTypes = DB::table('account_heads')->where('status', 1)->orderBy('id')->pluck('transaction_type', 'id')->all();
        $subCats = [];
        foreach (DB::table('society_head_sub_categories')->where('status', 1)->orderBy('id')->get(['id', 'title', 'account_head_id', 'account_category_id']) as $s) {
            $subCats[$s->id] = ['title' => $s->title, 'type' => $accountHeadTypes[$s->account_head_id] ?? null, 'category_id' => $s->account_category_id];
        }

        $heads = DB::table('society_ledger_heads')
            ->where('status', 1)->where('society_id', $this->societyId)
            ->orderBy('title')->orderBy('id')
            ->get(['title', 'id', 'society_head_sub_category_id', 'opening_amount', 'is_rebate_applicable']);

        // the interest and GST collection heads
        $interestId = $gstId = $interestFallback = $gstFallback = null;
        foreach ($heads as $h) {
            if (!isset($subCats[$h->society_head_sub_category_id])) {
                continue;
            }
            $tl = strtolower(trim($h->title));
            if ($tl == 'interest collection') {
                $interestId = $h->id;
            } elseif ($interestFallback === null && strpos($tl, 'interest') !== false
                && strpos($tl, 'fdr') === false && strpos($tl, 'f.d') === false && strpos($tl, 'accrued') === false
                && strpos($tl, 'income tax') === false && strpos($tl, 'refund') === false && strpos($tl, 'received') === false) {
                $interestFallback = $h->id;
            }
            if ($tl == 'gst collection') {
                $gstId = $h->id;
            } elseif ($gstFallback === null && strpos($tl, 'gst') !== false && strpos($tl, 'accrued') === false) {
                $gstFallback = $h->id;
            }
        }
        $interestId ??= $interestFallback;
        $gstId ??= $gstFallback;

        $trial = [];
        $cashDepositApplied = false;
        foreach ($heads as $h) {
            if (!array_key_exists($h->society_head_sub_category_id, $subCats)) {
                continue;
            }
            $id = $h->id;
            $subCat = $h->society_head_sub_category_id;
            $details = ['id' => $id, 'title' => $h->title];

            $debit = '';
            $credit = '';
            $type = trim((string) $subCats[$subCat]['type']);
            $categoryId = $subCats[$subCat]['category_id'];
            if (!in_array($categoryId, [3, 4])) {
                if ($type == 'DEBIT') {
                    $debit = $this->opening[$id] ?? 0;
                } elseif ($type == 'CREDIT') {
                    $credit = $this->opening[$id] ?? 0;
                }
            }

            $txnDebit = $this->sum('society_payments', 'total_amount', ['ledger_head_id' => $id]);
            $txnCredit = $this->sum('society_other_incomes', 'amount_paid', ['ledger_head_id' => $id]);
            if ($subCat == 20 || $subCat == 21) {
                $txnDebit = 0;
                $txnCredit = 0;
            }

            $v = $this->sum('journal_vouchers', 'jv_amount_debited', ['jv_debit_ledger_head_id' => $id]);
            if (!empty($v)) {
                $txnDebit += $v;
            }
            $v = $this->sum('journal_vouchers', 'jv_amount_credited', ['jv_credit_ledger_head_id' => $id]);
            if (!empty($v)) {
                $txnCredit += $v;
            }
            $v = $this->sum('member_bill_generates', 'amount', ['ledger_head_id' => $id]);
            if (!empty($v)) {
                $txnCredit += $v;
            }

            $v = $this->sum('member_payments', 'amount_paid', ['society_bank_id' => $id]);
            if (!empty($v)) {
                $txnDebit += $v;
            }
            if ($this->exists('member_payments', ['society_bank_id' => $id])) {
                $cheque = $this->chequeReturnForBank($id);
                if (!empty($cheque)) {
                    $txnCredit += $cheque;
                }
            }

            $v = $this->sum('cash_withdraws', 'amount', ['bank_ledger_head_id' => $id, 'txn_type' => 'deposit']);
            if (!empty($v)) {
                $txnDebit += $v;
            }
            $v = $this->sum('cash_withdraws', 'amount', ['bank_to_ledger_head_id' => $id, 'txn_type' => 'contra']);
            if (!empty($v)) {
                $txnDebit += $v;
            }

            // (the bank / cash other-income sums only matter for bank (20) and cash (21) heads)
            $bankOther = $subCat == 20 ? $this->sum('society_other_incomes', 'amount_paid', ['society_bank_id' => $id]) : null;
            $bankOtherTds = $subCat == 20 ? $this->sum('society_other_incomes', 'tds_amount', ['society_bank_id' => $id], [['tds_bank_id', '>', 0]]) : null;
            $tdsToBank = $this->sum('society_other_incomes', 'tds_amount', ['tds_bank_id' => $id]);
            if (!empty($tdsToBank)) {
                $txnDebit += $tdsToBank;
            }
            if (!empty($bankOther)) {
                if ($subCat == 20) {
                    $tdsToSubtract = !empty($bankOtherTds) ? $bankOtherTds : 0;
                    $txnDebit += ($bankOther - $tdsToSubtract);
                }
            }

            $cashOther = $subCat == 21 ? $this->sum('society_other_incomes', 'amount_paid', ['payment_mode' => 'Cash']) : null;
            $cashOtherTds = $subCat == 21 ? $this->sum('society_other_incomes', 'tds_amount', ['payment_mode' => 'Cash'], [['tds_bank_id', '>', 0]]) : null;
            if (!empty($cashOther)) {
                if ($subCat == 21) {
                    $tdsToSubtractCash = !empty($cashOtherTds) ? $cashOtherTds : 0;
                    $txnDebit += ($cashOther - $tdsToSubtractCash);
                }
            }

            $v = $this->sum('cash_withdraws', 'amount', ['bank_ledger_head_id' => $id, 'txn_type' => 'withdraw']);
            if (!empty($v)) {
                $txnCredit += $v;
            }
            $v = $this->sum('cash_withdraws', 'amount', ['bank_ledger_head_id' => $id, 'txn_type' => 'contra']);
            if (!empty($v)) {
                $txnCredit += $v;
            }

            $bankPayments = $this->sum('society_payments', 'total_amount', ['payment_by_ledger_id' => $id]);
            $bankPaymentsTds = $this->sum('society_payments', 'tax_amount', ['payment_by_ledger_id' => $id], [['tds_account_id', '>', 0]]);
            $tdsAmount = $this->sum('society_payments', 'tax_amount', ['tds_account_id' => $id]);
            if (!empty($tdsAmount)) {
                $txnCredit += $tdsAmount;
            }
            if (!empty($bankPayments)) {
                $tdsToSubtract = !empty($bankPaymentsTds) ? $bankPaymentsTds : 0;
                $txnCredit += ($bankPayments - $tdsToSubtract);
            }

            if ($subCat == 21 && !$cashDepositApplied) {
                $cashDepositApplied = true;
                $v = $this->sum('cash_withdraws', 'amount', ['txn_type' => 'withdraw']);
                if (!empty($v)) {
                    $txnDebit += $v;
                }
                $v = $this->sum('cash_withdraws', 'amount', ['txn_type' => 'deposit']);
                if (!empty($v)) {
                    $txnCredit += $v;
                }
            }

            if ($interestId !== null && $id == $interestId) {
                $v = $this->sum('member_bill_summaries', 'interest_on_due_amount', []);
                if (!empty($v)) {
                    $txnCredit += $v;
                }
            }
            if ($gstId !== null && $id == $gstId) {
                $v = $this->sum('member_bill_generates', 'tax_total', []);
                if (!empty($v)) {
                    $txnCredit += $v;
                }
            }
            if ($h->is_rebate_applicable == 1) {
                $interest = $this->sum('member_bill_summaries', 'interest_adjusted', []);
                $tax = $this->sum('member_bill_summaries', 'tax_adjusted', []);
                $principal = $this->sum('member_bill_summaries', 'principal_adjusted', []);
                $discount = $this->sum('member_bill_summaries', 'discount', []);
                $totalRebate = $discount + $interest + $tax + $principal;
                if (!empty($totalRebate)) {
                    $txnDebit += abs($totalRebate);
                }
            }

            $closingAmt = ($this->n($credit) + $this->n($txnCredit)) - ($this->n($debit) + $this->n($txnDebit));
            if ($closingAmt > 0) {
                $closing = ['debit' => '', 'credit' => abs($closingAmt)];
            } elseif ($closingAmt < 0) {
                $closing = ['debit' => abs($closingAmt), 'credit' => ''];
            } else {
                $closing = ['debit' => '', 'credit' => ''];
            }
            $trial[$subCat]['ledgers'][] = [
                'details' => $details,
                'opening' => ['debit' => $debit, 'credit' => $credit],
                'transactions' => ['debit' => $txnDebit, 'credit' => $txnCredit],
                'closing' => $closing,
            ];
        }

        // one line per member under "Advances & Dues From Members"
        $memberOpening = $this->memberOpeningBalances();
        unset($subCats[22]);
        $subCats[7]['title'] = 'Advances & Dues From Members';
        foreach (DB::table('members')->where('society_id', $this->societyId)->orderBy('id')->get(['id', 'member_prefix', 'member_name', 'flat_no']) as $m) {
            $name = $m->member_prefix . ' ' . $m->member_name . ' (' . $m->flat_no . ')';
            $op = $memberOpening[$m->id] ?? [];
            $openingTemp = ($op['principal_balance'] ?? 0) + ($op['interest_balance'] ?? 0) + ($op['tax_balance'] ?? 0);
            $debit = 0;
            $credit = 0;
            if ($openingTemp < 0) {
                $credit = abs($openingTemp);
            } elseif ($openingTemp > 0) {
                $debit = abs($openingTemp);
            }
            $opening = ['debit' => $debit, 'credit' => $credit];
            $txn = $this->memberBalance($m->id);
            $closingAmt = ($opening['debit'] + $txn['debit']) - ($opening['credit'] + $txn['credit']);
            if ($closingAmt >= 0) {
                $closing = ['credit' => '', 'debit' => abs($closingAmt)];
            } else {
                $closing = ['credit' => abs($closingAmt), 'debit' => ''];
            }
            $trial[7]['ledgers'][] = ['details' => ['id' => $m->id, 'title' => $name], 'opening' => $opening, 'transactions' => $txn, 'closing' => $closing];
        }

        return ['heads' => $trial, 'subCats' => $subCats, 'bankIds' => $bankIds, 'cashIds' => $cashIds, 'post' => $post];
    }

    /** a value as PHP 7 read it in a sum: '' and null are 0 */
    private function n($v)
    {
        return is_numeric($v) ? $v + 0 : 0;
    }

    /** SocietyBill::getSocietyLedgerOpClosingBalSocietyWise: last year's closing per ledger head (latest non-NULL row) */
    private function headOpeningBalances(): array
    {
        $out = [];
        $rows = DB::table('society_ledger_heads_opening_year_wise')
            ->select('ledger_head_id', DB::raw('CAST(balance_amount AS CHAR) as balance_amount'))
            ->where('society_id', $this->societyId)->where('financial_year_id', ($this->financialYearId ?? 0) - 1)
            ->whereNotNull('balance_amount')->orderBy('id')->get();
        foreach ($rows as $r) {
            $out[$r->ledger_head_id] = $r->balance_amount;
        }

        return $out;
    }

    /** SocietyBill::getMemberOpBalanceSocietyWise('reg'): last year's member closing, per member */
    public function memberOpeningBalances(): array
    {
        $out = [];
        $rows = DB::table('member_year_wise_closing_balance')
            ->select('member_id', DB::raw('CAST(principal_balance AS CHAR) as p'), DB::raw('CAST(interest_balance AS CHAR) as i'), DB::raw('CAST(tax_balance AS CHAR) as t'))
            ->where('society_id', $this->societyId)->where('year_id', ($this->financialYearId ?? 0) - 1)->where('bill_type', 'reg')->get();
        foreach ($rows as $r) {
            $out[$r->member_id] = ['principal_balance' => $r->p, 'interest_balance' => $r->i, 'tax_balance' => $r->t];
        }

        return $out;
    }

    /** the society + date range conditions CakePHP puts on every table it sums */
    private function base(string $table, ?int $memberId = null)
    {
        $q = DB::table($table)->where('society_id', $this->societyId);
        $col = self::DATE_COL[$table];
        if ($this->from && $this->to) {
            $q->whereBetween($col, [$this->from, $this->to]);
        } elseif ($this->from) {
            $q->where($col, $this->from);
        } elseif ($this->to) {
            $q->where($col, $this->to);
        }

        return $q;
    }

    /** sum(column) of a table under the society + date conditions and the extra conditions; the text of the total, null when no rows */
    private function sum(string $table, string $column, array $where, array $ops = [])
    {
        $q = $this->base($table)->select(DB::raw("CAST(SUM(`$column`) AS CHAR) as s"));
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        foreach ($ops as [$k, $op, $v]) {
            $q->where($k, $op, $v);
        }

        return $q->value('s');
    }

    private function exists(string $table, array $where): bool
    {
        $q = $this->base($table);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }

        return $q->exists();
    }

    /** cheque returns of the payments banked to this head in the period */
    private function chequeReturnForBank(int $bankId)
    {
        $sub = DB::table('member_payments')->select('id')->where('society_bank_id', $bankId)->where('society_id', $this->societyId);
        if ($this->from && $this->to) {
            $sub->whereBetween('payment_date', [$this->from, $this->to]);
        } elseif ($this->from) {
            $sub->where('payment_date', $this->from);
        } elseif ($this->to) {
            $sub->where('payment_date', $this->to);
        }

        return DB::table('cheque_return_details')->whereIn('payment_id', $sub)->select(DB::raw('CAST(SUM(cheque_amount) AS CHAR) as s'))->value('s');
    }

    /** Sets the date range the member balances are worked out for (both ends, like the Balance Sheet does). */
    public function forRange(?string $from, ?string $to): self
    {
        $this->from = $from ?: null;
        $this->to = $to ?: null;

        return $this;
    }

    /** AccountReportsController::getMemberBalance: what the member was billed (debit) and paid (credit) in the period */
    public function memberBalance(int $memberId): array
    {
        $debit = 0;
        $credit = 0;
        $v = $this->sum('member_bill_generates', 'amount', ['member_id' => $memberId]);
        if (!empty($v)) {
            $debit += $v;
        }
        $v = $this->sum('member_bill_summaries', 'interest_on_due_amount', ['member_id' => $memberId]);
        if (!empty($v)) {
            $debit += $v;
        }
        $v = $this->sum('journal_vouchers', 'jv_amount_debited', ['jv_debit_member_head_id' => $memberId]);
        if (!empty($v)) {
            $debit += $v;
        }
        $v = $this->sum('member_payments', 'amount_paid', ['member_id' => $memberId]);
        if (!empty($v)) {
            $credit += $v;
        }
        $v = $this->sum('journal_vouchers', 'jv_amount_credited', ['jv_credit_member_head_id' => $memberId]);
        if (!empty($v)) {
            $credit += $v;
        }
        $debit = $debit + $this->memberChequeReturn($memberId);

        return ['debit' => $debit, 'credit' => $credit];
    }

    /** getMemberWiseChequeReturnData: cheque returns of the member this year, payments dated in the period (only when both ends are given) */
    private function memberChequeReturn(int $memberId)
    {
        $q = DB::table('cheque_return_details as c')
            ->join('member_payments as p', 'p.id', '=', 'c.payment_id')
            ->where('c.society_id', $this->societyId)->where('c.member_id', $memberId)->where('c.financial_year_id', $this->financialYearId);
        if (!empty($this->from) && !empty($this->to)) {
            $q->whereBetween('p.payment_date', [$this->from, $this->to]);
        }
        $v = $q->select(DB::raw('CAST(SUM(c.cheque_amount) AS CHAR) as s'))->value('s');

        return $v ?? 0;
    }
}
