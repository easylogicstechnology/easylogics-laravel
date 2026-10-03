<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Dues From Members (CakePHP account_reports/account_dues_from_member) and, with the same member
 * selection, the Dues-Advance list (account_dues_advance_from_member).
 */
class DuesFromMembersReport
{
    private MemberDuesCalculator $calc;

    public function __construct(private int $societyId, private ?int $financialYearId)
    {
        $this->calc = new MemberDuesCalculator($societyId, $financialYearId);
    }

    /** The society's active members for the Building / Wing / Unit-range filters, in id order. */
    public function members(array $in): array
    {
        $q = DB::table('members')
            ->select('id', 'flat_no', 'member_prefix', 'member_name', 'building_id', 'wing_id', 'member_transfer',
                DB::raw('CAST(op_principal AS CHAR) as op_principal'), DB::raw('CAST(op_interest AS CHAR) as op_interest'), DB::raw('CAST(op_tax AS CHAR) as op_tax'))
            ->where('society_id', $this->societyId)->where('status', 1);

        if ($this->val($in, 'building_id') !== '') {
            $q->where('building_id', $this->val($in, 'building_id'));
        }
        if ($this->val($in, 'wing_id') !== '') {
            $q->where('wing_id', $this->val($in, 'wing_id'));
        }
        $from = $this->val($in, 'flat_no');
        $to = $this->val($in, 'flat_no_to');
        if ($from !== '' && $to !== '') {
            $q->whereBetween('flat_no', [$from, $to]);
        } elseif ($to !== '') {
            $q->where('flat_no', $to);
        } elseif ($from !== '') {
            $q->where('flat_no', $from);
        }

        return $q->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * account_dues_from_member: one row per member whose dues satisfy the Operator / Amount filter.
     * $fromBalanceSheet keeps the members with dues >= 0 (how the Balance Sheet asks for them).
     */
    public function dues(array $in, bool $fromBalanceSheet = false): array
    {
        $members = $this->members($in);
        $reportType = $this->val($in, 'report_type');
        $memberRecord = $this->val($in, 'member_record') ?: null;
        $operator = $this->val($in, 'operator') ?: '=';
        $amount = $this->val($in, 'amount');
        $date = (string) ($in['payment_date'] ?? '');

        if ($reportType === 'Current Dues-Advance') {
            $asOn = $this->val($in, 'payment_date') !== '' ? $this->val($in, 'payment_date') : date('Y-m-d');
            $dues = $this->calc->currentYearLastBillDue($members, $asOn);
        } elseif ($reportType === 'Detail') {
            // CakePHP hands the Detail array to json_decode(): every member comes out empty (null)
            $dues = [];
        } else {
            $dues = $this->calc->dueAmounts($members, $date, $reportType, $memberRecord);
        }

        $names = [];
        if ($memberRecord === 'Old') {
            $rows = DB::table('member_identifications')
                ->select('member_id', 'third_member', 'flat_no')
                ->where('status', 1)->where('society_id', $this->societyId)
                ->whereIn('member_id', array_column($members, 'id'))
                ->orderBy('flat_no')->orderBy('id')->get();
            foreach ($rows as $r) {
                $names[$r->member_id] ??= $r->third_member;
            }
        }

        $summary = [];
        foreach ($members as $m) {
            $id = (int) $m['id'];
            $row = $dues[$id] ?? ['dueAmount' => null, 'date' => null];
            $due = $row['dueAmount'];

            $ok = true;
            if ($operator !== '' && $amount !== '') {
                $ok = $this->evaluate(abs((float) $due), $operator, $amount);
            }
            if ($fromBalanceSheet) {
                $ok = ($due >= 0);
            }
            if (!$ok) {
                continue;
            }

            $summary[] = [
                'total_dues_amount' => $row['dueAmount'],
                'flat_no' => $m['flat_no'],
                'date' => $row['date'],
                'member_prefix' => $m['member_prefix'],
                'member_id' => $id,
                'building_id' => $m['building_id'],
                'wing_id' => $m['wing_id'],
                'member_name' => isset($names[$id]) ? $names[$id] : $m['member_name'],
            ];
        }

        return $summary;
    }

    /** account_dues_advance_from_member: members whose dues on the date are below zero (an advance). */
    public function advances(array $in): array
    {
        $members = $this->members($in);
        $dues = $this->calc->dueAmounts($members, (string) ($in['payment_date'] ?? ''), '', null);

        $summary = [];
        foreach ($members as $m) {
            $due = $dues[(int) $m['id']]['dueAmount'];
            if ($due < 0) {
                $summary[] = [
                    'total_dues_amount' => $due,
                    'flat_no' => $m['flat_no'],
                    'member_prefix' => $m['member_prefix'],
                    'member_name' => $m['member_name'],
                ];
            }
        }

        return $summary;
    }

    /** CakePHP AccountReportsController::evaluate() */
    private function evaluate($due, string $operator, $amount)
    {
        return match ($operator) {
            '<' => $due < $amount,
            '>' => $due > $amount,
            '>=' => $due >= $amount,
            '<=' => $due <= $amount,
            '=' => $due == $amount,
            '<>' => $due != $amount,
            default => null,
        };
    }

    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return (is_scalar($v) && !empty(trim((string) $v))) ? trim((string) $v) : '';
    }
}
