<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Cash Book (CakePHP: AccountReportsController::account_cash_book + getOpeningBalanceForthePeriod
 * type 'cashbook').
 *
 * A line-by-line port so the figures match the CakePHP report, quirks included (kept on purpose):
 *  - member receipts and society payments are picked by the chosen cash head (society_bank_id /
 *    payment_by_ledger_id); bank<->cash transfers (cash_withdraws) are NOT limited to a cash head;
 *  - general receipts are always the ones paid in Cash, and they are listed under both Receipt and
 *    Payment types;
 *  - the opening balance is the head's own opening_amount, replaced by the computed opening only when a
 *    "From" date is given; "To" only = that one day, and general receipts then drop out (Cake compares
 *    their date with the empty "From");
 *  - rows are grouped [date][group][counter]; rows landing on the same slot overwrite each other.
 */
class CashBookReport
{
    use BookQueryHelpers;

    private const OPERATORS = ['>', '<', '>=', '<=', '=', '<>'];

    public function __construct(
        private int $societyId,
        private ?int $financialYearId,
        private ?string $financialYearStart,
    ) {
    }

    /**
     * @param array $in cash_ledger_head_id, payment_date, payment_date_to, report_type, operator, amount
     * @return array{cashBookData: array, openingBalance: float|int|string|null, ledgerHeadSelected: bool}
     */
    public function run(array $in): array
    {
        $head = $this->val($in, 'cash_ledger_head_id');
        $from = $this->val($in, 'payment_date');
        $to = $this->val($in, 'payment_date_to');
        $type = $this->val($in, 'report_type');
        $operator = $this->val($in, 'operator');
        $amount = $this->val($in, 'amount');
        $operator = in_array($operator, self::OPERATORS, true) ? $operator : '=';

        $openingBalance = 0;
        $ledgerHeadSelected = false;
        if ($head !== '') {
            $ledgerHeadSelected = true;
            $row = DB::table('society_ledger_heads')->where('society_id', $this->societyId)->where('id', $head)->first();
            $openingBalance = $row ? $row->opening_amount : null;
        }

        // ---- constraints per source -----------------------------------------------------------
        $withHead = $this->filters($head, financialYear: false);   // member receipts / society payments
        $noHead = $this->filters('', financialYear: false);         // cash withdraws / general receipts: society only
        if ($amount !== '') {
            $withHead = $this->withAmount($withHead, $operator, $amount);
            $noHead = $this->withAmount($noHead, $operator, $amount);
        }
        $otherNoRows = false;

        if ($from !== '' && $to !== '') {
            $withHead = $this->withDates($withHead, between: [$from, $to]);
            $noHead = $this->withDates($noHead, between: [$from, $to]);
            $openingBalance = $this->openingBalanceForPeriod($from, $head);
        } elseif ($from !== '') {
            $withHead = $this->withDates($withHead, on: $from);
            $noHead = $this->withDates($noHead, on: $from);
            $openingBalance = $this->openingBalanceForPeriod($from, $head);
        } elseif ($to !== '') {
            $withHead = $this->withDates($withHead, on: $to);
            $noHead = $this->withDates($noHead, on: $to);
            // CakePHP compares the general receipts' date with the (empty) "From" here: none match.
            $otherNoRows = true;
        }

        // ---- fetch by type ---------------------------------------------------------------------
        $memberRows = $societyRows = $cashRows = $otherRows = [];
        if ($type === 'Receipt') {
            $memberRows = $this->memberRows($withHead);
            $cashRows = $this->cashRows($noHead, 'withdraw');
            $otherRows = $otherNoRows ? [] : $this->otherIncomeRows($noHead, 'Cash');
        } elseif ($type === 'Payment') {
            $societyRows = $this->societyRows($withHead);
            $cashRows = $this->cashRows($noHead, 'deposit');
            $otherRows = $otherNoRows ? [] : $this->otherIncomeRows($noHead, 'Cash');
        } else {
            $memberRows = $this->memberRows($withHead);
            $societyRows = $this->societyRows($withHead);
            $cashRows = $this->cashRows($noHead, null);
            $otherRows = $otherNoRows ? [] : $this->otherIncomeRows($noHead, 'Cash');
        }

        // ---- build [date][group][counter] ------------------------------------------------------
        $data = [];
        $flag = null;

        if (!empty($societyRows)) {
            $i = 0;
            foreach ($societyRows as $s) {
                $this->put($data, $s->payment_date, 'Payment', $i, [
                    'payment_date' => $s->payment_date,
                    'title' => $s->ledger_title,
                    'particulars' => $s->particulars,
                    'cheque_number' => $s->cheque_reference_number,
                    'withdrawal' => $s->amount_txt,
                    'payment_flag' => 'Payment',
                    'deposit' => '0.00',
                    'link' => route('society.addPayment', $s->id),
                    'voucher_no' => $s->bill_voucher_number,
                    'ledgerexists' => 0,
                ]);
                $i++;
            }
        }

        if (!empty($memberRows)) {
            $i = 0;
            foreach ($memberRows as $m) {
                $this->put($data, $m->payment_date, 'Receipt', $i, [
                    'payment_date' => $m->payment_date,
                    'particulars' => $m->m_member_name . ' Maint received',
                    'cheque_number' => $m->cheque_reference_number,
                    'withdrawal' => '0.00',
                    'deposit' => $m->amount_paid_txt,
                    'payment_flag' => 'Receipt',
                    'link' => route('society.addMemberPayment', $m->id),
                    'voucher_no' => $m->receipt_id,
                    'ledgerexists' => 0,
                ]);
                $i++;
            }
        }

        $cashCounter = 0;
        foreach ($cashRows as $c) {
            $note = '';
            if ($c->txn_type !== 'deposit' && $c->txn_type !== 'withdraw') {
                continue;
            }
            switch ($c->txn_type) {
                case 'deposit':
                    $flag = 'Payment';
                    $note = 'Deposited in Bank';
                    break;
                case 'withdraw':
                    $flag = 'Receipt';
                    $note = 'Withdrawn from Bank';
                    break;
            }
            $row = [
                'payment_date' => $c->payment_date,
                'particulars' => $c->particulars . '<br>' . $note,
                'cheque_number' => $c->cheque_no,
            ];
            if ($flag === 'Payment') {
                $row['withdrawal'] = $c->amount_txt;
                $row['deposit'] = '0.00';
            } elseif ($flag === 'Receipt') {
                $row['withdrawal'] = '0.00';
                $row['deposit'] = $c->amount_txt;
            }
            $row['payment_flag'] = 'Contra';
            $row['link'] = route('society.addCashContra', $c->id);
            $row['voucher_no'] = $c->id;
            $row['ledgerexists'] = 1;
            $this->put($data, $c->payment_date, $flag, $cashCounter, $row);
            $cashCounter++;
        }

        if (!empty($otherRows)) {
            $i = $cashCounter;
            $flag = 'Receipt';
            foreach ($otherRows as $o) {
                $this->put($data, $o->payment_date, $flag, $i, [
                    'payment_date' => $o->payment_date,
                    'particulars' => $o->title,
                    'withdrawal' => '0.00',
                    'deposit' => $o->amount_paid_txt,
                    'cheque_number' => $o->title,
                    'payment_flag' => 'General Receipt',
                    'link' => route('society.addGeneralReceipt', $o->id),
                    'voucher_no' => $o->general_receipt_number,
                    'ledgerexists' => 0,
                ]);
                $i++;
            }
        }

        ksort($data);

        return [
            'cashBookData' => $data,
            'openingBalance' => $openingBalance,
            'ledgerHeadSelected' => $ledgerHeadSelected,
        ];
    }

