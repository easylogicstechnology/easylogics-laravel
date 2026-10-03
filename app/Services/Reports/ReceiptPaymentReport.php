<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Receipt & Payment (CakePHP account_reports/account_receipt_payment): receipts (opening cash and bank
 * balances, member contribution, general receipts) against payments and the closing cash / bank balances.
 * Builds the arrays the CakePHP view walks. Sums are read as text and added in CakePHP's order.
 */
class ReceiptPaymentReport
{
    public function __construct(private int $societyId, private ?int $financialYearId, private ?string $yearStart, private ?string $yearEnd)
    {
    }

    /**
     * @param array $bankHeads [id => title] (society bank ledger heads)
     * @param array $cashHeads [id => title] (society cash ledger heads)
     */
    public function run(array $post, array $bankHeads, array $cashHeads): array
    {
        $post['payment_date'] = $post['payment_date'] ?? '';
        $post['payment_date_to'] = $post['payment_date_to'] ?? '';
        $fromDate = empty($post['payment_date']) ? $this->yearStart : $post['payment_date'];
        if (empty($post['payment_date'])) {
            $post['payment_date'] = $this->yearStart;
        }
        $toDate = empty($post['payment_date_to']) ? $this->yearEnd : $post['payment_date_to'];
        if (empty($post['payment_date_to'])) {
            $post['payment_date_to'] = $this->yearEnd;
        }

        $headOpening = DB::table('society_ledger_heads')->where('society_id', $this->societyId)
            ->select('id', DB::raw('CAST(opening_amount AS CHAR) as opening_amount'))->pluck('opening_amount', 'id')->all();
        $firstDay = $this->firstDayFinancialYear();

        $receipts = [];
        $cashLedgerText = '';
        $add = function (array &$arr, string $k1, string $k2, $v) {
            $arr[$k1][$k2] = ($arr[$k1][$k2] ?? null) + (is_numeric($v) ? $v + 0 : 0);
        };

        foreach ($cashHeads as $id => $title) {
            if (!empty($fromDate) && $fromDate != $firstDay) {
                $headOpening[$id] = $this->headOpening($id);
            }
            $add($receipts, 'CashBankBalances', $title, $headOpening[$id] ?? null);
            $cashLedgerText = $title;
            $add($receipts, 'MemberContribution', 'Maintenance Charges', $this->sumPayments('member_payments', 'amount_paid', ['society_bank_id' => $id], 'payment_date', $fromDate, $toDate));
        }
        foreach ($bankHeads as $id => $title) {
            if (!empty($fromDate) && $fromDate != $firstDay) {
                $headOpening[$id] = $this->headOpening($id);
            }
            $add($receipts, 'CashBankBalances', $title, $headOpening[$id] ?? null);
            $add($receipts, 'MemberContribution', 'Maintenance Charges', $this->sumPayments('member_payments', 'amount_paid', ['society_bank_id' => $id], 'payment_date', $fromDate, $toDate));
        }

        // general receipts, grouped by ledger head (the ledger head runs are added up)
        $oq = DB::table('society_other_incomes as o')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'o.ledger_head_id')
            ->select('o.ledger_head_id', 'o.payment_mode', DB::raw('CAST(o.amount_paid AS CHAR) as amount_paid'), 'h.title as head_title', 'h.society_head_sub_category_id')
            ->where('o.society_id', $this->societyId)->where('o.status', 1);
        if (!empty($fromDate) && !empty($toDate)) {
            $oq->whereBetween('o.payment_date', [$fromDate, $toDate]);
        }
        $others = $oq->orderByDesc('o.ledger_head_id')->get();
        $subTitles = DB::table('society_head_sub_categories')->where('status', 1)->orderBy('id')->pluck('title', 'id')->all();
        $receiptHeads = [];
        if (count($others)) {
            $sum = 0;
            foreach ($others as $key => $o) {
                $subId = $o->society_head_sub_category_id;
                $next = isset($others[$key + 1]) ? $others[$key + 1]->ledger_head_id : '';
                $sum = $sum + $o->amount_paid;
                if ($next != $o->ledger_head_id) {
                    $receiptHeads[$subId][] = [
                        'ledger_head_id' => $o->ledger_head_id, 'amount_paid' => $sum, 'title' => $o->head_title, 'mode' => $o->payment_mode, 'sub_head' => $subTitles[$subId] ?? '',
                    ];
                    $sum = 0;
                }
            }
        }

        $cashBalance = $this->allCashBalance($receipts, $cashLedgerText, $cashHeads, $fromDate, $toDate);
        $bankClosing = [];
        foreach ($bankHeads as $bankId => $bankName) {
            $openingAmt = $receipts['CashBankBalances'][$bankName] ?? null;
            $bankClosing[$bankName] = ($openingAmt + $this->bankClosingBalance($bankId, $fromDate, $toDate));
        }

