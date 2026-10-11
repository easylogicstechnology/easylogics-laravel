<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Port of CakePHP SocietyBillComponent::getMemberClosingBalanceData() - the closing row(s) a member's NEW year
 * opens with (member_year_wise_closing_balance of the previous year).
 *
 * A transferred unit keeps ONE member id and every closing row of it is stored with member_transfer = 0, so the
 * row of the year BEFORE the transfer is the OLD owner's balance. The new owner starts with nothing from it: their
 * opening is only what was entered on the transfer form (members.op_*, default 0) - callers fall back to that
 * when no row is returned. The new owner's own later years are rows of the years they were billed in, so those stay.
 *
 * "Before the transfer" = the year before the first year in which a bill of the owner's CURRENT generation
 * (member_bill_summaries.member_transfer = members.member_transfer) exists; with no such bill yet, the active
 * year (the transfer has just happened). A unit that was never transferred is read exactly as before.
 */
class MemberClosing
{
    /**
     * @return Collection<int, object> rows of member_year_wise_closing_balance (one per bill type)
     */
    public static function rowsForOpening(int $memberId, int $memberTransfer, int $fyId): Collection
    {
        $prevYearId = $fyId - 1;

        if ($memberId > 0 && $memberTransfer >= 1 && $prevYearId < self::firstBilledYearOfTransfer($memberId, $memberTransfer, $fyId)) {
            return collect();
        }

        return DB::table('member_year_wise_closing_balance')
            ->where('member_id', $memberId)
            ->where('year_id', $prevYearId)
            ->orderBy('id')
            ->get();
    }

    /** First financial year (id) with a bill of this owner generation; none yet -> the active year. */
    public static function firstBilledYearOfTransfer(int $memberId, int $memberTransfer, int $currentYearId): int
    {
        $first = (int) DB::table('member_bill_summaries')
            ->where('member_id', $memberId)
            ->where('member_transfer', $memberTransfer)
            ->min('financial_year_id');

        return $first > 0 ? $first : $currentYearId;
    }
}
