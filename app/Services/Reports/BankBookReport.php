<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Bank Book (CakePHP: AccountReportsController::account_bank_book + getOpeningBalanceForthePeriod
 * type 'bankbook' + getChecqueReturnedData).
 *
 * A line-by-line port so the figures match the CakePHP report. That includes its quirks, which are
 * deliberately kept (the migration must not change behaviour) and are called out where they matter:
 *  - rows are grouped as [payment date][group][counter]; two rows that land on the same
 *    date/group/counter overwrite each other (contra-received rows all use the cash counter);
 *  - "From" only = that one day, "To" only = that one day (with no opening balance);
 *  - the opening balance is only computed when a "From" date is given;
 *  - cheque-returned rows are added to the opening balance only when the period has society payments.
 */
class BankBookReport
{
    use BookQueryHelpers;

    private const OPERATORS = ['>', '<', '>=', '<=', '=', '<>'];

    /** @var string|null last transaction date before the period (dates the "Opening Balance" row) */
    private ?string $openingBalanceAsOfDate = null;

    public function __construct(
        private int $societyId,
        private ?int $financialYearId,
        private ?string $financialYearStart,
    ) {
    }

    /**
     * @param array $in society_bank_id, payment_date, payment_date_to, report_type, operator, amount
     * @return array{bankBookData: array, openingBalance: float|int, openingBalanceAsOfDate: ?string}
     */
    public function run(array $in): array
    {
        $bankId = $this->val($in, 'society_bank_id');
        $from = $this->val($in, 'payment_date');
        $to = $this->val($in, 'payment_date_to');
        $type = $this->val($in, 'report_type');
        $operator = $this->val($in, 'operator');
        $amount = $this->val($in, 'amount');
        $operator = in_array($operator, self::OPERATORS, true) ? $operator : '=';

        $openingBalance = 0;

        // ---- which rows: society + bank (+ amount) + date window -----------------------------
        $f = $this->filters($bankId, financialYear: false);
        if ($amount !== '') {
            $f = $this->withAmount($f, $operator, $amount);
        }

        if ($from !== '' && $to !== '') {
            $f = $this->withDates($f, between: [$from, $to]);
            $openingBalance = $this->openingBalanceForPeriod($from, $bankId);
        } elseif ($from !== '') {
            $f = $this->withDates($f, on: $from);
            $openingBalance = $this->openingBalanceForPeriod($from, $bankId);
        } elseif ($to !== '') {
            $f = $this->withDates($f, on: $to);
            // CakePHP asks for the opening only when "From" is filled - it is not here.
        }

        $data = [];
        $memberRows = $societyRows = $cashRows = $contraRows = $otherRows = $chequeRows = [];
        $emptyOut = ['bankBookData' => [], 'openingBalance' => $openingBalance, 'openingBalanceAsOfDate' => $this->openingBalanceAsOfDate];

        // ---- fetch by report type -------------------------------------------------------------
        if ($type === 'Deposit') {
            $memberRows = $this->memberRows($f);
            $cashRows = $this->cashRows($f, 'deposit');
            $otherRows = $this->otherIncomeRows($f);
        } elseif ($type === 'Withdrawal') {
            $societyRows = $this->societyRows($f);
            $chequeRows = $this->chequeReturned($bankId, $from, $to);
            $cashRows = $this->cashRows($f, 'withdraw');
        } elseif ($type === 'Contra') {
            $cashRows = $this->cashRows($f, 'contra');
            $contraRows = $this->contraReceivedRows($f);
        } else {
            $memberRows = $this->memberRows($f);
            $societyRows = $this->societyRows($f);
            $cashRows = $this->cashRows($f, null);
            $contraRows = $this->contraReceivedRows($f);
            $chequeRows = $this->chequeReturned($bankId, $from, $to);
            $otherRows = $this->otherIncomeRows($f);
        }

        // ---- build [date][group][counter] ------------------------------------------------------
        $flag = null;

        if (!empty($chequeRows)) {
            $i = 0;
            $flag = 'withdrawal';
            foreach ($chequeRows as $c) {
                $d = date('Y-m-d', strtotime($c->cheque_return_date));
                $data[$d][$flag][$i] = [
                    'payment_date' => $d,
                    'particulars' => 'cheque returned',
                    'cheque_number' => $c->cheque_no,
                    'withdrawal' => $c->cheque_amount,
                    'payment_flag' => $flag,
                    'deposit' => '0.00',
                    'link' => '',
                    'ledgerexists' => 0,
                ];
                $i++;
            }
        }

        if (!empty($societyRows)) {
            $i = 0;
            foreach ($societyRows as $s) {
                $data[$s->payment_date]['Payment'][$i] = [
                    'payment_date' => $s->payment_date,
                    'title' => $s->ledger_title,
                    'particulars' => $s->particulars,
                    'cheque_number' => $s->cheque_reference_number,
                    'withdrawal' => $s->total_amount_txt,
                    'payment_flag' => 'Payment',
                    'deposit' => '0.00',
                    'link' => route('society.addPayment', $s->id),
                    'ledgerexists' => 0,
                ];
                $i++;
            }
        }

        if (!empty($memberRows)) {
            $i = 0;
            foreach ($memberRows as $m) {
                $data[$m->payment_date]['Receipt'][$i] = [
                    'payment_date' => $m->payment_date,
                    'particulars' => $m->m_flat_no . ' - ' . $m->m_member_name . '<br>' . 'Being Maintenance received',
                    'cheque_number' => $m->cheque_reference_number,
                    'withdrawal' => '0.00',
                    'deposit' => $m->amount_paid_txt,
                    'payment_flag' => 'Receipt',
                    'link' => route('society.addMemberPayment', $m->id),
                    'ledgerexists' => 0,
                ];
                $i++;
            }
        }

        $cashCounter = 0;
        if (!empty($cashRows)) {
            foreach ($cashRows as $c) {
                $note = '';
                switch ($c->txn_type) {
                    case 'contra':
                        $flag = 'Contra';
                        $note = 'Contra Trasferred';
                        break;
                    case 'deposit':
                        $flag = 'Deposit';
                        $note = 'Deposited in Bank';
                        break;
                    case 'withdraw':
                        $flag = 'Withdrawal';
                        $note = 'Withdrawn from Bank';
                        break;
                }
                $row = [
                    'payment_date' => $c->payment_date,
                    'particulars' => $c->particulars . '<br>' . $note,
                    'cheque_number' => $c->cheque_no,
                ];
                if ($flag === 'Contra' || $flag === 'Withdrawal') {
                    $row['withdrawal'] = $c->amount_txt;
                    $row['deposit'] = '0.00';
                } elseif ($flag === 'Deposit') {
                    $row['withdrawal'] = '0.00';
                    $row['deposit'] = $c->amount_txt;
                }
                $row['payment_flag'] = $flag;
                $row['link'] = route('society.addCashContra', $c->id);
                $row['ledgerexists'] = 1;
                $data[$c->payment_date][$flag][$cashCounter] = $row;
                $cashCounter++;
            }
        }

        if (!empty($otherRows)) {
            $i = $cashCounter;
            $flag = 'Deposit';
            foreach ($otherRows as $o) {
                $data[$o->payment_date][$flag][$i] = [
                    'payment_date' => $o->payment_date,
                    'particulars' => $o->title,
                    'withdrawal' => '0.00',
                    'deposit' => $o->amount_paid_txt,
                    'cheque_number' => $o->cheque_no,
                    'payment_flag' => 'Deposit',
                    'link' => route('society.addGeneralReceipt', $o->id),
                    'ledgerexists' => 0,
                ];
                $i++;
            }
        }

        if (!empty($contraRows)) {
            foreach ($contraRows as $c) {
                if ($c->txn_type !== 'contra') {
                    continue;
                }
                // CakePHP indexes these by the cash counter (not by its own), so several contra-received
                // rows on the same date all land in one slot - kept as is.
                $data[$c->payment_date][$flag][$cashCounter] = [
                    'payment_date' => $c->payment_date,
                    'particulars' => $c->particulars . '<br>' . 'Contra Received',
                    'cheque_number' => $c->cheque_no,
                    'withdrawal' => '0.00',
                    'deposit' => $c->amount_txt,
                    'payment_flag' => 'Contra',
                    'link' => route('society.addCashContra', $c->id),
                    'ledgerexists' => 1,
                ];
            }
        }

        ksort($data);

        return [
            'bankBookData' => $data,
            'openingBalance' => $openingBalance,
            'openingBalanceAsOfDate' => $this->openingBalanceAsOfDate,
        ];
    }