    // ------------------------------------------------------------------------------------------
    // Opening balance for the period (getOpeningBalanceForthePeriod, type 'cashbook')
    // ------------------------------------------------------------------------------------------

    private function openingBalanceForPeriod(string $date, string $head): float|int
    {
        $withHead = $this->filters($head, financialYear: true);
        $noHead = $this->filters('', financialYear: true);
        $dateFrom = $this->financialYearStart ?: $this->firstDayOfFinancialYear();
        $dateTo = date('Y-m-d', strtotime($date . ' - 1 days'));
        $withHead = $this->withDates($withHead, between: [$dateFrom, $dateTo]);
        $noHead = $this->withDates($noHead, between: [$dateFrom, $dateTo]);

        $memberRows = $this->memberRows($withHead);
        $societyRows = $this->societyRows($withHead);
        $cashRows = $this->cashRows($noHead, null);
        $otherRows = $this->otherIncomeRows($noHead, 'cash');

        $data = [];
        $flag = null;

        if (!empty($societyRows)) {
            $i = 0;
            foreach ($societyRows as $s) {
                $this->put($data, $s->payment_date, 'Payment', $i, ['withdrawal' => $s->amount_txt, 'deposit' => '0.00']);
                $i++;
            }
        }
        if (!empty($memberRows)) {
            $i = 0;
            foreach ($memberRows as $m) {
                $this->put($data, $m->payment_date, 'Receipt', $i, ['withdrawal' => '0.00', 'deposit' => $m->amount_paid_txt]);
                $i++;
            }
        }

        $cashCounter = 0;
        foreach ($cashRows as $c) {
            if ($c->txn_type === 'contra') {
                continue;
            }
            switch ($c->txn_type) {
                case 'deposit':
                    $flag = 'Payment';
                    break;
                case 'withdraw':
                    $flag = 'Receipt';
                    break;
            }
            if ($flag === 'Payment') {
                $this->put($data, $c->payment_date, $flag, $cashCounter, ['withdrawal' => $c->amount_txt, 'deposit' => '0.00']);
            } elseif ($flag === 'Receipt') {
                $this->put($data, $c->payment_date, $flag, $cashCounter, ['withdrawal' => '0.00', 'deposit' => $c->amount_txt]);
            }
            $cashCounter++;
        }

        if (!empty($otherRows)) {
            $i = $cashCounter;
            $flag = 'Receipt';
            foreach ($otherRows as $o) {
                $this->put($data, $o->payment_date, $flag, $i, ['withdrawal' => '0.00', 'deposit' => $o->amount_paid_txt]);
                $i++;
            }
        }

        $totalDeposit = $this->ledgerOpeningBalance($head);
        $totalWithdraw = 0;
        foreach ($data as $groups) {
            foreach ($groups as $rows) {
                foreach ($rows as $r) {
                    $totalDeposit += $r['deposit'] ?? 0;
                    $totalWithdraw += $r['withdrawal'] ?? 0;
                }
            }
        }

        return $totalDeposit - abs($totalWithdraw);
    }

