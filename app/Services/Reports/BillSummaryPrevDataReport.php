<?php

namespace App\Services\Reports;

use App\Support\ReportUtil;
use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * Bill Summary With PrevData (CakePHP account_reports/bill_summary_with_prev_data): one bill block for EVERY member of the
 * society - previous bills / received / balance / current bill, the current charges, an annual summary and the receipts.
 *
 * Ported as CakePHP runs it on PHP 7.4, its slips included (they are listed in the report notes): the interest columns are
 * read under a mistyped name so they are always 0, the journal-voucher figures never match so they are always 0, and the
 * returned-cheque list is not applied inside the monthly payment total (it is already left out of the payment query).
 */
class BillSummaryPrevDataReport
{
    public function __construct(private int $societyId)
    {
    }

    /**
     * @param  array  $req  month (as picked, e.g. "4"), bill_type ('reg' / 'sup', blank = 'reg')
     * @return array [member id => report data]
     */
    public function run(array $req): array
    {
        $out = [];
        // no ORDER BY, as CakePHP (id + flat_no are read): members come in the order the database returns them
        $ids = DB::table('members')->where('society_id', $this->societyId)->get(['id', 'flat_no'])->pluck('id');
        foreach ($ids as $memberId) {
            $out[$memberId] = $this->member($memberId, $req);
        }

        return $out;
    }

