<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Bill Summary Update (CakePHP account_reports/comparison_2018closing_2019opening): each member's closing
 * balance worked out from last year's closing plus this year's bills and receipts, against the balance
 * their latest bill of the year shows. Also the member-ledger reconciliation (preview / apply) behind the
 * page's second block.
 */
class BillSummaryUpdateReport
{
    public function __construct(private int $societyId, private ?int $financialYearId, private ?string $yearStart, private ?string $yearEnd)
    {
    }

    /** @return array{societyMemberDetails: array, differenceTotalAmount: mixed} */
    public function run(): array
    {
        $trial = (new TrialBalanceReport($this->societyId, $this->financialYearId, $this->yearStart, $this->yearEnd))->forRange($this->yearStart, $this->yearEnd);
        $opening = $trial->memberOpeningBalances();

        $details = [];
        $members = DB::table('members')->where('society_id', $this->societyId)->where('status', 1)->orderBy('id')->get(['id', 'member_name', 'flat_no']);
        foreach ($members as $m) {
            $closing = $this->closing($trial, $opening, $m->id);
            $details[$m->id]['closing2018'] = $closing;
            $details[$m->id]['flat_no'] = $m->flat_no;
            $details[$m->id]['closingCrDr'] = $this->crDr($closing);
        }

        $last = $this->lastBills('reg');
        $differenceTotal = 0;
        foreach ($members as $m) {
            $details[$m->id]['member_name'] = $m->member_name;
            $balance = $last[$m->id]['balance_amount'] ?? null;
            $details[$m->id]['opening2019'] = number_format(abs($balance === null ? 0 : $balance), 2, '.', ',');
            $details[$m->id]['openingCrDr'] = $this->crDr($balance);
            $difference = abs($details[$m->id]['closing2018']) - abs($balance === null ? 0 : $balance);
            $differenceTotal += $difference;
            $details[$m->id]['difference'] = $difference;
            $details[$m->id]['differenceCrDr'] = $this->crDr($difference);
        }

        return ['societyMemberDetails' => $details, 'differenceTotalAmount' => $differenceTotal];
    }

    /** the member's closing balance: opening carried in + the year's bills - the year's receipts */
    private function closing(TrialBalanceReport $trial, array $opening, $memberId)
    {
        $debit = 0;
        $credit = 0;
        $openingTemp = 0;
        if (isset($opening[$memberId])) {
            $openingTemp = $this->n($opening[$memberId]['principal_balance']) + $this->n($opening[$memberId]['interest_balance']) + $this->n($opening[$memberId]['tax_balance']);
        }
        if ($openingTemp < 0) {
            $credit = abs($openingTemp);
        } elseif ($openingTemp > 0) {
            $debit = abs($openingTemp);
        }
        $txn = $trial->memberBalance((int) $memberId);
        $txn['debit'] += $this->billLevelAdjustment((int) $memberId);

        return ($debit + $txn['debit']) - ($credit + $txn['credit']);
    }

    /**
     * Bill-level principal adjustments that are not in the bill's head lines (port of Cake's
     * _memberBillLevelAdjustment).
     *
     * The member's debit is built from the bill HEAD lines (member_bill_generates) plus interest, but a bill can
     * carry an extra principal adjustment of its own (member_bill_summaries.monthly_principal_amount differing from
     * the sum of its head lines, e.g. an "Other Adjustment (Principal)" keyed in on the bill). The member ledger and
     * the bill's stored balance include it, so this comparison has to as well - otherwise a correct member shows as
     * a false mismatch. Only bills that HAVE head lines are compared (a bill with none is left as before), inside
     * the same date window as the rest of the report. Used here only - the Trial Balance / Balance Sheet post from
     * the ledger heads and stay as they are.
     */
    private function billLevelAdjustment(int $memberId): float
    {
        if ($memberId <= 0) {
            return 0.0;
        }

        $heads = DB::table('member_bill_generates')
            ->where('member_id', $memberId)->where('society_id', $this->societyId)
            ->groupBy('member_id', 'bill_generated_date', 'bill_type')
            ->selectRaw('member_id, bill_generated_date, bill_type, SUM(amount) as amt');

        $q = DB::table('member_bill_summaries as s')
            ->joinSub($heads, 'g', function ($j) {
                $j->on('g.member_id', '=', 's.member_id')
                    ->on('g.bill_generated_date', '=', 's.bill_generated_date')
                    ->on('g.bill_type', '=', 's.bill_type');
            })
            ->where('s.member_id', $memberId)->where('s.society_id', $this->societyId);

        if ($this->yearStart && $this->yearEnd) {
            $q->whereBetween('s.bill_generated_date', [$this->yearStart, $this->yearEnd]);
        } elseif ($this->yearStart) {
            $q->where('s.bill_generated_date', $this->yearStart);
        } elseif ($this->yearEnd) {
            $q->where('s.bill_generated_date', $this->yearEnd);
        }

        $adjustment = round((float) $q->selectRaw('SUM(s.monthly_principal_amount - g.amt) as adjustment')->value('adjustment'), 2);

        return abs($adjustment) < 0.005 ? 0.0 : $adjustment;
    }

    /** SocietyBill::getMemberWiseClosingBalance: the latest bill row (highest id) of each member for this year and bill type */
    private function lastBills(string $billType): array
    {
        $ids = DB::table('member_bill_summaries')->where('society_id', $this->societyId)->where('bill_type', $billType)
            ->where('financial_year_id', $this->financialYearId)->groupBy('member_id')->selectRaw('max(id) as id');
        $out = [];
        $rows = DB::table('member_bill_summaries')->whereIn('id', $ids)
            ->select('member_id', DB::raw('CAST(balance_amount AS CHAR) as balance_amount'))->get();
        foreach ($rows as $r) {
            $out[$r->member_id] = ['balance_amount' => $r->balance_amount];
        }

        return $out;
    }

