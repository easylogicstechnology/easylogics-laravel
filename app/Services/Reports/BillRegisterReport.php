<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Bill Register (CakePHP account_reports/account_bill_register): one row per bill of this financial year
 * (regular or supplementary) with the tariff-head amounts, arrears, payable, paid, adjustment and balance.
 * Kept as CakePHP does it: members are taken 100 at a time ("Member Record Range"), the unit range shares
 * an OR group with the bill-date range, and "Paid" is every payment the member made for that bill month.
 */
class BillRegisterReport
{
    /** bill summary amount columns read as text (CakePHP's text protocol) */
    private const TEXT_COLUMNS = ['monthly_amount', 'interest_on_due_amount', 'monthly_bill_amount', 'op_principal_arrears', 'op_interest_arrears', 'op_tax_arrears',
        'amount_payable', 'interest_adjusted', 'tax_adjusted', 'principal_adjusted', 'discount'];

    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** Month numbers (April first) that have bills this year, as [monthNo => label] in the society's billing frequency. */
    public function billMonths(): array
    {
        $generated = DB::table('member_bill_summaries')
            ->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)
            ->distinct()->orderBy('month')->pluck('month')->all();
        $out = [];
        foreach ([4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3] as $monthNo) {
            if (in_array($monthNo, $generated)) {
                $label = $this->monthLabel($monthNo);
                if ($label) {
                    $out[$monthNo] = $label;
                }
            }
        }