    private function member($memberId, array $req): array
    {
        $report = [];
        $member = DB::table('members')->select(TextSelect::columns('members'))
            ->where('id', $memberId)->where('society_id', $this->societyId)->where('status', 1)->first();
        $member = $member ? (array) $member : null;

        $info = [];
        $openingBalance = 0;
        if ($member) {
            $society = DB::table('societies')->select(TextSelect::columns('societies'))->where('id', $member['society_id'])->first();
            $society = $society ? (array) $society : [];
            foreach (['society_name', 'registration_no', 'address'] as $k) {
                if (isset($society[$k])) {
                    $society[$k] = ReportUtil::plain($society[$k]);
                }
            }
            $info = [
                'Member' => $member,
                'Society' => $society,
                'MemberPayment' => DB::table('member_payments')->select(TextSelect::columns('member_payments'))
                    ->where('member_id', $memberId)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            ];
            // op_principal + op_interest + op_interest, where the CakePHP controller reads both interests from a variable that does not exist (0)
            $openingBalance = $member['op_principal'] + 0 + 0;
        }
        $report['member_information'] = $info;

        $memberTransfer = $member['member_transfer'] ?? 0;
        $report['member_transfered'] = $memberTransfer;
        $billType = !BillPrintReport::phpEmpty($req['bill_type'] ?? null) ? $req['bill_type'] : 'reg';

        $bills = DB::table('member_bill_summaries')->select(TextSelect::columns('member_bill_summaries'))
            ->where('society_id', $this->societyId)->where('member_id', $memberId)
            ->where('member_transfer', $memberTransfer)->where('bill_type', $billType)
            ->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        if (empty($bills)) {
            return $report;
        }

        $currentYear = date('Y', strtotime($bills[0]['bill_generated_date']));
        if (date('m', strtotime($bills[0]['bill_generated_date'])) < 4) {
            $currentYear = $currentYear - 1;
        }
        $nextYear = $currentYear + 1;

        $month = $req['month'] ?? '';
        $filteredMonth = $month;
        if (!BillPrintReport::phpEmpty($filteredMonth) && $filteredMonth <= 9) {
            $filteredMonth = '0' . $filteredMonth;
        }
        $customerDate = $filteredMonth < 4 ? "$nextYear-$filteredMonth-01" : "$currentYear-$filteredMonth-01";

        $months = [];
        foreach ([4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $no => $name) {
            $months[$no] = ['month' => $name, 'date' => date('d-m-Y', strtotime("$currentYear-" . str_pad((string) $no, 2, '0', STR_PAD_LEFT) . '-01'))];
        }
        foreach ([1 => 'January', 2 => 'February', 3 => 'March'] as $no => $name) {
            $months[$no] = ['month' => $name, 'date' => date('d-m-Y', strtotime("$nextYear-0$no-01"))];
        }

        $report['generated_bill_tariffs'] = DB::table('member_bill_generates as g')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
            ->select(DB::raw('CAST(g.amount AS CHAR) as amount'), 'h.title')
            ->where('g.member_id', $memberId)->where('g.society_id', $this->societyId)->where('g.month', $month)
            ->get()->map(fn ($r) => ['MemberBillGenerate' => ['amount' => $r->amount], 'SocietyLedgerHeads' => ['title' => $r->title]])->all();

        $returned = DB::table('cheque_return_details')
            ->where('member_id', $memberId)->where('society_id', $this->societyId)
            ->pluck('payment_id')->all();
        $payments = DB::table('member_payments')->select(TextSelect::columns('member_payments'))
            ->where('society_id', $this->societyId)->where('member_id', $memberId)->where('member_transfer', $memberTransfer);
        if (!empty($returned)) {
            $payments->whereNotIn('id', $returned);
        }
        $payments = $payments->get()->map(fn ($r) => (array) $r)->all();

        $report['bill_current_prev_data'] = $this->currentAndPrev($bills, $openingBalance, $month, $currentYear, $nextYear);

        $totalPaidTillBillDate = 0;
        foreach ($months as $monthId => $m) {
            $date = date('Y-m-d', strtotime($m['date']));
            $months[$monthId]['bill_amount'] = $this->billAmount($date, $bills);
            $months[$monthId]['paid_amount'] = $this->paidInMonth($date, $payments);
            // the voucher figures compare a year with a full date and a date with "Credit"/"Debit": nothing ever matches
            $months[$monthId]['jv_credited'] = 0;
            $months[$monthId]['jv_debited'] = 0;
            if (strtotime($date) == strtotime($customerDate)) {
                $totalPaidTillBillDate = $totalPaidTillBillDate + ($months[$monthId]['paid_amount'] + $months[$monthId]['jv_credited'] - $months[$monthId]['jv_debited']);
            }
        }
        $report['bill_ledger_data'] = $months;
        $report['total_paid_till_bill_date'] = $totalPaidTillBillDate;

        return $report;
    }

    private function currentAndPrev(array $bills, $openingBalance, $month, $currentYear, $nextYear): array
    {
        $filteredMonth = $month;
        $customerDate = $filteredMonth < 4 ? "$nextYear-$filteredMonth-01" : "$currentYear-$filteredMonth-01";

        $out = [];
        $prevPrinciple = $prevInterest = $prevTax = 0;
        $totalPrincipalPaid = $totalInterestPaid = $totalTaxPaid = 0;
        $totalBal = 0;
        foreach ($bills as $bill) {
            $billDate = $bill['bill_generated_date'];
            $tax = $bill['tax_total'];
            $interest = $bill['interest_on_due_amount'];
            $balAmt = $bill['balance_amount'];
            $billMonth = $bill['month'];
            $principle = $bill['monthly_principal_amount'] - $tax;

            if (strtotime($customerDate) > strtotime($billDate)) {
                if ($filteredMonth != 4 && $billMonth == 4) {
                    $principle = $principle + $openingBalance;
                }
                $prevPrinciple = $prevPrinciple + $principle;
                $prevInterest = $prevInterest + $interest;
                $prevTax = $prevTax + $tax;

                $totalPrincipalPaid = $totalPrincipalPaid + $bill['principal_paid'];
                $totalInterestPaid = $totalInterestPaid + $bill['interest_paid'];
                $totalTaxPaid = $totalTaxPaid + $bill['tax_paid'];

                $totalBal = ($balAmt < 0) ? $totalBal + abs($balAmt) : $totalBal;

                $out['prev_bill_data'] = [
                    'principle' => $prevPrinciple,
                    'tax' => $prevTax,
                    'interest' => $prevInterest,
                    'principle_paid' => $totalPrincipalPaid,
                    'tax_paid' => $totalTaxPaid,
                    'interest_paid' => $totalInterestPaid,
                    'total_bal_amt' => $totalBal,
                ];
            }

            if ($filteredMonth == $billMonth) {
                if ($filteredMonth == 4) {
                    $principle = $principle + $openingBalance;
                }
                $out['current_bill_data'] = [
                    'principle' => $principle,
                    'tax' => $tax,
                    'interest' => $interest,
                    'bill_no' => $bill['bill_no'],
                    'bill_generated_date' => $billDate,
                    'bill_due_date' => $bill['bill_due_date'],
                ];
            }
        }

        return $out;
    }

    /** monthly_amount of the first bill generated in the calendar month of $date (0 when none) */
    private function billAmount(string $date, array $bills)
    {
        $m = date('m', strtotime($date));
        $y = date('Y', strtotime($date));
        foreach ($bills as $b) {
            if ($y == date('Y', strtotime($b['bill_generated_date'])) && $m == date('m', strtotime($b['bill_generated_date']))) {
                return $b['monthly_amount'];
            }
        }

        return 0;
    }

    /** the member's payments made in the calendar month of $date */
    private function paidInMonth(string $date, array $payments)
    {
        $paid = 0;
        $m = date('m', strtotime($date));
        $y = date('Y', strtotime($date));
        foreach ($payments as $p) {
            if ($y == date('Y', strtotime($p['payment_date'])) && $m == date('m', strtotime($p['payment_date']))) {
                $paid = $paid + $p['amount_paid'];
            }
        }

        return $paid;
    }
}
