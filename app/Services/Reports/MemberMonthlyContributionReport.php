<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Member Monthly Contribution (CakePHP account_reports/account_member_monthly_contribution): what the
 * bills charged per ledger head for each month April..March, added up over all bills of the society
 * (no financial-year filter, as in CakePHP), optionally for one member.
 */
class MemberMonthlyContributionReport
{
    /** month number => label, in the order CakePHP walks them (billing frequency 1) */
    private const MONTHS = [4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December', 1 => 'January', 2 => 'February', 3 => 'March'];

    public function __construct(private int $societyId)
    {
    }

    /** @return array{contribution: array, tax: array, heads: array} */
    public function run(array $in): array
    {
        $memberId = $this->val($in, 'member_id');
        $flat = $this->val($in, 'flat_no');
        // CakePHP: "Unit No" and "Member" both write Member.id, the later (Member) wins
        $memberIdFilter = $memberId !== '' ? $memberId : ($flat !== '' ? $flat : '');

        $contribution = [];
        $tax = [];
        foreach (array_keys(self::MONTHS) as $month) {
            $q = DB::table('member_bill_generates as g')
                ->leftJoin('members as m', 'm.id', '=', 'g.member_id')
                ->select('g.ledger_head_id', DB::raw('CAST(g.amount AS CHAR) as amount'), DB::raw('CAST(g.tax_total AS CHAR) as tax_total'))
                ->where('g.society_id', $this->societyId)
                ->where('g.month', $month);
            if ($this->val($in, 'building_id') !== '') {
                $q->where('m.building_id', $this->val($in, 'building_id'));
            }
            if ($this->val($in, 'wing_id') !== '') {
                $q->where('m.wing_id', $this->val($in, 'wing_id'));
            }
            if ($memberIdFilter !== '') {
                $q->where('m.id', $memberIdFilter);
            }
            foreach ($q->orderBy('g.id')->get() as $r) {
                $contribution[$r->ledger_head_id][$month] = ($contribution[$r->ledger_head_id][$month] ?? null) + (isset($r->amount) ? $r->amount : 0.00);
                $tax[$month] = ($tax[$month] ?? null) + (isset($r->tax_total) ? $r->tax_total : 0.00);
            }
        }

        $heads = DB::table('society_ledger_heads')
            ->where('society_id', $this->societyId)->where('status', 1)->orderBy('id')->pluck('title', 'id')->all();

        return ['contribution' => $contribution, 'tax' => $tax, 'heads' => $heads];
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
