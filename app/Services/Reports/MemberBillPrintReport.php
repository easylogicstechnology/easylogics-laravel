<?php

namespace App\Services\Reports;

use App\Support\ReportUtil;
use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * The member bills of the "Print Bill" / "Bill With Receipt Tabular" screen (CakePHP society_bills/print_member_bills): the
 * bills of one bill month for a bill-number / unit range, each with its charges, its member, society, wing and the member's
 * receipts. The data reaches the carried-over bill view in the same shape CakePHP hands it.
 *
 * Kept as CakePHP does it: an unit number and a bill-number RANGE sit in one OR group (a bill matching either is listed), an
 * empty bill month means month 1, and the April bill's receipts are those of March unless a receipt period is given.
 */
class MemberBillPrintReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /**
     * @param  array  $q  the screen's query: month, bill_from, bill_to, bill_type, member_record, flat_no, receipt_from, receipt_to, billFormat
     */
    public function run(array $q): array
    {
        $helper = new BillPrintReport($this->societyId, $this->financialYearId);
        $isSet = fn ($k) => array_key_exists($k, $q) && $q[$k] !== null;
        $filled = fn ($k) => $isSet($k) && !BillPrintReport::phpEmpty($q[$k]);

        $billMonth = $filled('month') ? $q['month'] : 1;
        $from = $filled('bill_from') ? $q['bill_from'] : '';
        $to = $filled('bill_to') ? $q['bill_to'] : '';
        $billType = $filled('bill_type') ? $q['bill_type'] : 'reg';
        $recordType = ($isSet('member_record') && $q['member_record'] != '') ? $q['member_record'] : 'Current';
        $transferOf = DB::table('members')->where('society_id', $this->societyId)->pluck('member_transfer', 'id')->all();

        $banks = DB::table('banks')->orderBy('id')->pluck('bank_name', 'id')->all();
        $societyParameters = $helper->societyParameters();
        $titles = $helper->tariffTitles();

        $summary = DB::table('member_bill_summaries as b')
            ->leftJoin('societies as s', 's.id', '=', 'b.society_id')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select(array_merge(TextSelect::columns('member_bill_summaries', 'b'), TextSelect::columns('societies', 's', 's__'), TextSelect::columns('members', 'm', 'm__')))
            ->where('b.society_id', $this->societyId)->where('b.month', $billMonth)->where('b.bill_type', $billType);
        if ($recordType != 'Old') {
            $summary->where('b.financial_year_id', $this->financialYearId);
        }

        $or = [];
        if ($filled('flat_no')) {
            $or[] = ['=', 'b.flat_no', $q['flat_no']];
        } elseif ($filled('bill_generated_date')) {
            $or[] = ['=', 'b.bill_generated_date', $q['bill_generated_date']];
        }
        if ($from !== '' && $to !== '') {
            $or[] = ['between', 'b.bill_no', [$from, $to]];
        } elseif ($to !== '') {
            $summary->where('b.bill_no', $to);
        } elseif ($from !== '') {
            $summary->where('b.bill_no', $from);
        }
        if ($or) {
            $summary->where(function ($w) use ($or) {
                foreach ($or as [$op, $col, $val]) {
                    $op === 'between' ? $w->orWhereBetween($col, $val) : $w->orWhere($col, $val);
                }
            });
        }

        $oldOwner = [];
        $details = [];
        $bills = $summary->orderBy('b.bill_no')->get();
        if ($bills->isNotEmpty()) {
            if ($recordType == 'Old') {
                foreach (DB::table('member_identifications')->where('society_id', $this->societyId)->orderBy('id')->get(['member_id', 'third_member']) as $i) {
                    if (!empty($i->third_member)) {
                        $oldOwner[$i->member_id] = $i->third_member;
                    }
                }
            }
            foreach ($bills as $r) {
                $bill = $this->split((array) $r);
                $memberId = $bill['MemberBillSummary']['member_id'];
                $billTransfer = (int) $bill['MemberBillSummary']['member_transfer'];
                $currentTransfer = isset($transferOf[$memberId]) ? (int) $transferOf[$memberId] : 0;
                if ($recordType == 'Old') {
                    if ($billTransfer == $currentTransfer) {
                        continue;
                    }
                } elseif ($billTransfer != $currentTransfer) {
                    continue;
                }
                $key = $bill['MemberBillSummary']['id'];
                $billFy = $bill['MemberBillSummary']['financial_year_id'];
                $details[$key] = $bill;
                $details[$key]['tariff'] = $this->tariff($billFy, $billMonth, $memberId, $billType);
                $details[$key]['memberDetails'] = $this->memberDetails($memberId);
                if ($recordType == 'Old' && isset($oldOwner[$memberId])) {
                    $details[$key]['Member']['member_name'] = $oldOwner[$memberId];
                    $details[$key]['Member']['member_prefix'] = '';
                    $details[$key]['Member']['joint_member_name'] = '';
                }

                $rf = $q['receipt_from'] ?? null;
                $rt = $q['receipt_to'] ?? null;
                if ($bill['MemberBillSummary']['month'] == 4) {
                    // the April bill shows the receipts of March, unless the screen names a period
                    $details[$key]['receipts'] = $helper->payments(
                        ['member_id' => $memberId, 'bill_type' => $billType, 'member_transfer' => $billTransfer],
                        [$rf !== null ? $rf : date('Y') . '-03-01', $rt !== null ? $rt : date('Y') . '-03-31']
                    );
                } elseif (!BillPrintReport::phpEmpty($rf) && !BillPrintReport::phpEmpty($rt)) {
                    $details[$key]['receipts'] = $helper->payments(
                        ['member_id' => $memberId, 'financial_year_id' => $billFy, 'member_transfer' => $billTransfer, 'bill_type' => $billType],
                        [$rf, $rt]
                    );
                } else {
                    $details[$key]['receipts'] = $helper->payments(
                        ['member_id' => $memberId, 'financial_year_id' => $billFy, 'member_transfer' => $billTransfer, 'bill_type' => $billType]
                    );
                }
            }
        }

        return [
            'ledgerHeadDetails' => $this->billHeads(),
            'printBillDetails' => $details,
            'societyParameters' => ['SocietyParameter' => $societyParameters],
            'societyLedgerHeadTitleList' => $titles,
            'banks' => $banks,
            'receiptPeriod' => ['From' => $q['receipt_from'] ?? null, 'To' => $q['receipt_to'] ?? null],
            'billFormat' => (isset($q['billFormat']) && $q['billFormat'] == 'half') ? 'half' : 'full',
        ];
    }

    /** the society / member columns the bill row was joined with, under their model names */
    private function split(array $r): array
    {
        $row = ['MemberBillSummary' => [], 'Society' => [], 'Member' => []];
        foreach ($r as $col => $v) {
            if (str_starts_with($col, 's__')) {
                $row['Society'][substr($col, 3)] = $v;
            } elseif (str_starts_with($col, 'm__')) {
                $row['Member'][substr($col, 3)] = $v;
            } else {
                $row['MemberBillSummary'][$col] = $v;
            }
        }

        return $row;
    }

    /** MemberBillGenerate of the bill (with its ledger head and member joined, as CakePHP reads it), as ['MemberBillGenerate' => [..]] */
    private function tariff($fy, $month, $memberId, $billType): array
    {
        return DB::table('member_bill_generates as g')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
            ->leftJoin('members as mm', 'mm.id', '=', 'g.member_id')
            ->select(TextSelect::columns('member_bill_generates', 'g'))
            ->where('g.society_id', $this->societyId)->where('g.financial_year_id', $fy)->where('g.month', $month)
            ->where('g.member_id', $memberId)->where('g.bill_type', $billType)
            ->get()->map(fn ($r) => ['MemberBillGenerate' => (array) $r])->all();
    }

    /** Member::find('first') with its society, building and wing */
    private function memberDetails($memberId): array
    {
        $m = DB::table('members as m')
            ->leftJoin('societies as s', 's.id', '=', 'm.society_id')
            ->leftJoin('buildings as bd', 'bd.id', '=', 'm.building_id')
            ->leftJoin('wings as w', 'w.id', '=', 'm.wing_id')
            ->select(array_merge(TextSelect::columns('members', 'm', 'm__'), TextSelect::columns('societies', 's', 's__'), TextSelect::columns('buildings', 'bd', 'b__'), TextSelect::columns('wings', 'w', 'w__')))
            ->where('m.id', $memberId)->first();
        $out = ['Member' => [], 'Society' => [], 'Building' => [], 'Wing' => []];
        foreach ((array) $m as $col => $v) {
            $target = ['m__' => 'Member', 's__' => 'Society', 'b__' => 'Building', 'w__' => 'Wing'][substr($col, 0, 3)];
            $out[$target][substr($col, 3)] = $v;
        }
        foreach (['society_name', 'registration_no', 'address'] as $k) {
            if (isset($out['Society'][$k])) {
                $out['Society'][$k] = ReportUtil::plain($out['Society'][$k]);
            }
        }

        return $out;
    }

    /** the ledger heads that are part of the bill: [id => title] */
    private function billHeads(): array
    {
        $out = [];
        $hasOrder = DB::table('society_tariff_orders')->where('society_id', $this->societyId)->exists();
        if ($hasOrder) {
            $rows = DB::table('society_tariff_orders as o')
                ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'o.ledger_head_id')
                ->where('h.status', 1)->where('h.society_id', $this->societyId)
                ->orderBy('o.id')->get(['h.title', 'h.id']);
        } else {
            $rows = DB::table('society_ledger_heads as h')
                ->where('h.status', 1)->where('h.society_id', $this->societyId)->where('h.is_in_bill_charges', 1)
                ->orderBy('h.title')->get(['h.title', 'h.id']);
        }
        foreach ($rows as $r) {
            $out[$r->id] = $r->title;
        }

        return $out;
    }
}