    // ------------------------------------------------------------------------------------------
    // Opening balance for the period (getOpeningBalanceForthePeriod, type 'bankbook')
    // ------------------------------------------------------------------------------------------

    private function openingBalanceForPeriod(string $date, string $bankId): float|int
    {
        $this->openingBalanceAsOfDate = null;

        $f = $this->filters($bankId, financialYear: true);
        $paymentDateFrom = $this->financialYearStart ?: $this->firstDayOfFinancialYear();
        $paymentDateTo = date('Y-m-d', strtotime($date . ' - 1 days'));
        $f = $this->withDates($f, between: [$paymentDateFrom, $paymentDateTo]);

        $memberRows = $this->memberRows($f);
        $societyRows = $this->societyRows($f);
        $cashRows = $this->cashRows($f, null);
        $contraRows = $this->contraReceivedRows($f);
        $otherRows = $this->otherIncomeRows($f);

        $data = [];
        $flag = null;

        if (!empty($societyRows)) {
            $i = 0;
            foreach ($societyRows as $s) {
                $data[$s->payment_date]['Payment'][$i] = ['withdrawal' => $s->total_amount_txt, 'deposit' => '0.00'];
                $i++;
            }
            // CakePHP looks for returned cheques here, i.e. only when the period has society payments.
            $chequeRows = $this->chequeReturned($bankId, $paymentDateFrom, $paymentDateTo);
            if (!empty($chequeRows)) {
                $i = 0;
                $flag = 'withdrawal';
                foreach ($chequeRows as $c) {
                    $d = date('Y-m-d', strtotime($c->cheque_return_date));
                    $data[$d][$flag][$i] = ['withdrawal' => $c->cheque_amount, 'deposit' => '0.00'];
                    $i++;
                }
            }
        }

        if (!empty($memberRows)) {
            $i = 0;
            foreach ($memberRows as $m) {
                $data[$m->payment_date]['Receipt'][$i] = ['withdrawal' => '0.00', 'deposit' => $m->amount_paid_txt];
                $i++;
            }
        }

        $cashCounter = 0;
        if (!empty($cashRows)) {
            foreach ($cashRows as $c) {
                switch ($c->txn_type) {
                    case 'contra':
                        $flag = 'Contra';
                        break;
                    case 'deposit':
                        $flag = 'Deposit';
                        break;
                    case 'withdraw':
                        $flag = 'Withdrawal';
                        break;
                }
                if ($flag === 'Contra' || $flag === 'Withdrawal') {
                    $data[$c->payment_date][$flag][$cashCounter] = ['withdrawal' => $c->amount_txt, 'deposit' => '0.00'];
                } elseif ($flag === 'Deposit') {
                    $data[$c->payment_date][$flag][$cashCounter] = ['withdrawal' => '0.00', 'deposit' => $c->amount_txt];
                }
                $data[$c->payment_date][$flag][$cashCounter]['payment_flag'] = $flag;
                $cashCounter++;
            }
        }

        if (!empty($otherRows)) {
            $i = $cashCounter;
            $flag = 'Deposit';
            foreach ($otherRows as $o) {
                $data[$o->payment_date][$flag][$i] = ['withdrawal' => '0.00', 'deposit' => $o->amount_paid_txt];
                $i++;
            }
        }

        if (!empty($contraRows)) {
            foreach ($contraRows as $c) {
                if ($c->txn_type !== 'contra') {
                    continue;
                }
                $data[$c->payment_date][$flag][$cashCounter] = ['withdrawal' => '0.00', 'deposit' => $c->amount_txt];
            }
        }

        ksort($data);
        $this->openingBalanceAsOfDate = count($data) > 0 ? max(array_keys($data)) : null;

        $opening = $this->ledgerOpeningBalance($bankId);
        $totalDeposit = $opening;
        $totalWithdraw = 0;
        foreach ($data as $groups) {
            foreach ($groups as $rows) {
                foreach ($rows as $r) {
                    $totalDeposit += $r['deposit'];
                    $totalWithdraw += $r['withdrawal'];
                }
            }
        }

        return $totalDeposit - abs($totalWithdraw);
    }