        $payments = [];
        $pq = DB::table('society_payments as p')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'p.ledger_head_id')
            ->select('h.title as head_title', DB::raw('CAST(p.total_amount AS CHAR) as total_amount'))->where('p.society_id', $this->societyId);
        if (!empty($fromDate) && !empty($toDate)) {
            $pq->whereBetween('p.payment_date', [$fromDate, $toDate]);
        }
        foreach ($pq->get() as $p) {
            $add($payments, 'Payments', (string) $p->head_title, $p->total_amount);
        }

        return [
            'receipts' => $receipts, 'payments' => $payments, 'receiptHeads' => $receiptHeads,
            'BalanceData' => ['cash_balance' => $cashBalance], 'bankClosingData' => $bankClosing, 'post' => $post,
        ];
    }

    /** AccountReportsController::getFirstDayFinancialYear (from today's date) */
    private function firstDayFinancialYear(): string
    {
        $mon = date('m');
        $y = $mon > 4 ? date('Y') : date('Y', strtotime('-1 year'));

        return $y . '-04-01';
    }

    private function headOpening($headId)
    {
        return DB::table('society_ledger_heads_opening_year_wise')
            ->where('ledger_head_id', $headId)->where('society_id', $this->societyId)->where('financial_year_id', ($this->financialYearId ?? 0) - 1)
            ->whereNotNull('balance_amount')->orderByDesc('id')
            ->select(DB::raw('CAST(balance_amount AS CHAR) as b'))->value('b');
    }

    /** sum(column) as text for the society between the dates (both ends given) with extra conditions */
    private function sumPayments(string $table, string $column, array $where, string $dateCol, $from, $to)
    {
        $q = DB::table($table)->where('society_id', $this->societyId);
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }
        if (!empty($from) && !empty($to)) {
            $q->whereBetween($dateCol, [$from, $to]);
        }

        return $q->select(DB::raw("CAST(SUM(`$column`) AS CHAR) as s"))->value('s');
    }

    private function n($v)
    {
        return is_numeric($v) ? $v + 0 : 0;
    }

    /** getAllCashBalance: opening + cash receipts + withdrawals - cash payments - cash deposited */
    private function allCashBalance(array $receipts, string $cashLedgerText, array $cashHeads, $from, $to)
    {
        $range = !empty($from) && !empty($to);
        $totalAvailableCash = 0;
        $deductFromTotal = 0;

        $mp = $this->sumPayments('member_payments', 'amount_paid', ['payment_mode' => 1], 'payment_date', $from, $to);
        $totalAvailableCash = $totalAvailableCash + $this->n($mp);
        $sp = $this->sumPayments('society_payments', 'amount', ['payment_type' => 'cash'], 'payment_date', $from, $to);
        $deductFromTotal = $deductFromTotal + $this->n($sp);

        $cw = DB::table('cash_withdraws')->where('society_id', $this->societyId)->select('txn_type', DB::raw('CAST(amount AS CHAR) as amount'));
        if ($range) {
            $cw->whereBetween('payment_date', [$from, $to]);
        }
        foreach ($cw->orderBy('payment_date')->get() as $c) {
            if ($c->txn_type == 'deposit') {
                $deductFromTotal = $deductFromTotal + $this->n($c->amount);
            }
            if ($c->txn_type == 'withdraw') {
                $totalAvailableCash = $totalAvailableCash + $this->n($c->amount);
            }
        }

        $openingBal = 0;
        if (!empty($receipts)) {
            $openingBal = $receipts['CashBankBalances'][$cashLedgerText] ?? null;
        }
        $oi = $this->sumPayments('society_other_incomes', 'amount_paid', ['payment_mode' => 'cash'], 'payment_date', $from, $to);
        $totalAvailableCash = $totalAvailableCash + $this->n($oi);

        return ($this->n($openingBal) + $totalAvailableCash) - $deductFromTotal;
    }

    /** getSocietyAllBankCosingBalance: receipts + deposits + general receipts - payments - withdrawals of the bank in the period */
    private function bankClosingBalance($bankId, $from, $to)
    {
        $totalAvailableCash = 0;
        $deductFromTotal = 0;
        $mp = $this->sumPayments('member_payments', 'amount_paid', ['society_bank_id' => $bankId], 'payment_date', $from, $to);
        $totalAvailableCash = $totalAvailableCash + $this->n($mp);
        $sp = $this->sumPayments('society_payments', 'amount', ['payment_by_ledger_id' => $bankId], 'payment_date', $from, $to);
        $deductFromTotal = $deductFromTotal + $this->n($sp);

        $cw = DB::table('cash_withdraws')->where('society_id', $this->societyId)->where('bank_ledger_head_id', $bankId)
            ->select('txn_type', DB::raw('CAST(amount AS CHAR) as amount'));
        if (!empty($from) && !empty($to)) {
            $cw->whereBetween('payment_date', [$from, $to]);
        }
        foreach ($cw->orderBy('payment_date')->get() as $c) {
            if ($c->txn_type == 'deposit') {
                $totalAvailableCash = $totalAvailableCash + $this->n($c->amount);
            }
            if ($c->txn_type == 'withdraw') {
                $deductFromTotal = $deductFromTotal + $this->n($c->amount);
            }
        }
        $oi = $this->sumPayments('society_other_incomes', 'amount_paid', ['payment_mode' => 'bank', 'society_bank_id' => $bankId], 'payment_date', $from, $to);
        $totalAvailableCash = $totalAvailableCash + $this->n($oi);

        return $totalAvailableCash - $deductFromTotal;
    }
}
