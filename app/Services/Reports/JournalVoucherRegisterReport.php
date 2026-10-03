<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Journal Voucher Register (CakePHP account_reports/account_journal_voucher_register).
 * Every journal voucher of the society (all years), grouped by voucher number; the Voucher No box
 * narrows it to one voucher.
 */
class JournalVoucherRegisterReport
{
    public function __construct(private int $societyId)
    {
    }

    /** @return array{data: array, members: array, ledgers: array} */
    public function run(array $post): array
    {
        $voucherNo = $post['voucher_no'] ?? '';

        $q = DB::table('journal_vouchers')
            ->select('id', 'society_id', 'jv_debit_ledger_head_id', 'jv_credit_ledger_head_id', 'member_transfer', 'voucher_date', 'voucher_no',
                'jv_debit_member_head_id', 'jv_credit_member_head_id', 'note',
                DB::raw('CAST(jv_amount_debited AS CHAR) as jv_amount_debited'), DB::raw('CAST(jv_amount_credited AS CHAR) as jv_amount_credited'))
            ->where('society_id', $this->societyId);
        if ($voucherNo !== '' && $voucherNo !== null) {
            $q->where('voucher_no', $voucherNo);
        }
        $vouchers = $q->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();

        $members = DB::table('members')->where('society_id', $this->societyId)->orderBy('id')->get(['id', 'member_name', 'member_transfer']);
        $memberNames = $members->pluck('member_name', 'id')->all();
        $memberTransfer = $members->pluck('member_transfer', 'id')->all();

        $data = [];
        if ($vouchers) {
            // Name the member carried at the time of an old (pre-transfer) voucher
            $old = [];
            $oldRows = DB::select(
                'select third_member, member_id, flat_no from member_identifications where id in ('
                . 'select max(id) from member_identifications where member_id in ('
                . 'SELECT id FROM `members` where member_transfer > 0 and society_id = ? and status = 1) group by member_id)',
                [$this->societyId]
            );
            foreach ($oldRows as $r) {
                if (!empty($r->member_id) && isset($r->third_member)) {
                    $old[$r->member_id] = $r->third_member;
                }
            }

            foreach ($vouchers as $jv) {
                $jvTransfer = $jv['member_transfer'] ?? null;
                foreach (['credit', 'debit'] as $side) {
                    $headId = !empty($jv['jv_' . $side . '_member_head_id']) ? $jv['jv_' . $side . '_member_head_id'] : null;
                    if ($headId === null) {
                        continue;
                    }
                    $title = $memberNames[$headId] ?? '';
                    $current = $memberTransfer[$headId] ?? null;
                    if ($jvTransfer !== null && $current !== null && $jvTransfer < $current && !empty($old[$headId])) {
                        $title = $old[$headId];
                    }
                    $jv[$side . '_member_title'] = $title;
                }

                $no = $jv['voucher_no'];
                if (!empty($jv['jv_credit_ledger_head_id'])) {
                    $jv['flag'] = 'ledger';
                    $data[$no]['credit'][] = $jv;
                    $data[$no]['date'] = $jv['voucher_date'];
                } elseif (!empty($jv['jv_debit_ledger_head_id'])) {
                    $jv['flag'] = 'ledger';
                    $data[$no]['date'] = $jv['voucher_date'];
                    $data[$no]['debit'][] = $jv;
                }
                if (!empty($jv['jv_credit_member_head_id'])) {
                    $jv['flag'] = 'member';
                    $data[$no]['date'] = $jv['voucher_date'];
                    $data[$no]['credit'][] = $jv;
                } elseif (!empty($jv['jv_debit_member_head_id'])) {
                    $jv['flag'] = 'member';
                    $data[$no]['date'] = $jv['voucher_date'];
                    $data[$no]['debit'][] = $jv;
                }
            }
        }

        return [
            'data' => $data,
            'members' => $memberNames,
            'ledgers' => DB::table('society_ledger_heads')->where('society_id', $this->societyId)->pluck('title', 'id')->all(),
        ];
    }
}
