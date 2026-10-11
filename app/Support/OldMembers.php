<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Port of CakePHP SocietyBillComponent::getSocietyOldMeberFlatList() - the "Old Members" a society's screens
 * (Journal Voucher, Debit Note / Credit Note, Journal Voucher Register ...) can pick or name.
 *
 * 1. The most recent member_identifications row (by id) of every member that has a transfer history
 *    (member_transfer > 0): third_member is the previous owner's name.
 * 2. A transferred unit with NO identification row at all (an older transfer, or one whose records were later
 *    removed) used to be missing from every Old Members list. Its previous owner is listed too, the way the Old
 *    Member Bill tab already shows it: this unit, under the name the member record carries - the previous owner's
 *    name is simply not stored anywhere for such a unit.
 */
class OldMembers
{
    /**
     * @return array<int, object{third_member: ?string, member_id: int|string, flat_no: ?string}>
     */
    public static function forSociety(int $societyId): array
    {
        $identified = DB::select('
            select third_member, member_id, flat_no from member_identifications where id in (
                select max(id) from member_identifications where member_id in (
                    select id from members where member_transfer > 0 and society_id = ? and status = 1
                ) group by member_id
            )
        ', [$societyId]);

        $withoutIdentification = DB::select('
            select m.member_name as third_member, m.id as member_id, m.flat_no as flat_no
            from members m
            where m.member_transfer > 0 and m.society_id = ? and m.status = 1
              and not exists (select 1 from member_identifications i where i.member_id = m.id)
            order by m.id
        ', [$societyId]);

        return array_merge($identified, $withoutIdentification);
    }
}
