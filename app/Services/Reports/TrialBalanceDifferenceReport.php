<?php

namespace App\Services\Reports;

use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * "Trial Balance Difference" (CakePHP account_reports/account_trial_balance_difference), two pages:
 *  - Member Bill Difference: per member bill, what the bill charged (Dr) against the charge lines that make it
 *    up (Cr: contribution + GST + interest);
 *  - Bank/Cash Difference (?t=1): every bank and cash movement of the year, listed per ledger head.
 */
class TrialBalanceDifferenceReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** @return array{members: array, bills: array, memberList: array} */
    public function memberBills(array $post): array
    {
        $filterMember = (isset($post['member_id']) && $post['member_id'] !== '') ? $post['member_id'] : null;
        $from = (isset($post['from_date']) && $post['from_date'] !== '') ? $post['from_date'] : null;
        $to = (isset($post['to_date']) && $post['to_date'] !== '') ? $post['to_date'] : null;

        $memberList = DB::table('members')->where('society_id', $this->societyId)->where('status', 1)->orderBy('id')->pluck('member_name', 'id')->all();

        $mq = DB::table('members')->where('society_id', $this->societyId)->where('status', 1)->orderBy('id');
        if ($filterMember) {
            $mq->where('id', $filterMember);
        }
        $members = $mq->get(['id', 'member_name', 'flat_no'])->map(fn ($r) => (array) $r)->all();

        $bq = DB::table('member_bill_summaries')
            ->select('member_id', 'bill_no', 'financial_year_id', 'bill_type', 'month',
                DB::raw('CAST(monthly_bill_amount AS CHAR) as monthly_bill_amount'), DB::raw('CAST(interest_on_due_amount AS CHAR) as interest_on_due_amount'))
            ->where('society_id', $this->societyId);
        if ($filterMember) {
            $bq->where('member_id', $filterMember);
        }
        if ($from && $to) {
            $bq->whereBetween('bill_generated_date', [$from, $to]);
        } elseif ($from) {
            $bq->where('bill_generated_date', '>=', $from);
        } elseif ($to) {
            $bq->where('bill_generated_date', '<=', $to);
        }
        $byMember = [];
        foreach ($bq->orderBy('id')->get() as $b) {
            $byMember[$b->member_id][] = (array) $b;
        }

        $gq = DB::table('member_bill_generates')
            ->select('member_id', 'month', 'financial_year_id', 'bill_type', DB::raw('CAST(SUM(amount) AS CHAR) as contri'), DB::raw('CAST(SUM(tax_total) AS CHAR) as gst'))
            ->where('society_id', $this->societyId);
        if ($filterMember) {
            $gq->where('member_id', $filterMember);
        }
        $contribution = [];
        foreach ($gq->groupBy('member_id', 'month', 'financial_year_id', 'bill_type')->get() as $g) {
            $contribution[$g->member_id][$g->month][$g->financial_year_id][$g->bill_type] = ['contri' => $g->contri ?? 0, 'gst' => $g->gst ?? 0];
        }

        $bills = [];
        $cnt = 0;
        foreach ($members as $m) {
            $memberId = $m['id'];
            $list = [];
            foreach ($byMember[$memberId] ?? [] as $b) {
                $month = $b['month'];
                $fy = $b['financial_year_id'];
                $bt = $b['bill_type'];
                $list[$cnt] = [
                    'bill_no' => $b['bill_no'], 'financial_year_id' => $fy, 'bill_type' => $bt, 'bill_month' => $month,
                    'bill_amount' => $b['monthly_bill_amount'],
                    'contri' => $contribution[$memberId][$month][$fy][$bt]['contri'] ?? 0,
                    'gst' => $contribution[$memberId][$month][$fy][$bt]['gst'] ?? 0,
                    'interest' => $b['interest_on_due_amount'],
                ];
                $cnt++;
            }
            $bills[$memberId] = $list;
        }

        return ['members' => $members, 'bills' => $bills, 'memberList' => $memberList];
    }

    /**
     * The bank / cash movements of the year per ledger head, in the shape the CakePHP view walks:
     * [ledger name][date][flag][slot] => fields.
     *
     * @param array $bankHeads [id => title]
     * @param array $cashHeads [id => title]
     */
    public function bankCash(array $bankHeads, array $cashHeads): array
    {
        $fy = $this->financialYearId;
        $sid = $this->societyId;

        // every charge line of the society by bill number (CakePHP does not tell members apart here)
        $billCredits = [];
        $rows = DB::table('member_bill_generates as g')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
            ->leftJoin('members as m', 'm.id', '=', 'g.member_id')
            ->where('g.society_id', $sid)
            ->get(['g.bill_number', DB::raw('CAST(g.amount AS CHAR) as amount'), 'h.title as ledger_title', 'm.member_name', 'm.id as member_id']);
        foreach ($rows as $r) {
            $billCredits[$r->bill_number][] = ['ledger_head' => $r->ledger_title, 'particular' => $r->member_name . '(' . $r->member_id . ')', 'amount' => $r->amount];
        }
        unset($rows);

        $bookData = [];
        $flag = null; // CakePHP keeps this variable from one ledger to the next
        foreach ($bankHeads as $lid => $lname) {
            $book = [];
            $memberReceipts = DB::table('member_payments as p')->leftJoin('members as m', 'm.id', '=', 'p.member_id')
                ->select(array_merge(TextSelect::columns('member_payments', 'p'), [DB::raw('m.flat_no as flat_no'), DB::raw('m.member_name as member_name')]))
                ->where('p.society_id', $sid)->where('p.financial_year_id', $fy)->where('p.society_bank_id', $lid)
                ->orderBy('p.payment_date')->get();
            $societyPayments = DB::table('society_payments as p')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'p.ledger_head_id')
                ->select(array_merge(TextSelect::columns('society_payments', 'p'), [DB::raw('h.title as ledger_title')]))
                ->where('p.society_id', $sid)->where('p.financial_year_id', $fy)->where('p.payment_by_ledger_id', $lid)
                ->orderBy('p.payment_date')->get();
            $cashWithdraws = DB::table('cash_withdraws as c')->select(TextSelect::columns('cash_withdraws', 'c'))
                ->where('c.society_id', $sid)->where('c.financial_year_id', $fy)->where('c.bank_ledger_head_id', $lid)
                ->orderBy('c.payment_date')->get();
            $contraReceived = DB::table('cash_withdraws as c')->select(TextSelect::columns('cash_withdraws', 'c'))
                ->where('c.society_id', $sid)->where('c.financial_year_id', $fy)->where('c.bank_to_ledger_head_id', $lid)
                ->orderBy('c.payment_date')->get();
            $otherIncomes = DB::table('society_other_incomes as o')->select(TextSelect::columns('society_other_incomes', 'o'))
                ->where('o.society_id', $sid)->where('o.financial_year_id', $fy)->where('o.society_bank_id', $lid)->where('o.payment_mode', 'Bank')
                ->orderBy('o.payment_date')->get();

            $s = 0;
            foreach ($societyPayments as $p) {
                $d = $p->payment_date;
                $book[$d]['Payment'][$s]['payment_date'] = $p->payment_date;
                $book[$d]['Payment'][$s]['particulars'] = $p->particulars . ' &' . $p->ledger_title;
                $book[$d]['Payment'][$s]['cheque_number'] = $p->cheque_reference_number;
                $book[$d]['Payment'][$s]['withdrawal'] = $p->total_amount;
                $book[$d]['Payment'][$s]['payment_flag'] = 'Payment';
                $book[$d]['Payment'][$s]['deposit'] = $p->total_amount;
                $book[$d]['Payment'][$s]['link'] = route('society.addPayment', $p->id);
                $book[$d]['Payment'][$s]['voucher_no'] = $p->bill_voucher_number;
                $s++;
            }
            $m = 0;
            foreach ($memberReceipts as $p) {
                $d = $p->payment_date;
                $book[$d]['Receipt'][$m]['payment_date'] = $p->payment_date;
                $book[$d]['Receipt'][$m]['particulars'] = $p->flat_no . ' - ' . $p->member_name . '<br>' . 'Being Maintenance received' . '<br>' . 'Bill Generated ID' . $p->bill_generated_id;
                $book[$d]['Receipt'][$m]['cheque_number'] = $p->cheque_reference_number;
                $book[$d]['Receipt'][$m]['withdrawal'] = (isset($billCredits[$p->bill_generated_id]) && !empty($billCredits[$p->bill_generated_id])) ? $billCredits[$p->bill_generated_id] : $p->amount_paid;
                $book[$d]['Receipt'][$m]['deposit'] = $p->amount_paid;
                $book[$d]['Receipt'][$m]['payment_flag'] = 'Receipt';
                $book[$d]['Receipt'][$m]['link'] = route('society.addMemberPayment', $p->id);
                $book[$d]['Receipt'][$m]['voucher_no'] = $p->receipt_id;
                $m++;
            }
            $cashCounter = 0;
            foreach ($cashWithdraws as $c) {
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
                $d = $c->payment_date;
                $k = $flag ?? '';
                $book[$d][$k][$cashCounter]['payment_date'] = $c->payment_date;
                $book[$d][$k][$cashCounter]['particulars'] = $c->particulars . '<br>' . $note;
                $book[$d][$k][$cashCounter]['cheque_number'] = $c->cheque_no;
                if ($flag == 'Contra' || $flag == 'Withdrawal' || $flag == 'Deposit') {
                    $book[$d][$k][$cashCounter]['withdrawal'] = $c->amount;
                    $book[$d][$k][$cashCounter]['deposit'] = $c->amount;
                }
                $book[$d][$k][$cashCounter]['payment_flag'] = $flag;
                $book[$d][$k][$cashCounter]['link'] = route('society.addCashContra', $c->id);
                $book[$d][$k][$cashCounter]['voucher_no'] = $c->id;
                $cashCounter++;
            }
            if (count($otherIncomes) > 0) {
                $counter = $cashCounter;
                $flag = 'Deposit';
                foreach ($otherIncomes as $o) {
                    $d = $o->payment_date;
                    $book[$d][$flag][$counter]['payment_date'] = $o->payment_date;
                    $book[$d][$flag][$counter]['particulars'] = $o->title;
                    $book[$d][$flag][$counter]['withdrawal'] = $o->amount_paid;
                    $book[$d][$flag][$counter]['deposit'] = $o->amount_paid;
                    $book[$d][$flag][$counter]['cheque_number'] = $o->cheque_no;
                    $book[$d][$flag][$counter]['payment_flag'] = 'Deposit';
                    $book[$d][$flag][$counter]['link'] = route('society.addGeneralReceipt', $o->id);
                    $book[$d][$flag][$counter]['voucher_no'] = $o->general_receipt_number;
                    $counter++;
                }
            }
            if (count($contraReceived) > 0) {
                foreach ($contraReceived as $c) {
                    if ($c->txn_type != 'contra') {
                        continue;
                    }
                    $d = $c->payment_date;
                    $k = $flag ?? '';
                    $book[$d][$k][$cashCounter]['payment_date'] = $c->payment_date;
                    $book[$d][$k][$cashCounter]['particulars'] = $c->particulars . '<br>' . 'Contra Received';
                    $book[$d][$k][$cashCounter]['cheque_number'] = $c->cheque_no;
                    $book[$d][$k][$cashCounter]['withdrawal'] = $c->amount;
                    $book[$d][$k][$cashCounter]['deposit'] = $c->amount;
                    $book[$d][$k][$cashCounter]['payment_flag'] = 'Contra';
                    $book[$d][$k][$cashCounter]['link'] = route('society.addCashContra', $c->id);
                }
            }
            ksort($book);
            $bookData[$lname] = $book;
        }

        // cash heads: society-wide, no financial-year filter
        foreach ($cashHeads as $lid => $lname) {
            $book = [];
            $memberReceipts = DB::table('member_payments as p')->leftJoin('members as m', 'm.id', '=', 'p.member_id')
                ->select(array_merge(TextSelect::columns('member_payments', 'p'), [DB::raw('m.member_name as member_name')]))
                ->where('p.society_id', $sid)->where('p.society_bank_id', $lid)->orderBy('p.payment_date')->get();
            $societyPayments = DB::table('society_payments as p')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'p.ledger_head_id')
                ->select(array_merge(TextSelect::columns('society_payments', 'p'), [DB::raw('h.title as ledger_title')]))
                ->where('p.society_id', $sid)->where('p.payment_by_ledger_id', $lid)->orderBy('p.payment_date')->get();
            $cashWithdraws = DB::table('cash_withdraws as c')->select(TextSelect::columns('cash_withdraws', 'c'))
                ->where('c.society_id', $sid)->orderBy('c.payment_date')->get();
            $otherIncomes = DB::table('society_other_incomes as o')->select(TextSelect::columns('society_other_incomes', 'o'))
                ->where('o.society_id', $sid)->where('o.society_bank_id', $lid)->orderBy('o.payment_date')->get();

            $s = 0;
            foreach ($societyPayments as $p) {
                $d = $p->payment_date;
                $book[$d]['Payment'][$s]['payment_date'] = $p->payment_date;
                $book[$d]['Payment'][$s]['particulars'] = $p->particulars . '&' . $p->ledger_title;
                $book[$d]['Payment'][$s]['cheque_number'] = $p->cheque_reference_number;
                $book[$d]['Payment'][$s]['withdrawal'] = $p->amount;
                $book[$d]['Payment'][$s]['payment_flag'] = 'Payment';
                $book[$d]['Payment'][$s]['deposit'] = $p->amount;
                $book[$d]['Payment'][$s]['link'] = route('society.addPayment', $p->id);
                $s++;
            }
            $m = 0;
            foreach ($memberReceipts as $p) {
                $d = $p->payment_date;
                $book[$d]['Receipt'][$m]['payment_date'] = $p->payment_date;
                $book[$d]['Receipt'][$m]['particulars'] = $p->member_name . ' Maint received';
                $book[$d]['Receipt'][$m]['cheque_number'] = $p->cheque_reference_number;
                $book[$d]['Receipt'][$m]['withdrawal'] = $p->amount_paid;
                $book[$d]['Receipt'][$m]['deposit'] = $p->amount_paid;
                $book[$d]['Receipt'][$m]['payment_flag'] = 'Receipt';
                $book[$d]['Receipt'][$m]['link'] = route('society.addMemberPayment', $p->id);
                $m++;
            }
            $cashCounter = 0;
            if (count($cashWithdraws) > 0) {
                foreach ($cashWithdraws as $c) {
                    $note = '';
                    if ($c->txn_type != 'deposit' && $c->txn_type != 'withdraw') {
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
                    $d = $c->payment_date;
                    $book[$d][$flag][$cashCounter]['payment_date'] = $c->payment_date;
                    $book[$d][$flag][$cashCounter]['particulars'] = $c->particulars . '<br>' . $note;
                    $book[$d][$flag][$cashCounter]['cheque_number'] = $c->cheque_no;
                    $book[$d][$flag][$cashCounter]['withdrawal'] = $c->amount;
                    $book[$d][$flag][$cashCounter]['deposit'] = $c->amount;
                    $book[$d][$flag][$cashCounter]['payment_flag'] = 'Contra';
                    $book[$d][$flag][$cashCounter]['link'] = route('society.addCashContra', $c->id);
                    $cashCounter++;
                }
                if (count($otherIncomes) > 0) {
                    $counter = $cashCounter;
                    $flag = 'Receipt';
                    foreach ($otherIncomes as $o) {
                        $d = $o->payment_date;
                        $book[$d][$flag][$counter]['payment_date'] = $o->payment_date;
                        $book[$d][$flag][$counter]['particulars'] = $o->title;
                        $book[$d][$flag][$counter]['withdrawal'] = $o->amount_paid;
                        $book[$d][$flag][$counter]['deposit'] = $o->amount_paid;
                        $book[$d][$flag][$counter]['cheque_number'] = $o->title;
                        $book[$d][$flag][$counter]['payment_flag'] = 'General Receipt';
                        $book[$d][$flag][$counter]['link'] = route('society.addGeneralReceipt', $o->id);
                        $counter++;
                    }
                }
            }
            ksort($book);
            $bookData[$lname] = $book;
        }

        return $bookData;
    }
}
