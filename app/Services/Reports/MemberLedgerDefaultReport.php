<?php

namespace App\Services\Reports;

use App\Support\CakeUtil;
use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * Member Ledger Default (CakePHP account_reports/account_member_ledger_default): for each member of the
 * selected record range, the bills, receipts and journal vouchers of the year in date order. This builds
 * the same array the CakePHP controller hands to its view ($accountMemberLedgerDetails, $memberArr); the
 * view then does the running-balance maths.
 */
class MemberLedgerDefaultReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** @return array{ledger: array, memberArr: array} */
    public function run(array $in): array
    {
        $reportType = $in['report_type'] ?? 'Default';
        $memberRecord = $in['member_record'] ?? null;
        $old = $memberRecord == 'Old';

        // members of the range: [id => member_transfer]
        $page = isset($in['member_record_range']) ? $in['member_record_range'] : 1;
        $q = DB::table('members')->where('status', 1)->where('society_id', $this->societyId)->orderBy('id');
        if ($page > 0) {
            $q->limit(100)->offset(100 * ($page - 1));
        }
        $societyMemberList = $q->pluck('member_transfer', 'id')->all();

        if ($old) {
            foreach ($societyMemberList as $key => $value) {
                if ($value - 1 <= -1) {
                    unset($societyMemberList[$key]);
                }
            }
        }

        $opPrincipal = 'op_principal';
        $opTax = 'op_tax';
        $opInterest = 'op_interest';
        $closing = [];
        if ($reportType == 'reg' || $reportType == 'Summary' || $reportType == 'Default') {
            $closing = $this->lastClosingBalance('reg');
        }
        if ($reportType == 'sup') {
            $closing = $this->lastClosingBalance('sup');
            $opPrincipal = 'supplementary_principal';
            $opTax = 'supplementary_tax';
            $opInterest = 'supplementary_interest';
        }

        // every member of the society (all statuses), the opening figures replaced by last year's closing
        $memberArr = [];
        foreach (DB::table('members')->select(TextSelect::columns('members'))->where('society_id', $this->societyId)->get() as $m) {
            $m = (array) $m;
            $memberArr[$m['id']] = $m;
            if (!empty($closing[$m['id']])) {
                $memberArr[$m['id']][$opPrincipal] = $closing[$m['id']]['principal_balance'];
                $memberArr[$m['id']][$opTax] = $closing[$m['id']]['tax_balance'];
                $memberArr[$m['id']][$opInterest] = $closing[$m['id']]['interest_balance'];
            }
        }

        $ledger = [];
        if (count($societyMemberList) > 0) {
            $ids = array_keys($societyMemberList);
            $expected = [];
            foreach ($societyMemberList as $mid => $mt) {
                if ($old) {
                    $mt = $mt - 1;
                    if ($mt <= -1) {
                        $mt = 0;
                    }
                }
                $expected[$mid] = $mt;
            }

            $identifications = [];
            if ($old) {
                $rows = DB::table('member_identifications')->select(TextSelect::columns('member_identifications'))
                    ->where('status', 1)->where('society_id', $this->societyId)->whereIn('member_id', $ids)
                    ->orderBy('flat_no')->get();
                foreach ($rows as $mi) {
                    $mi = (array) $mi;
                    if (isset($expected[$mi['member_id']]) && $mi['member_transfer'] == $expected[$mi['member_id']]) {
                        $identifications[$mi['member_id']] = $mi;
                    }
                }
                foreach ($identifications as $mid => $mi) {
                    if (isset($memberArr[$mid])) {
                        $memberArr[$mid]['member_name'] = $mi['third_member'];
                        $memberArr[$mid]['op_principal'] = $mi['op_principal'] ?? null; // member_identifications has no such column
                        $memberArr[$mid]['op_interest'] = $mi['op_interest'] ?? null; // member_identifications has no such column
                        $memberArr[$mid]['op_tax'] = $mi['op_tax'] ?? null; // member_identifications has no such column
                    }
                }
            }

            // journal vouchers of the current year, date order
            $jvs = DB::table('journal_vouchers')->select(TextSelect::columns('journal_vouchers'))
                ->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)
                ->orderBy('voucher_date')->get()->map(fn ($r) => (array) $r)->all();

            // the bills
            $bq = DB::table('member_bill_summaries as b')
                ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
                ->select(array_merge(TextSelect::columns('member_bill_summaries', 'b'), TextSelect::columns('members', 'm', 'mem__')))
                ->where('b.society_id', $this->societyId);
            $this->billFilters($bq, $in, $reportType);
            $bq->whereIn('b.member_id', $ids);
            if (!$old) {
                $bq->where('b.financial_year_id', $this->financialYearId);
            }
            $billsByMember = [];
            foreach ($bq->orderBy('b.member_id')->orderBy('b.bill_no')->get() as $row) {
                $row = (array) $row;
                $member = [];
                $bill = [];
                foreach ($row as $k => $v) {
                    if (str_starts_with($k, 'mem__')) {
                        $member[substr($k, 5)] = $v;
                    } else {
                        $bill[$k] = $v;
                    }
                }
                $mid = $bill['member_id'];
                if (isset($expected[$mid]) && $bill['member_transfer'] == $expected[$mid]) {
                    $billsByMember[$mid][] = ['MemberBillSummary' => $bill, 'Member' => $member];
                }
            }

            // the payments
            $pq = DB::table('member_payments as p')->select(TextSelect::columns('member_payments', 'p'))
                ->where('p.society_id', $this->societyId)->whereIn('p.member_id', $ids);
            if (!$old) {
                $pq->where('p.financial_year_id', $this->financialYearId);
            }
            if (isset($in['report_type']) && $in['report_type'] != 'Default') {
                $pBillType = $in['report_type'];
                if ($pBillType == 'Summary') {
                    $pBillType = 'reg';
                }
                $pq->where('p.bill_type', $pBillType);
            }
            $paymentsByMember = [];
            $paymentIds = [];
            foreach ($pq->get() as $row) {
                $row = (array) $row;
                if (isset($expected[$row['member_id']]) && $row['member_transfer'] == $expected[$row['member_id']]) {
                    $paymentsByMember[$row['member_id']][] = $row;
                    $paymentIds[] = $row['id'];
                }
            }
            $settleByPayment = [];
            foreach (array_chunk($paymentIds, 1000) as $chunk) {
                foreach (DB::table('member_bill_settlements')->select('payment_id', DB::raw('CAST(principal_paid AS CHAR) as principal_paid'), DB::raw('CAST(interest_paid AS CHAR) as interest_paid'), DB::raw('CAST(tax_paid AS CHAR) as tax_paid'))
                    ->whereIn('payment_id', $chunk)->orderBy('id')->get() as $s) {
                    $pid = $s->payment_id;
                    if (!isset($settleByPayment[$pid])) {
                        $settleByPayment[$pid] = ['principal_paid' => 0, 'interest_paid' => 0, 'tax_paid' => 0];
                    }
                    $settleByPayment[$pid]['principal_paid'] += $s->principal_paid;
                    $settleByPayment[$pid]['interest_paid'] += $s->interest_paid;
                    $settleByPayment[$pid]['tax_paid'] += $s->tax_paid;
                }
            }

            $util = new CakeUtil();
            foreach ($societyMemberList as $memberId => $transferRaw) {
                $transfer = $expected[$memberId];
                $bills = $billsByMember[$memberId] ?? [];
                $summaryCounter = 0;
                if (!empty($bills)) {
                    $identity = $identifications[$memberId] ?? null;
                    foreach ($bills as $billData) {
                        $ledger[$memberId]['MemberBillSummary'][$summaryCounter] = $billData['MemberBillSummary'];
                        $ledger[$memberId]['Member'] = $billData['Member'];
                        if (!empty($identity) && $old) {
                            $ledger[$memberId]['Member']['member_name'] = $identity['third_member'];
                            $ledger[$memberId]['Member']['op_principal'] = $identity['op_principal'] ?? null;
                            $ledger[$memberId]['Member']['op_interest'] = $identity['op_interest'] ?? null;
                            $ledger[$memberId]['Member']['op_tax'] = $identity['op_tax'] ?? null;
                        }
                        $ledger[$memberId]['MemberBillSummary'][$summaryCounter]['payment_date'] = date('d/m/Y', strtotime($billData['MemberBillSummary']['bill_generated_date']));
                        if (isset($billData['MemberBillSummary']['balance_amount']) && $billData['MemberBillSummary']['balance_amount'] > 0) {
                            $ledger[$memberId]['MemberBillSummary'][$summaryCounter]['debit_credit_value'] = 'DR';
                        } else {
                            $ledger[$memberId]['MemberBillSummary'][$summaryCounter]['debit_credit_value'] = 'CR';
                        }
                        $ledger[$memberId]['MemberBillSummary'][$summaryCounter]['type'] = 'member_bill_summary';
                        $ledger[$memberId]['MemberBillSummary'][$summaryCounter]['monthName'] = $util->monthWordFormatByBillingFrequency($billData['MemberBillSummary']['month']);
                        $summaryCounter++;
                    }
                }

                foreach ($paymentsByMember[$memberId] ?? [] as $payment) {
                    $key = $payment['bill_generated_id'] . $payment['member_id'] . $payment['receipt_id'];
                    $ledger[$memberId]['MemberBillSummary'][$key] = $payment;
                    $ledger[$memberId]['MemberBillSummary'][$key]['payment_date'] = date('d/m/Y', strtotime($payment['payment_date']));
                    $ledger[$memberId]['MemberBillSummary'][$key]['type'] = 'member_payments';
                    $pid = $payment['id'];
                    if (isset($settleByPayment[$pid])) {
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_principal'] = $settleByPayment[$pid]['principal_paid'];
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_interest'] = $settleByPayment[$pid]['interest_paid'];
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_tax'] = $settleByPayment[$pid]['tax_paid'];
                    } else {
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_principal'] = $payment['amount_paid'];
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_interest'] = 0;
                        $ledger[$memberId]['MemberBillSummary'][$key]['paid_tax'] = 0;
                    }
                }

                $memberJvs = [];
                foreach ($jvs as $jv) {
                    if ($jv['jv_credit_member_head_id'] == $memberId || $jv['jv_debit_member_head_id'] == $memberId) {
                        if ($transfer == 0 || (isset($jv['member_transfer']) && $jv['member_transfer'] == $transfer)) {
                            $memberJvs[] = $jv;
                        }
                    }
                }
                if (!empty($memberJvs)) {
                    $slot = $summaryCounter + 1;
                    foreach ($memberJvs as $jv) {
                        if ($jv['jv_credit_member_head_id'] != '' && $jv['jv_credit_member_head_id'] == $memberId) {
                            $this->jvSlot($ledger[$memberId]['MemberBillSummary'][$slot], $jv, $memberId, 'credit');
                        }
                        if ($jv['jv_debit_member_head_id'] != '' && $jv['jv_debit_member_head_id'] == $memberId) {
                            $this->jvSlot($ledger[$memberId]['MemberBillSummary'][$slot], $jv, $memberId, 'debit');
                        }
                        $slot++;
                    }
                }

                if (isset($ledger[$memberId]['MemberBillSummary'])) {
                    uasort($ledger[$memberId]['MemberBillSummary'], function ($a, $b) {
                        return $this->stamp($a['payment_date']) - $this->stamp($b['payment_date']);
                    });
                }
            }
        }

        return ['ledger' => $ledger, 'memberArr' => $memberArr];
    }

    private function stamp(string $dmY): int
    {
        return \DateTime::createFromFormat('d/m/Y', $dmY, new \DateTimeZone('UTC'))->getTimestamp();
    }

    /** the fields the CakePHP controller writes for a journal-voucher row of the ledger */
    private function jvSlot(?array &$slot, array $jv, $memberId, string $side): void
    {
        $credit = $side === 'credit';
        $amountKey = $credit ? 'jv_amount_credited' : 'jv_amount_debited';
        $slot ??= [];
        $slot['amount_paid'] = isset($jv[$amountKey]) ? $jv[$amountKey] : 0.00;
        $slot['payment_date'] = date('d/m/Y', strtotime($jv['voucher_date']));
        $slot['month'] = date('n', strtotime($jv['voucher_date']));
        $slot['member_id'] = $memberId;
        $slot['cheque_reference_number'] = '-';
        $slot['particulars'] = ($credit ? 'JV Credited  ' : 'JV Debited  ') . $jv['note'];
        $slot['voucher_no'] = $jv['voucher_no'];
        $slot['type'] = $credit ? 'Credit' : 'Debit';
        $slot['types'] = 'JV';
    }

    /** the bill-summary conditions built from the form (CakePHP: $options) */
    private function billFilters($q, array $in, string $reportType): void
    {
        if ($this->val($in, 'building_id') !== '') {
            $q->where('m.building_id', $this->val($in, 'building_id'));
        }
        if (isset($in['report_type']) && $in['report_type'] != 'Default') {
            $q->where('b.bill_type', $in['report_type'] == 'Summary' ? 'reg' : $in['report_type']);
        }
        if ($this->val($in, 'wing_id') !== '') {
            $q->where('m.wing_id', $this->val($in, 'wing_id'));
        }
        $from = $this->val($in, 'flat_no');
        $to = $this->val($in, 'flat_no_to');
        if ($from !== '' && $to !== '') {
            $q->whereBetween('m.flat_no', [$from, $to]);
        } elseif ($to !== '') {
            $q->where('m.flat_no', $to);
        } elseif ($from !== '') {
            $q->where('m.flat_no', $from);
        }
        $d1 = $this->val($in, 'payment_date');
        $d2 = $this->val($in, 'payment_date_to');
        if ($d1 !== '' && $d2 !== '') {
            $q->whereBetween('b.bill_generated_date', [$d1, $d2]);
        } elseif ($d1 !== '') {
            $q->where('b.bill_generated_date', $d2);
        } elseif ($d2 !== '') {
            $q->where('b.bill_generated_date', $d2);
        }
    }

    /** SocietyBill::getMemberslastClosingBalance: last year's closing rows of the society, by member */
    private function lastClosingBalance(string $billType): array
    {
        $year = ($this->financialYearId ?? 0) - 1;
        $out = [];
        $rows = DB::table('member_year_wise_closing_balance')->select(TextSelect::columns('member_year_wise_closing_balance'))
            ->where('society_id', $this->societyId)->where('year_id', $year)->where('bill_type', $billType)->get();
        foreach ($rows as $r) {
            $out[$r->member_id] = (array) $r;
        }

        return $out;
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