    private function firstDayOfFinancialYear(): string
    {
        // CakePHP: a fixed 2022-04-01 for the old-database config, otherwise April 1st of the
        // running financial year. Only used when the session has no year start date.
        $month = (int) date('n');
        $year = (int) date('Y');

        return ($month >= 4 ? $year : $year - 1) . '-04-01';
    }

    // ------------------------------------------------------------------------------------------
    // Row sources
    // ------------------------------------------------------------------------------------------

    private function memberRows(array $f)
    {
        $q = DB::table('member_payments as mp')
            ->leftJoin('members as m', 'm.id', '=', 'mp.member_id')
            ->selectRaw('mp.*, CAST(mp.amount_paid AS CHAR) AS amount_paid_txt, m.flat_no AS m_flat_no, m.member_name AS m_member_name');

        return $this->apply($q, $f, 'mp', 'society_bank_id', 'amount_paid')->orderBy('mp.payment_date')->get()->all();
    }

    private function societyRows(array $f)
    {
        $q = DB::table('society_payments as sp')
            ->leftJoin('society_ledger_heads as lh', 'lh.id', '=', 'sp.ledger_head_id')
            ->selectRaw('sp.*, CAST(sp.total_amount AS CHAR) AS total_amount_txt, lh.title AS ledger_title');

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

    private function contraReceivedRows(array $f)
    {
        $q = DB::table('cash_withdraws as cw')->selectRaw('cw.*, CAST(cw.amount AS CHAR) AS amount_txt');
        $this->apply($q, $f, 'cw', 'bank_to_ledger_head_id', 'amount');

        return $q->orderBy('cw.payment_date')->get()->all();
    }

    private function otherIncomeRows(array $f)
    {
        $q = DB::table('society_other_incomes as oi')->selectRaw('oi.*, CAST(oi.amount_paid AS CHAR) AS amount_paid_txt');
        $this->apply($q, $f, 'oi', 'society_bank_id', 'amount_paid');
        $q->where('oi.payment_mode', 'Bank');

        return $q->orderBy('oi.payment_date')->get()->all();
    }

    /** getChecqueReturnedData: returned cheques matched to the member payment they were banked with. */
    private function chequeReturned(string $bankId, string $from, string $to)
    {
        $q = DB::table('cheque_return_details as c')
            ->join('member_payments as member_pay', function ($j) {
                $j->on('member_pay.cheque_reference_number', '=', 'c.cheque_no')
                    ->on('member_pay.member_id', '=', 'c.member_id');
            })
            ->selectRaw('c.cheque_return_date, c.cheque_no, CAST(c.cheque_amount AS CHAR) AS cheque_amount');
        if ($bankId !== '' && (int) $bankId > 0) {
            $q->where('member_pay.society_bank_id', $bankId);
        }
        if ($from !== '' && $to !== '') {
            $q->whereBetween('c.cheque_return_date', [$from, $to]);
        }

        return $q->get()->all();
    }

}
