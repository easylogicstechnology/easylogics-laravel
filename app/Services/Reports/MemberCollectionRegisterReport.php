<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Member Collection Register (CakePHP account_reports/member_collection_register): every active member
 * of the society (Building / Wing / Unit range) with the member payments inside the date range.
 * CakePHP fetches the payments with a hasMany "contain": all of the member's payments matching the
 * date condition, whatever the society year.
 */
class MemberCollectionRegisterReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array<int, array{member: array, payments: array}> */
    public function run(array $in): array
    {
        $q = DB::table('members as m')
            ->leftJoin('buildings as bl', 'bl.id', '=', 'm.building_id')
            ->select('m.id', 'm.flat_no', 'm.member_prefix', 'm.member_name', 'bl.building_name')
            ->where('m.society_id', $this->societyId)->where('m.status', 1);

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
        $members = $q->orderBy('m.id')->get()->map(fn ($r) => (array) $r)->all();

        $d1 = $this->val($in, 'payment_date');
        $d2 = $this->val($in, 'payment_date_to');
        $payments = [];
        foreach (array_chunk(array_column($members, 'id'), 500) as $ids) {
            $p = DB::table('member_payments')
                ->select('id', 'member_id', 'bill_month', 'payment_date', 'receipt_id', 'cheque_reference_number', 'payment_mode', 'society_bank_id', 'member_bank_branch',
                    DB::raw('CAST(amount_paid AS CHAR) as amount_paid'))
                ->whereIn('member_id', $ids);
            if ($d1 !== '' && $d2 !== '') {
                $p->whereBetween('payment_date', [$d1, $d2]);
            } elseif ($d2 !== '') {
                $p->where('payment_date', $d2);
            } elseif ($d1 !== '') {
                $p->where('payment_date', $d1);
            }
            foreach ($p->orderBy('id')->get() as $r) {
                $payments[$r->member_id][] = (array) $r;
            }
        }

        $out = [];
        foreach ($members as $m) {
            $out[] = ['member' => $m, 'payments' => $payments[$m['id']] ?? []];
        }

        return $out;
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
