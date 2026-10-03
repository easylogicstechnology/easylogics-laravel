<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Collection Sheet (CakePHP account_reports/account_collection_sheet): the society's bills, in bill-number
 * order, filtered by building / wing / unit range and by bill date. CakePHP runs it on a plain page open
 * too (every bill of the society, all years), so the caller does the same.
 */
class CollectionSheetReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array{rows: array, showGst: bool} */
    public function run(array $in): array
    {
        $q = DB::table('member_bill_summaries as b')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select('b.*', 'm.member_prefix', 'm.member_name', 'm.flat_no as member_flat_no',
                DB::raw('CAST(b.amount_payable AS CHAR) as t_amount_payable'), DB::raw('CAST(b.op_due_amount AS CHAR) as t_op_due_amount'),
                DB::raw('CAST(b.monthly_amount AS CHAR) as t_monthly_amount'), DB::raw('CAST(b.tax_total AS CHAR) as t_tax_total'),
                DB::raw('CAST(b.interest_on_due_amount AS CHAR) as t_interest'))
            ->where('b.society_id', $this->societyId);

        if ($this->val($in, 'building_id') !== '') {
            $q->where('m.building_id', $this->val($in, 'building_id'));
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
            $q->where('b.bill_generated_date', $d1);
        } elseif ($d2 !== '') {
            $q->where('b.bill_generated_date', $d2);
        }

        $rows = $q->orderBy('b.bill_no')->get()->map(fn ($r) => (array) $r)->all();

        $p = DB::table('society_parameters')
            ->select(DB::raw('CAST(cgst_tax_per AS CHAR) as cgst'), DB::raw('CAST(igst_tax_per AS CHAR) as igst'), DB::raw('CAST(sgst_tax_per AS CHAR) as sgst'))
            ->where('society_id', $this->societyId)->orderBy('id')->first();
        // CakePHP: !empty(cgst) || igst || sgst  (PHP truthiness of the printed values)
        $showGst = $p ? (!empty($p->cgst) || $this->truthy($p->igst) || $this->truthy($p->sgst)) : false;

        return ['rows' => $rows, 'showGst' => $showGst];
    }

    private function truthy($v): bool
    {
        return (bool) $v;
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