    private function firstDayOfFinancialYear(): string
    {
        $month = (int) date('n');
        $year = (int) date('Y');

        return ($month >= 4 ? $year : $year - 1) . '-04-01';
    }

    // ------------------------------------------------------------------------------------------
    // Row sources (`table.*` first: that is what makes ties on the same date come out in CakePHP's order)
    // ------------------------------------------------------------------------------------------

    private function memberRows(array $f)
    {
        $q = DB::table('member_payments as mp')
            ->leftJoin('members as m', 'm.id', '=', 'mp.member_id')
            ->selectRaw('mp.*, CAST(mp.amount_paid AS CHAR) AS amount_paid_txt, m.member_name AS m_member_name');

        return $this->apply($q, $f, 'mp', 'society_bank_id', 'amount_paid')->orderBy('mp.payment_date')->get()->all();
    }

    private function societyRows(array $f)
    {
        $q = DB::table('society_payments as sp')
            ->leftJoin('society_ledger_heads as lh', 'lh.id', '=', 'sp.ledger_head_id')
            ->selectRaw('sp.*, CAST(sp.amount AS CHAR) AS amount_txt, lh.title AS ledger_title');

        return $this->apply($q, $f, 'sp', 'payment_by_ledger_id', 'amount')->orderBy('sp.payment_date')->get()->all();
    }

    private function cashRows(array $f, ?string $txnType)
    {
        $q = DB::table('cash_withdraws as cw')->selectRaw('cw.*, CAST(cw.amount AS CHAR) AS amount_txt');
        $this->apply($q, $f, 'cw', 'bank_ledger_head_id', 'amount');
        if ($txnType !== null) {
            $q->where('cw.txn_type', $txnType);
        }

        return $q->orderBy('cw.payment_date')->get()->all();
    }

    private function otherIncomeRows(array $f, string $paymentMode)
    {
        $q = DB::table('society_other_incomes as oi')->selectRaw('oi.*, CAST(oi.amount_paid AS CHAR) AS amount_paid_txt');
        $this->apply($q, $f, 'oi', 'society_bank_id', 'amount_paid');
        $q->where('oi.payment_mode', $paymentMode);

        return $q->orderBy('oi.payment_date')->get()->all();
    }
}
