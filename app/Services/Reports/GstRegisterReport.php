<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * GST Register (CakePHP account_reports/account_gst_register): the bills of this financial year with the
 * amount / CGST / SGST of every tax-applicable tariff head on them.
 * Kept as CakePHP builds it: the unit range and the bill-date range share one OR group (so together they
 * widen the result); it runs on a plain page open too.
 */
class GstRegisterReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** @return array{data: array, members: array, tariffs: array, params: ?object} */
    public function run(array $in): array
    {
        $q = DB::table('member_bill_summaries as b')
            ->leftJoin('societies as s', 's.id', '=', 'b.society_id')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select('b.id', 'b.bill_no', 'b.bill_generated_date', 'b.member_id', 's.gstin_no')
            ->where('b.society_id', $this->societyId)
            ->where('b.financial_year_id', $this->financialYearId);

        if ($this->val($in, 'building_id') !== '') {
            $q->where('m.building_id', $this->val($in, 'building_id'));
        }
        if ($this->val($in, 'wing_id') !== '') {
            $q->where('m.wing_id', $this->val($in, 'wing_id'));
        }

        $or = [];
        $from = $this->val($in, 'flat_no');
        $to = $this->val($in, 'flat_no_to');
        if ($from !== '' && $to !== '') {
            $or[] = ['m.flat_no', [$from, $to]];
        } elseif ($to !== '') {
            $q->where('m.flat_no', $to);
        } elseif ($from !== '') {
            $q->where('m.flat_no', $from);
        }
        $d1 = $this->val($in, 'bill_generated_date');
        $d2 = $this->val($in, 'bill_generated_date_to');
        if ($d1 !== '' && $d2 !== '') {
            $or[] = ['b.bill_generated_date', [$d1, $d2]];
        } elseif ($d2 !== '') {
            $q->where('b.bill_generated_date', $d2);
        } elseif ($d1 !== '') {
            $q->where('b.bill_generated_date', $d1);
        }
        if ($or) {
            $q->where(function ($w) use ($or) {
                foreach ($or as [$col, $range]) {
                    $w->orWhereBetween($col, $range);
                }
            });
        }

        $bills = $q->orderBy('b.bill_generated_date')->orderBy('b.bill_no')->get();

        $members = [];
        foreach (DB::table('members')->where('society_id', $this->societyId)->orderBy('id')->get(['id', 'flat_no', 'member_prefix', 'member_name', 'gstin_no']) as $m) {
            $members[$m->id] = (array) $m;
        }

        // tax-applicable tariff lines of those bills, per member + bill number
        $lines = [];
        foreach (array_chunk($bills->pluck('member_id')->unique()->values()->all(), 400) as $ids) {
            $rows = DB::table('member_bill_generates as g')
                ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
                ->select('g.member_id', 'g.bill_number', 'g.ledger_head_id', 'h.title',
                    DB::raw('CAST(g.amount AS CHAR) as amount'), DB::raw('CAST(g.cgst_total AS CHAR) as cgst'), DB::raw('CAST(g.sgst_total AS CHAR) as sgst'))
                ->where('g.society_id', $this->societyId)->whereIn('g.member_id', $ids)->where('h.is_tax_applicable', 1)
                ->orderBy('g.id')->get();
            foreach ($rows as $r) {
                $lines[$r->member_id . '|' . $r->bill_number][] = $r;
            }
        }

        $data = [];
        $tariffs = [];
        foreach ($bills as $b) {
            $data[$b->id]['MemberBillSummary'] = ['bill_no' => $b->bill_no, 'bill_date' => $b->bill_generated_date, 'member_id' => $b->member_id, 'gstin_no' => $b->gstin_no];
            foreach ($lines[$b->member_id . '|' . $b->bill_no] ?? [] as $t) {
                $data[$b->id]['MemberBillGenerate'][$t->ledger_head_id] = ['amount' => $t->amount, 'cgst' => $t->cgst, 'sgst' => $t->sgst];
                if (!array_key_exists($t->ledger_head_id, $tariffs)) {
                    $tariffs[$t->ledger_head_id] = $t->title;
                }
            }
        }

        $params = DB::table('society_parameters')
            ->select(DB::raw('CAST(sgst_tax_per AS CHAR) as sgst_tax_per'), DB::raw('CAST(cgst_tax_per AS CHAR) as cgst_tax_per'))
            ->where('society_id', $this->societyId)->orderBy('id')->first();

        return ['data' => $data, 'members' => $members, 'tariffs' => $tariffs, 'params' => $params];
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