        return $out;
    }

    private ?array $frequencyList = null;

    /** SocietyBill::monthWordFormatByBillingFrequency: the month label in the society's billing frequency, or false */
    public function monthLabel($monthNo)
    {
        if ($this->frequencyList === null) {
            $row = DB::table('society_parameters')->where('society_id', $this->societyId)->orderBy('id')->first(['billing_frequency_id']);
            $this->frequencyList = $row ? \App\Support\ReportUtil::billingFrequency($row->billing_frequency_id) : [];
        }

        return $this->frequencyList[$monthNo] ?? false;
    }

    /** @return array{data: array, members: array, tariffs: array} */
    public function run(array $in): array
    {
        $summaryQ = DB::table('member_bill_summaries as b')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select('b.*');
        foreach (self::TEXT_COLUMNS as $c) {
            $summaryQ->addSelect(DB::raw("CAST(b.$c AS CHAR) as t_$c"));
        }
        $summaryQ->where('b.society_id', $this->societyId)->where('b.financial_year_id', $this->financialYearId);

        if ($this->val($in, 'building_id') !== '') {
            $summaryQ->where('m.building_id', $this->val($in, 'building_id'));
        }
        if ($this->val($in, 'wing_id') !== '') {
            $summaryQ->where('m.wing_id', $this->val($in, 'wing_id'));
        }
        $or = [];
        $from = $this->val($in, 'flat_no');
        $to = $this->val($in, 'flat_no_to');
        if ($from !== '' && $to !== '') {
            $or[] = ['m.flat_no', [$from, $to]];
        } elseif ($to !== '') {
            $summaryQ->where('m.flat_no', $to);
        } elseif ($from !== '') {
            $summaryQ->where('m.flat_no', $from);
        }

        $fromMonth = $this->val($in, 'from_month');
        $toMonth = $this->val($in, 'to_month');
        $d1 = $this->val($in, 'bill_generated_date');
        $d2 = $this->val($in, 'bill_generated_date_to');
        $paymentDates = null;
        if ($fromMonth !== '') {
            if ($toMonth !== '') {
                if ($fromMonth == $toMonth) {
                    $summaryQ->where('b.month', $fromMonth);
                } else {
                    $range = [];
                    $start = false;
                    foreach ([4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3] as $m) {
                        if ($m == $fromMonth) {
                            $start = true;
                        }
                        if ($start) {
                            $range[] = $m;
                        }
                        if ($m == $toMonth) {
                            break;
                        }
                    }
                    $summaryQ->whereIn('b.month', $range);
                }
            } else {
                $summaryQ->where('b.month', $fromMonth);
            }
        } elseif ($toMonth !== '') {
            $summaryQ->where('b.month', $toMonth);
        } elseif ($d1 !== '' && $d2 !== '') {
            $or[] = ['b.bill_generated_date', [$d1, $d2]];
        } elseif ($d2 !== '') {
            $summaryQ->where('b.bill_generated_date', $d2);
        } elseif ($d1 !== '') {
            $summaryQ->where('b.bill_generated_date', $d1);
        }
        if ($or) {
            $summaryQ->where(function ($w) use ($or) {
                foreach ($or as [$col, $range]) {
                    $w->orWhereBetween($col, $range);
                }
            });
        }

        // members, 100 at a time (page -1 = all)
        $page = $this->val($in, 'member_record_range') !== '' ? $this->val($in, 'member_record_range') : 1;
        $memberQ = DB::table('members')->where('society_id', $this->societyId)->orderBy('id');
        if ($page != '-1') {
            $memberQ->limit(100)->offset(100 * ($page - 1));
        }
        $membersArray = [];
        foreach ($memberQ->get(['id', 'flat_no', 'member_prefix', 'member_name', 'member_transfer']) as $m) {
            $membersArray[$m->id] = (array) $m;
        }
        if ($page != '-1') {
            $summaryQ->whereIn('b.member_id', array_keys($membersArray));
        }

        $billType = $in['bill_type'] ?? null;
        $memberRecordType = (isset($in['member_record']) && $in['member_record'] != '') ? $in['member_record'] : 'Current';
        if ($memberRecordType == 'Old' && !empty($membersArray)) {
            $idents = DB::table('member_identifications')
                ->where('society_id', $this->societyId)->whereIn('member_id', array_keys($membersArray))
                ->orderBy('id')->get(['member_id', 'third_member']);
            foreach ($idents as $oi) {
                if (isset($membersArray[$oi->member_id]) && !empty($oi->third_member)) {
                    $membersArray[$oi->member_id]['member_name'] = $oi->third_member;
                    $membersArray[$oi->member_id]['member_prefix'] = '';
                }
            }
        }
        if ($billType === null || $billType === '') {
            $summaryQ->whereNull('b.bill_type');
        } else {
            $summaryQ->where('b.bill_type', $billType);
        }

        $summaries = $summaryQ->orderBy('b.bill_generated_date')->orderBy('b.bill_no')->get();

        $data = [];
        $billNos = $memberIds = $months = [];
        foreach ($summaries as $s) {
            $s = (array) $s;
            $curT = $membersArray[$s['member_id']]['member_transfer'] ?? 0;
            $billT = $s['member_transfer'] ?? 0;
            if ($memberRecordType == 'Old') {
                if (!($billT < $curT)) {
                    continue;
                }
            } elseif ($billT != $curT) {
                continue;
            }
            // the amount columns are the printed text
            foreach (self::TEXT_COLUMNS as $c) {
                $s[$c] = $s['t_' . $c];
                unset($s['t_' . $c]);
            }
            $data[$s['id']] = ['MemberBillSummary' => $s, 'MemberBillGenerate' => [], 'amountPaid' => '0.00'];
            $billNos[] = $s['bill_no'];
            $memberIds[] = $s['member_id'];
            $months[] = $s['month'];
        }

        $tariffs = [];
        if (!empty($data)) {
            $billNos = array_values(array_unique($billNos));
            $memberIds = array_values(array_unique($memberIds));
            $months = array_values(array_unique($months));

            $byBill = [];
            foreach (array_chunk($memberIds, 400) as $idChunk) {
                $rows = DB::table('member_bill_generates as g')
                    ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
                    ->select('g.member_id', 'g.bill_number', 'g.ledger_head_id', 'h.title', DB::raw('CAST(g.amount AS CHAR) as amount'))
                    ->where('g.society_id', $this->societyId)->whereIn('g.member_id', $idChunk)->whereIn('g.bill_number', $billNos)
                    ->where('g.bill_type', $billType)->where('g.financial_year_id', $this->financialYearId)
                    ->orderBy('g.id')->get();
                foreach ($rows as $t) {
                    $byBill[$t->member_id . '_' . $t->bill_number][] = $t;
                    if (!array_key_exists($t->ledger_head_id, $tariffs)) {
                        $tariffs[$t->ledger_head_id] = $t->title;
                    }
                }
            }
            foreach ($data as $id => $d) {
                $b = $d['MemberBillSummary'];
                foreach ($byBill[$b['member_id'] . '_' . $b['bill_no']] ?? [] as $t) {
                    $data[$id]['MemberBillGenerate'][$t->ledger_head_id] = $t->amount;
                }
            }

            $paid = [];
            foreach (array_chunk($memberIds, 400) as $idChunk) {
                $rows = DB::table('member_payments')
                    ->select('member_id', 'bill_month', DB::raw('sum(amount_paid) as amount_paid'))
                    ->whereIn('member_id', $idChunk)->whereIn('bill_month', $months)
                    ->groupBy('member_id', 'bill_month')->get();
                foreach ($rows as $p) {
                    $paid[$p->member_id . '_' . $p->bill_month] = $p->amount_paid;
                }
            }
            foreach ($data as $id => $d) {
                $b = $d['MemberBillSummary'];
                $key = $b['member_id'] . '_' . $b['month'];
                if (isset($paid[$key])) {
                    $data[$id]['amountPaid'] = number_format((float) $paid[$key], 2, '.', '');
                }
            }
        }

        return ['data' => $data, 'members' => $membersArray, 'tariffs' => $tariffs];
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