    private function crDr($amount): string
    {
        return $amount <= 0 ? ' Cr' : ' Dr';
    }

    private function n($v)
    {
        return is_numeric($v) ? $v + 0 : 0;
    }

    /**
     * SocietyBillsController::reconcileMemberBalanceForCurrentYear: what the member's last bill of this year should
     * carry (opening + this year's bills - this year's receipts, spread tax -> interest -> principal).
     * With $save the last bill row is rewritten with the computed figures.
     */
    public function reconcile(int $memberId, bool $save): array
    {
        $memberTransfer = (int) (DB::table('members')->where('id', $memberId)->value('member_transfer') ?? 0);
        // (a transferred unit's new owner does not inherit the old owner's closing row - see MemberClosing)
        $prev = \App\Support\MemberClosing::rowsForOpening($memberId, $memberTransfer, (int) ($this->financialYearId ?? 0));
        $reg = null;
        foreach ($prev as $p) {
            if ($p->bill_type === 'reg') {
                $reg = $p; // the last row of the bill type wins
            }
        }
        if ($reg) {
            $opPrincipal = (float) $reg->principal_balance;
            $opInterest = (float) $reg->interest_balance;
            $opTax = (float) $reg->tax_balance;
        } else {
            $row = DB::table('members')->where('id', $memberId)->first(['op_principal', 'op_interest', 'op_tax']);
            $opPrincipal = !empty($row->op_principal) ? (float) $row->op_principal : 0.0;
            $opInterest = !empty($row->op_interest) ? (float) $row->op_interest : 0.0;
            $opTax = !empty($row->op_tax) ? (float) $row->op_tax : 0.0;
        }

        $bills = DB::table('member_bill_summaries')
            ->where('member_id', $memberId)->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)
            ->where('member_transfer', $memberTransfer)->where('bill_type', 'reg')
            ->orderByDesc('bill_generated_date')->orderByDesc('id')->get();
        if ($bills->isEmpty()) {
            return ['success' => false, 'error' => 'No bill summary rows exist for this member in the current financial year.'];
        }
        $last = $bills[0];
        $tax = $opTax;
        $interest = $opInterest;
        $principal = $opPrincipal;
        foreach ($bills as $b) {
            $tax += (float) $b->tax_total;
            $interest += (float) $b->interest_on_due_amount;
            $principal += (float) $b->monthly_principal_amount;
        }
        $totalDebit = round($tax + $interest + $principal, 2);
        $paid = (float) DB::table('member_payments')
            ->where('member_id', $memberId)->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)
            ->where('member_transfer', $memberTransfer)->sum('amount_paid');
        $net = round($totalDebit - $paid, 2);
        $remaining = $net;
        $newTax = ($remaining > 0) ? min($remaining, $tax) : 0.0;
        $remaining -= $newTax;
        $newInterest = ($remaining > 0) ? min($remaining, $interest) : 0.0;
        $remaining -= $newInterest;
        $newPrincipal = $remaining;
        $newTax = round($newTax, 2);
        $newInterest = round($newInterest, 2);
        $newPrincipal = round($newPrincipal, 2);
        $newBalance = round($newTax + $newInterest + $newPrincipal, 2);

        $newOpTax = round($newTax - (float) $last->tax_total, 2);
        $newOpInterest = round($newInterest - (float) $last->interest_on_due_amount, 2);
        $newOpPrincipal = round($newPrincipal - (float) $last->monthly_principal_amount, 2);
        $newOpDue = round($newOpTax + $newOpInterest + $newOpPrincipal, 2);
        $newPayable = round($newOpDue + (float) $last->monthly_bill_amount, 2);

        $result = [
            'success' => true, 'memberId' => $memberId, 'billId' => $last->id, 'financialYearId' => $this->financialYearId, 'net' => $net,
            'current' => [
                'op_principal_arrears' => (float) $last->op_principal_arrears, 'op_interest_arrears' => (float) $last->op_interest_arrears,
                'op_tax_arrears' => (float) $last->op_tax_arrears, 'amount_payable' => (float) $last->amount_payable,
                'tax_balance' => (float) $last->tax_balance, 'interest_balance' => (float) $last->interest_balance,
                'principal_balance' => (float) $last->principal_balance, 'balance_amount' => (float) $last->balance_amount,
            ],
            'computed' => [
                'op_principal_arrears' => $newOpPrincipal, 'op_interest_arrears' => $newOpInterest, 'op_tax_arrears' => $newOpTax,
                'amount_payable' => $newPayable, 'tax_balance' => $newTax, 'interest_balance' => $newInterest,
                'principal_balance' => $newPrincipal, 'balance_amount' => $newBalance,
            ],
            'saved' => false,
        ];

        if ($save) {
            $ok = DB::table('member_bill_summaries')->where('id', $last->id)->update([
                'op_principal_arrears' => $newOpPrincipal, 'op_principal_arrears_original' => $newOpPrincipal,
                'op_interest_arrears' => $newOpInterest, 'op_tax_arrears' => $newOpTax, 'op_due_amount' => $newOpDue,
                'amount_payable' => $newPayable, 'tax_balance' => $newTax, 'interest_balance' => $newInterest,
                'principal_balance' => $newPrincipal, 'balance_amount' => $newBalance, 'udate' => date('Y-m-d H:i:s'),
            ]);
            $result['saved'] = true;
            $result['rows'] = $ok;
        }

        return $result;
    }
}
