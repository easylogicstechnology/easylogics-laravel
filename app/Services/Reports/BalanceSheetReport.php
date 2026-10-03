<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Balance Sheet (CakePHP account_reports/account_balance_sheet): liability and asset heads with their
 * opening and movement, plus the member dues / advances. Builds the arrays the CakePHP view walks
 * ($societyHeadSubCategory, $accountDuesMemberSummary, $balanceSheetTally ...).
 *
 * The sums are read as text and added in CakePHP's order. Where CakePHP added '' or null the port adds 0.
 */
class BalanceSheetReport
{
    public function __construct(private int $societyId, private ?int $financialYearId, private ?string $yearStart, private ?string $yearEnd)
    {
    }

    /**
     * @param array $post form fields: from_date, to_date, order_assets, order_liabilities
     * @param bool $run true when the form was submitted (CakePHP fills the sheet only then)
     */
    public function run(array $post, bool $run): array
    {
        $societyId = $this->societyId;
        $fy = $this->financialYearId;

        // ---- member dues / advances for the whole year (also worked out before the form is submitted) ----
        $trial = (new TrialBalanceReport($societyId, $fy, $this->yearStart, $this->yearEnd))->forRange($this->yearStart, $this->yearEnd);
        $memberOpening = $trial->memberOpeningBalances();
        $advanceAmount = 0;
        $duesAmount = 0;
        $advanceOpening = 0;
        $duesOpening = 0;
        foreach (DB::table('members')->where('society_id', $societyId)->orderBy('id')->pluck('id') as $memberId) {
            $op = $memberOpening[$memberId] ?? [];
            $openingTemp = $this->n($op['principal_balance'] ?? null) + $this->n($op['interest_balance'] ?? null) + $this->n($op['tax_balance'] ?? null);
            $debit = 0;
            $credit = 0;
            if ($openingTemp < 0) {
                $credit = abs($openingTemp);
                $advanceOpening += $credit;
            } elseif ($openingTemp > 0) {
                $debit = abs($openingTemp);
                $duesOpening += $openingTemp;
            }
            $txn = $trial->memberBalance((int) $memberId);
            $closingAmt = ($debit + $txn['debit']) - ($credit + $txn['credit']);
            if ($closingAmt >= 0) {
                $duesAmount += abs($closingAmt);
            } elseif ($closingAmt < 0) {
                $advanceAmount += $closingAmt;
            }
        }

        // ---- liability / asset heads ----
        $headRows = DB::table('society_ledger_heads as h')
            ->leftJoin('account_categories as c', 'c.id', '=', 'h.account_category_id')
            ->leftJoin('society_head_sub_categories as s', 's.id', '=', 'h.society_head_sub_category_id')
            ->where('h.society_id', $societyId)->where('h.status', 1)->orderBy('h.title')
            ->get(['h.id', 'h.title', 'h.society_head_sub_category_id', 'c.title as category_title', 's.id as sub_id', 's.title as sub_title']);

        $societyHeads = [];
        foreach ($headRows as $h) {
            if (!in_array($h->category_title, ['Liability', 'Asset'])) {
                continue;
            }
            $societyHeads[$h->category_title][$h->sub_id] = $h->sub_title;
        }

        $sub = [];
        $summary = [];
        $totalLiability = 0;
        $totalAsset = 0;
        $tally = null;

        if ($run) {
            $from = !empty($post['from_date']) ? $post['from_date'] : $this->yearStart;
            $to = !empty($post['to_date']) ? $post['to_date'] : $this->yearEnd;
            $incomeExp = new IncomeExpenditureReport($societyId);
            $cashDepositApplied = false;

            foreach ($headRows as $h) {
                $accountCategory = $h->category_title;
                $headCategory = $h->sub_title;
                $id = $h->id;
                if (!in_array($accountCategory, ['Liability', 'Asset'])) {
                    continue;
                }
                $credit = 0;
                $debit = 0;
                $opening = $this->headOpening($id);
                $sub[$accountCategory][$headCategory]['heads'][$id]['title'] = $h->title;
                $sub[$accountCategory][$headCategory]['heads'][$id]['openingAmount'] = $opening;

                $normalized = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $headCategory));
                if (strpos($normalized, 'INCOME') !== false && strpos($normalized, 'EXPENDITURE') !== false) {
                    $surplus = $incomeExp->surplus($from, $to);
                    $jvDr = $this->n($this->sumFy('journal_vouchers', 'jv_amount_debited', 'voucher_date', $from, $to, ['jv_debit_ledger_head_id' => $id]));
                    $jvCr = $this->n($this->sumFy('journal_vouchers', 'jv_amount_credited', 'voucher_date', $from, $to, ['jv_credit_ledger_head_id' => $id]));
                    $sub[$accountCategory][$headCategory]['heads'][$id]['txnAmount'] = $surplus + ($jvCr - $jvDr);
                    continue;
                }

                $subCat = $h->society_head_sub_category_id;
                $v = $this->sumFy('society_payments', 'total_amount', 'payment_date', $from, $to, ['ledger_head_id' => $id]);
                if ($subCat != 21 && $subCat != 20) {
                    $debit += $this->n($v);
                }
                $debit += $this->n($this->sumFy('journal_vouchers', 'jv_amount_debited', 'voucher_date', $from, $to, ['jv_debit_ledger_head_id' => $id]));
                $credit += $this->n($this->sumFy('journal_vouchers', 'jv_amount_credited', 'voucher_date', $from, $to, ['jv_credit_ledger_head_id' => $id]));
                $v = $this->sumFy('member_bill_generates', 'amount', 'bill_generated_date', $from, $to, ['ledger_head_id' => $id]);
                if (!empty($v)) {
                    $credit += $v;
                }
                $v = $this->sumFy('society_other_incomes', 'amount_paid', 'payment_date', $from, $to, ['ledger_head_id' => $id]);
                if ($subCat != 21 && $subCat != 20) {
                    $credit += $this->n($v);
                }

                $v = $this->sumFy('member_payments', 'amount_paid', 'payment_date', $from, $to, ['society_bank_id' => $id]);
                if (!empty($v)) {
                    $debit += $v;
                }
                $paymentIds = DB::table('member_payments')->select('id')
                    ->where('society_id', $societyId)->where('financial_year_id', $fy)->where('society_bank_id', $id)
                    ->where('payment_date', '>=', $from)->where('payment_date', '<=', $to);
                if ((clone $paymentIds)->exists()) {
                    $v = DB::table('cheque_return_details')->whereIn('payment_id', $paymentIds)->where('financial_year_id', $fy)
                        ->select(DB::raw('CAST(SUM(cheque_amount) AS CHAR) as s'))->value('s');
                    if (!empty($v)) {
                        $credit += $v;
                    }
                }

                $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'deposit', 'bank_ledger_head_id' => $id]);
                if (!empty($v)) {
                    $debit += $v;
                }
                $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'contra', 'bank_to_ledger_head_id' => $id]);
                if (!empty($v)) {
                    $debit += $v;
                }
                $v = $this->sumFy('society_other_incomes', 'amount_paid', 'payment_date', $from, $to, ['society_bank_id' => $id]);
                if (!empty($v)) {
                    $debit += $v;
                }
                $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'withdraw', 'bank_ledger_head_id' => $id]);
                if (!empty($v)) {
                    $credit += $v;
                }
                $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'contra', 'bank_ledger_head_id' => $id]);
                if (!empty($v)) {
                    $credit += $v;
                }
                $paid = $this->sumFy('society_payments', 'total_amount', 'payment_date', $from, $to, ['payment_by_ledger_id' => $id]);
                $tds = $this->sumFy('society_payments', 'tax_amount', 'payment_date', $from, $to, ['payment_by_ledger_id' => $id]);
                if (!empty($paid)) {
                    $credit += ($paid - $this->n($tds));
                }
                $v = $this->sumFy('society_payments', 'tax_amount', 'payment_date', $from, $to, ['tds_account_id' => $id]);
                if (!empty($v)) {
                    $credit += $v;
                }

                if ($subCat == 21 && !$cashDepositApplied) {
                    $cashDepositApplied = true;
                    $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'deposit']);
                    if (!empty($v)) {
                        $credit += $v;
                    }
                    $v = $this->sumFy('cash_withdraws', 'amount', 'payment_date', $from, $to, ['txn_type' => 'withdraw']);
                    if (!empty($v)) {
                        $debit += $v;
                    }
                }

                if ($headCategory == 'Cash Balance') {
                    $txnAmount = ($debit - $credit) + $this->n($opening) + $this->n($opening);
                } else {
                    $txnAmount = $credit - $debit;
                }
                $sub[$accountCategory][$headCategory]['heads'][$id]['txnAmount'] = $txnAmount;
            }

            // other income that was never banked: goes to the first cash head, else comes off the first bank head
            $unbanked = DB::table('society_other_incomes')->where('society_id', $societyId)
                ->where('payment_date', '>=', $from)->where('payment_date', '<=', $to)
                ->where(function ($w) {
                    $w->where('society_bank_id', 0)->orWhereNull('society_bank_id');
                })->select(DB::raw('CAST(SUM(amount_paid) AS CHAR) as s'))->value('s');
            if (!empty($unbanked)) {
                $added = false;
                if (isset($sub['Asset']['Cash Balance'])) {
                    foreach ($sub['Asset']['Cash Balance']['heads'] as $cid => $chead) {
                        $sub['Asset']['Cash Balance']['heads'][$cid]['txnAmount'] = $this->n($chead['txnAmount'] ?? null) + $unbanked;
                        $added = true;
                        break;
                    }
                }
                if (!$added && isset($sub['Asset']['Bank Balances'])) {
                    foreach ($sub['Asset']['Bank Balances']['heads'] as $bid => $bhead) {
                        $sub['Asset']['Bank Balances']['heads'][$bid]['txnAmount'] = $this->n($bhead['txnAmount'] ?? null) - $unbanked;
                        $added = true;
                        break;
                    }
                }
            }

            $summary['advance']['opening_amount'] = $advanceOpening;
            $summary['dues']['opening_amount'] = $duesOpening;
            $summary['dues']['txn_amount'] = $duesAmount;
            $summary['advance']['txn_amount'] = $advanceAmount;
        }

        // the order the user picked with "Set Order of Ledger Heads"
        foreach (['Asset' => 'order_assets', 'Liability' => 'order_liabilities'] as $category => $field) {
            if (isset($post[$field]) && $post[$field] != '') {
                $order = array_filter(explode(',', $post[$field]), fn ($value) => $value !== '');
                // (PHP 7: array_merge() with a missing group gave NULL - nothing to list)
                $sub[$category] = isset($sub[$category]) ? array_merge(array_flip($order), $sub[$category]) : null;
            }
        }

        $totalLiability = 0;
        if (!empty($sub['Liability'])) {
            foreach ($sub['Liability'] as $details) {
                foreach ($details['heads'] ?? [] as $head) {
                    $totalLiability += $this->n($head['openingAmount'] ?? null) + $this->n($head['txnAmount'] ?? null);
                }
            }
        }
        if (!empty($summary['advance']['txn_amount'])) {
            $totalLiability += abs($summary['advance']['txn_amount']);
        }
        $totalAsset = 0;
        $cashIds = !empty($sub['Asset']['Cash Balance']['heads']) ? array_keys($sub['Asset']['Cash Balance']['heads']) : [];
        if (!empty($sub['Asset'])) {
            foreach ($sub['Asset'] as $details) {
                foreach ($details['heads'] ?? [] as $headId => $head) {
                    if (in_array($headId, $cashIds)) {
                        $totalAsset += $this->n($head['txnAmount'] ?? null) - $this->n($head['openingAmount'] ?? null);
                    } else {
                        $totalAsset += $this->n($head['openingAmount'] ?? null) - $this->n($head['txnAmount'] ?? null);
                    }
                }
            }
        }
        if (!empty($summary['dues']['txn_amount'])) {
            $totalAsset += $summary['dues']['txn_amount'];
        }
        $tally = [
            'totalAsset' => $totalAsset, 'totalLiability' => $totalLiability,
            'difference' => $totalAsset - $totalLiability, 'tallies' => (round($totalAsset - $totalLiability, 2) == 0),
        ];

        return [
            'societyHeadSubCategory' => $sub, 'societyHeads' => $societyHeads, 'accountDuesMemberSummary' => $summary,
            'totalLiabilityAmount' => $totalLiability, 'totalAssetAmount' => $totalAsset, 'balanceSheetTally' => $tally,
        ];
    }

    private function n($v)
    {
        return is_numeric($v) ? $v + 0 : 0;
    }

    /** SocietyBill::getSocietyLedgerOpClosingBal: last year's closing of the head (latest non-NULL row), 0 when none */
    private function headOpening($headId)
    {
        $v = DB::table('society_ledger_heads_opening_year_wise')
            ->where('ledger_head_id', $headId)->where('society_id', $this->societyId)->where('financial_year_id', ($this->financialYearId ?? 0) - 1)
            ->whereNotNull('balance_amount')->orderByDesc('id')
            ->select(DB::raw('CAST(balance_amount AS CHAR) as b'))->value('b');

        return $v ?? 0;
    }

    /** sum(column) as text for the society and financial year between the dates (>= / <=) with extra conditions; null when nothing matches */
    private function sumFy(string $table, string $column, string $dateCol, $from, $to, array $where)
    {
        $q = DB::table($table)->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        $q->where($dateCol, '>=', $from)->where($dateCol, '<=', $to);

        return $q->select(DB::raw("CAST(SUM(`$column`) AS CHAR) as s"))->value('s');
    }
}
