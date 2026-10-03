<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\DebitCreditNote;
use App\Models\Member;
use App\Models\SocietyLedgerHead;
use App\Support\JournalNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Debit Note / Credit Note (CakePHP DebitCreditNotesController).
 *
 * A copy of the Journal Voucher idea (a voucher of Dr / Cr rows saved to journal_vouchers) limited to
 * transactions that involve a Member, and able to adjust a member's latest bill automatically (existing engine
 * rules) or by a chosen component (Tax / Interest / Principal).
 *
 * The Journal Voucher screen is not touched by this class; the two only share the journal_vouchers table, split
 * by entry_type ('JV' vs 'DN' / 'CN').
 *
 * A note acts on the member's LATEST regular bill only and nothing else is recalculated: after the rows are
 * saved, the signed component amounts are added to that one bill row in place (applyDeltas). Older bills are
 * never touched. The journal rows stay the source of truth, so the bill engine (which has the same amounts built
 * into its formulas - BillSettlementService, via JournalNotes) re-applies them on any later full rebuild,
 * e.g. after a payment.
 */
class DebitCreditNoteController extends Controller
{
    private const MSG_NEEDS_MEMBER = 'Debit Note / Credit Note must contain at least one Member transaction. Other Head to Other Head transactions are not allowed.';

    private const COMPONENTS = ['TAX', 'INTEREST', 'PRINCIPAL'];

    private function societyId(): int
    {
        return (int) auth()->id();
    }

    private function fyId(): int
    {
        return (int) session('fy.year_id');
    }

    // ------------------------------------------------------------------------------------ helpers shared with the JV screen

    /** id => member name of the society's active members (SocietyBill::getSocietyMemberList) */
    private function memberList(int $societyId): array
    {
        return Member::where('status', 1)->where('society_id', $societyId)->orderBy('id')->pluck('member_name', 'id')->all();
    }

    private function flatNoList(int $societyId): array
    {
        return Member::where('status', 1)->where('society_id', $societyId)->orderBy('id')->pluck('flat_no', 'id')->all();
    }

    /** the previous owner's most recent member_identifications row of every member with a transfer history */
    private function oldMemberRows(int $societyId): array
    {
        return DB::select('
            select third_member, member_id, flat_no from member_identifications where id in (
                select max(id) from member_identifications where member_id in (
                    select id from members where member_transfer > 0 and society_id = ? and status = 1
                ) group by member_id
            )
        ', [$societyId]);
    }

    /**
     * Ledger (Other Head) accounts a note may use: the same list, with the same Bank / Cash exclusion, as the
     * Journal Voucher screen.
     */
    private function ledgerList(int $societyId): array
    {
        return SocietyLedgerHead::where('status', 1)->where('society_id', $societyId)
            ->whereNotIn('society_head_sub_category_id', [20, 21])
            ->orderBy('title')->pluck('title', 'id')->all();
    }

    /** the next voucher number, the same generator the Journal Voucher screen uses (max + 1 of the society + year) */
    private function nextVoucherNumber(int $societyId, int $fyId): int
    {
        $max = DB::table('journal_vouchers')->where('society_id', $societyId)->where('financial_year_id', $fyId)->max('voucher_no');

        return ($max !== null && $max >= 0) ? ((int) $max + 1) : 1;
    }

    // ------------------------------------------------------------------------------------ screen

    public function index(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $migrated = JournalNotes::hasNoteColumns();
        $payableOption = $migrated && JournalNotes::hasPayableColumn();

        if ($request->isMethod('post') && $migrated) {
            return $this->saveNote($request, $societyId, $fyId); // always redirects
        }

        $memberList = $this->memberList($societyId);
        $flatNoList = $this->flatNoList($societyId);

        return view('society.modules.debit-credit-notes', [
            'migrated' => $migrated,
            'payableOption' => $payableOption,
            'memberList' => $memberList,
            'oldMemberRows' => $this->oldMemberRows($societyId),
            'flatNoList' => $flatNoList,
            'ledgerList' => $this->ledgerList($societyId),
            'notes' => $migrated ? $this->noteList($societyId, $fyId, $memberList, $flatNoList) : [],
        ]);
    }

    /**
     * Read-only JSON: the member's latest regular bill and its component balances, so the screen can show what a
     * manual adjustment will act on. Informational only - the save looks the bill up again on the server and never
     * uses anything sent by the browser.
     */
    public function latestBill($memberKey = '')
    {
        $out = ['found' => 0];
        $memberId = $this->parseCurrentMemberKey((string) $memberKey);

        if ($memberId) {
            $bill = $this->latestRegularBill($memberId, $this->societyId(), $this->fyId());
            if (!empty($bill)) {
                $out = [
                    'found' => 1,
                    'bill_no' => $bill['bill_no'],
                    'month' => $bill['month'],
                    'generated' => date('d/m/Y', strtotime($bill['bill_generated_date'])),
                    'generated_iso' => $bill['bill_generated_date'],
                    'tax' => round((float) $bill['tax_balance'], 2),
                    'interest' => round((float) $bill['interest_balance'], 2),
                    'principal' => round((float) $bill['principal_balance'], 2),
                    'balance' => round((float) $bill['balance_amount'], 2),
                ];
            }
        }

        return response()->json($out);
    }

    // ------------------------------------------------------------------------------------ save

    private function saveNote(Request $request, int $societyId, int $fyId)
    {
        $back = redirect()->route('society.debitCreditNotes');
        $input = is_array($request->input('DebitCreditNote')) ? $request->input('DebitCreditNote') : [];

        $built = $this->validateNote($input, $societyId, $fyId);
        if (isset($built['error'])) {
            return $back->with('error', $built['error']);
        }

        $rows = $built['rows'];
        $memberIds = $built['memberIds'];
        $deltas = $built['deltas'];

        // The member's latest bill row as it is now, so it can be put back if applying the note to it fails
        // (member_bill_summaries is MyISAM: no rollback). Only that one row per member.
        $snapshot = $this->snapshotBills($memberIds, $societyId, $fyId);

        $voucherNo = 0;
        try {
            DB::transaction(function () use ($rows, $built, $societyId, $fyId, &$voucherNo) {
                // Serialise voucher numbering for this society + year for the length of the transaction, then use
                // the same generator the Journal Voucher screen uses so a note number can never collide with a JV.
                DB::select('SELECT MAX(voucher_no) FROM journal_vouchers WHERE society_id = ? AND financial_year_id = ? FOR UPDATE', [$societyId, $fyId]);
                $voucherNo = $this->nextVoucherNumber($societyId, $fyId);

                $now = date('Y-m-d H:i:s');
                foreach ($rows as $r) {
                    $isDebit = ($r['type'] === 'Debit');
                    $isMember = ($r['kind'] === 'member');

                    // A member row that acts on the latest bill is stored as one row per bill component (its amounts
                    // add up to the member's amount, so Dr = Cr still holds).
                    $parts = !empty($r['splits']) ? $r['splits'] : ['' => $r['cents']];
                    foreach ($parts as $component => $cents) {
                        $component = (string) $component;
                        $amount = sprintf('%.2f', $cents / 100);
                        $data = [
                            'society_id' => $societyId,
                            'financial_year_id' => $fyId,
                            'entry_type' => $built['noteType'],
                            'voucher_no' => $voucherNo,
                            'voucher_date' => $built['date'],
                            'note' => $built['note'],
                            'jv_type' => $r['type'],
                            'jv_debit_ledger_head_id' => ($isDebit && !$isMember) ? $r['id'] : 0,
                            'jv_debit_member_head_id' => ($isDebit && $isMember) ? $r['id'] : 0,
                            'jv_credit_ledger_head_id' => (!$isDebit && !$isMember) ? $r['id'] : 0,
                            'jv_credit_member_head_id' => (!$isDebit && $isMember) ? $r['id'] : 0,
                            'jv_amount_debited' => $isDebit ? $amount : '0.00',
                            'jv_amount_credited' => $isDebit ? '0.00' : $amount,
                            'jv_debit_type' => $isDebit ? 'Debit' : '',
                            'jv_creadit_type' => $isDebit ? '' : 'Credit',
                            'member_transfer' => $isMember ? $r['transfer'] : null,
                            'manual_component' => $component !== '' ? $component : null,
                            'manual_bill_no' => $component !== '' ? $r['billNo'] : null,
                            'cdate' => $now,
                            'udate' => $now,
                        ];
                        if (JournalNotes::hasPayableColumn()) {
                            $data['adjust_payable'] = ($component !== '' && !empty($built['adjustPayable'])) ? 1 : 0;
                        }

                        if (!DB::table('journal_vouchers')->insert($data)) {
                            throw new \RuntimeException('A note row could not be saved.');
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('Debit/Credit Note save failed (society ' . $societyId . '): ' . $e->getMessage());

            return $back->with('error', 'The note could not be saved. Nothing was changed. Please try again.');
        }

        // Rows are committed; now apply them to each member's LATEST bill only. Older bills are never touched, and
        // the bills are not recalculated.
        $failed = $this->applyDeltas($deltas, $societyId, $fyId);
        if (empty($failed)) {
            return $back->with('success', 'The ' . ($built['noteType'] === 'DN' ? 'Debit' : 'Credit') . ' Note ' . $built['noteType'] . '-' . str_pad((string) $voucherNo, 5, '0', STR_PAD_LEFT) . ' has been saved.');
        }

        // Compensation: take the note back out and put the latest bill rows back as they were.
        $this->deleteVoucherRows($voucherNo, $societyId, $fyId);
        $restored = $this->restoreBills($snapshot);
        Log::error('Debit/Credit Note ' . $voucherNo . ' could not be applied to the latest bill of members ' . implode(',', $failed) . '; note removed, bill restore ' . ($restored ? 'succeeded.' : 'FAILED.'));

        return $back->with('error', $restored
            ? 'The note was not saved: the latest bill could not be updated. Nothing was changed.'
            : "The note was not saved, but the latest bill of a member could not be fully restored. Please contact support before changing these members' bills.");
    }

    /**
     * Server-side validation. Returns ['error' => message] or the rows to write. The browser's JavaScript is a
     * convenience only; nothing here trusts it.
     */
    private function validateNote(array $input, int $societyId, int $fyId): array
    {
        $noteType = $input['note_type'] ?? '';
        if (!in_array($noteType, DebitCreditNote::ENTRY_TYPES, true)) {
            return ['error' => 'Please choose Debit Note or Credit Note.'];
        }

        $date = isset($input['voucher_date']) ? trim((string) $input['voucher_date']) : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            return ['error' => 'Please enter a valid voucher date.'];
        }
        $yearStart = session('fy.year_start_date');
        $yearEnd = session('fy.year_end_date');
        if (!(strtotime($date) >= strtotime((string) $yearStart) && strtotime($date) <= strtotime((string) $yearEnd))) {
            return ['error' => 'The voucher date must be within the current financial year.'];
        }

        $note = isset($input['note']) ? trim((string) $input['note']) : '';
        if (strlen($note) > 480) {
            return ['error' => 'The note is too long.'];
        }

        // Default: a note adjusts the bill's balance only. Ticking the option also moves its Amount Payable.
        $adjustPayable = !empty($input['adjust_payable']);
        if ($adjustPayable && !JournalNotes::hasPayableColumn()) {
            return ['error' => 'Adjusting the Amount Payable needs a database update (Config/Schema/debit_credit_notes_adjust_payable.sql). Untick the option or contact support.'];
        }

        $types = isset($input['type']) && is_array($input['type']) ? array_values($input['type']) : [];
        $accounts = isset($input['account']) && is_array($input['account']) ? array_values($input['account']) : [];
        $debits = isset($input['debit']) && is_array($input['debit']) ? array_values($input['debit']) : [];
        $credits = isset($input['credit']) && is_array($input['credit']) ? array_values($input['credit']) : [];
        $adjusts = isset($input['adjust']) && is_array($input['adjust']) ? array_values($input['adjust']) : [];

        if (count($types) < 2 || count($types) !== count($accounts)) {
            return ['error' => 'A note needs at least two rows.'];
        }

        $memberList = $this->memberList($societyId);
        $oldIds = [];
        foreach ($this->oldMemberRows($societyId) as $old) {
            $oldIds[$old->member_id] = true;
        }
        $ledgerList = $this->ledgerList($societyId);

        $rows = [];
        $memberIds = [];
        foreach ($types as $i => $type) {
            $n = $i + 1;
            if ($type !== 'Debit' && $type !== 'Credit') {
                return ['error' => "Row $n: choose Debit or Credit."];
            }
            $raw = $type === 'Debit' ? (isset($debits[$i]) ? trim((string) $debits[$i]) : '') : (isset($credits[$i]) ? trim((string) $credits[$i]) : '');
            if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0) {
                return ['error' => "Row $n: enter an amount greater than zero (at most two decimals)."];
            }
            $cents = (int) round(((float) $raw) * 100);
            $acct = isset($accounts[$i]) ? (string) $accounts[$i] : '';

            $row = ['type' => $type, 'cents' => $cents, 'manual' => false, 'component' => null, 'billNo' => null, 'transfer' => null, 'old' => false];

            if (preg_match('/^member-(\d+)(-old)?$/', $acct, $m)) {
                $row['kind'] = 'member';
                $row['id'] = (int) $m[1];
                $row['old'] = !empty($m[2]);
                if ($row['old'] ? !isset($oldIds[$row['id']]) : !isset($memberList[$row['id']])) {
                    return ['error' => "Row $n: the selected member is not valid for this society."];
                }
            } elseif (preg_match('/^ledger-(\d+)$/', $acct, $m)) {
                $row['kind'] = 'ledger';
                $row['id'] = (int) $m[1];
                if (!isset($ledgerList[$row['id']])) {
                    return ['error' => "Row $n: the selected account is not valid (Bank and Cash accounts are not allowed here)."];
                }
            } else {
                return ['error' => "Row $n: choose an account."];
            }

            $adjust = isset($adjusts[$i]) ? strtoupper(trim((string) $adjusts[$i])) : 'AUTO';
            if ($row['kind'] === 'member' && $adjust !== 'AUTO') {
                if (!in_array($adjust, self::COMPONENTS, true)) {
                    return ['error' => "Row $n: choose Auto, Tax, Interest or Principal."];
                }
                if ($row['old']) {
                    return ['error' => "Row $n: a manual adjustment is available only for a current member."];
                }
                $row['manual'] = true;
                $row['component'] = $adjust;
            }

            $rows[] = $row;
        }

        // ---- Dr = Cr ---------------------------------------------------------------
        $drTotal = $crTotal = 0;
        $memberDr = $memberCr = $otherDr = $otherCr = 0;
        foreach ($rows as $r) {
            if ($r['type'] === 'Debit') {
                $drTotal += $r['cents'];
                if ($r['kind'] === 'member') { $memberDr += $r['cents']; } else { $otherDr += $r['cents']; }
            } else {
                $crTotal += $r['cents'];
                if ($r['kind'] === 'member') { $memberCr += $r['cents']; } else { $otherCr += $r['cents']; }
            }
        }
        if ($drTotal !== $crTotal) {
            return ['error' => 'Total Debit and total Credit must be equal.'];
        }

        // ---- Member must be involved; no Other Head -> Other Head, directly or indirectly ----
        if ($memberDr + $memberCr === 0) {
            return ['error' => self::MSG_NEEDS_MEMBER];
        }
        if ($otherDr > $memberCr || $otherCr > $memberDr) {
            return ['error' => self::MSG_NEEDS_MEMBER];
        }

        // ---- Debit Note debits the Member, Credit Note credits the Member ---------------
        $otherDebitRows = $otherCreditRows = 0;
        foreach ($rows as $r) {
            if ($r['kind'] === 'ledger') {
                if ($r['type'] === 'Debit') { $otherDebitRows++; } else { $otherCreditRows++; }
            }
        }
        if ($noteType === 'DN') {
            if ($memberDr === 0) {
                return ['error' => 'A Debit Note must debit at least one Member.'];
            }
            if ($otherDebitRows > 0) {
                return ['error' => 'In a Debit Note an Other Head can only be credited (the Member is debited). To debit an Other Head, use a Credit Note.'];
            }
        } else {
            if ($memberCr === 0) {
                return ['error' => 'A Credit Note must credit at least one Member.'];
            }
            if ($otherCreditRows > 0) {
                return ['error' => 'In a Credit Note an Other Head can only be debited (the Member is credited). To credit an Other Head, use a Debit Note.'];
            }
        }

        // ---- Members: transfer number, latest bill, date, and what each row does to that bill --------
        // Every note row of a current member who has a bill acts on that member's LATEST bill only:
        //   manual  -> exactly the component chosen
        //   auto    -> a debit goes to Principal; a credit settles Tax / Interest / Principal in the society's
        //              settlement order, never more than each component's balance
        // Older bills are never changed. A credit can never take a component below zero.
        $memberRowIds = array_unique(array_map(fn ($r) => ($r['kind'] === 'member' && isset($r['id'])) ? $r['id'] : 0, $rows));
        $transfers = Member::whereIn('id', $memberRowIds)->pluck('member_transfer', 'id')->all();

        $latest = [];
        $remaining = []; // member id => component => cents still available to credit
        $deltas = [];    // member id => ['billNo' => n, 'TAX' => cents, 'INTEREST' => cents, 'PRINCIPAL' => cents, 'PAYABLE' => cents]
        $order = $this->settleOrder($societyId);

        foreach ($rows as $i => &$r) {
            $r['splits'] = [];
            if ($r['kind'] !== 'member') {
                continue;
            }
            $n = $i + 1;
            $t = isset($transfers[$r['id']]) ? (int) $transfers[$r['id']] : 0;
            $r['transfer'] = $r['old'] ? $t - 1 : $t;

            if ($r['old']) {
                continue; // a previous owner's entry: posted to the ledger only, as a Journal Voucher does
            }

            $memberIds[$r['id']] = $r['id'];

            if (!array_key_exists($r['id'], $latest)) {
                $latest[$r['id']] = $this->latestRegularBill($r['id'], $societyId, $fyId);
                $b = $latest[$r['id']];
                if (!empty($b)) {
                    $remaining[$r['id']] = [
                        'TAX' => (int) round(max(0, (float) $b['tax_balance']) * 100),
                        'INTEREST' => (int) round(max(0, (float) $b['interest_balance']) * 100),
                        'PRINCIPAL' => (int) round(max(0, (float) $b['principal_balance']) * 100),
                    ];
                }
            }
            $bill = $latest[$r['id']];

            if (!empty($bill) && strtotime($date) < strtotime($bill['bill_generated_date'])) {
                return ['error' => "Row $n: the voucher date cannot be earlier than the member's latest bill date (" . date('d/m/Y', strtotime($bill['bill_generated_date'])) . ').'];
            }

            if (empty($bill)) {
                if ($r['manual']) {
                    return ['error' => "Row $n: this member has no generated bill, so only Auto can be used."];
                }
                continue; // no bill to act on: posted to the ledger only
            }

            $r['billNo'] = (int) $bill['bill_no'];

            if ($r['manual']) {
                $parts = [$r['component'] => $r['cents']];
            } elseif ($r['type'] === 'Debit') {
                $parts = ['PRINCIPAL' => $r['cents']];
            } else {
                $parts = [];
                $left = $r['cents'];
                foreach ($order as $component) {
                    if ($left <= 0) {
                        break;
                    }
                    $take = min($left, $remaining[$r['id']][$component]);
                    if ($take > 0) {
                        $parts[$component] = $take;
                        $left -= $take;
                    }
                }
                if ($left > 0) {
                    $outstanding = array_sum($remaining[$r['id']]);

                    return ['error' => "Row $n: the credit is more than the member's outstanding balance on the latest bill (" . number_format($outstanding / 100, 2) . ').'];
                }
            }

            foreach ($parts as $component => $cents) {
                if ($r['type'] === 'Credit') {
                    if ($cents > $remaining[$r['id']][$component]) {
                        return ['error' => "Row $n: the " . ucfirst(strtolower($component)) . ' balance of the latest bill is ' . number_format($remaining[$r['id']][$component] / 100, 2) . '; a credit note cannot take it below zero.'];
                    }
                    $remaining[$r['id']][$component] -= $cents;
                }
                if (!isset($deltas[$r['id']])) {
                    $deltas[$r['id']] = ['billNo' => $r['billNo'], 'TAX' => 0, 'INTEREST' => 0, 'PRINCIPAL' => 0, 'PAYABLE' => 0];
                }
                $signed = ($r['type'] === 'Debit' ? 1 : -1) * $cents;
                $deltas[$r['id']][$component] += $signed;
                if ($adjustPayable) {
                    $deltas[$r['id']]['PAYABLE'] += $signed;
                }
            }
            $r['splits'] = $parts;
        }
        unset($r);

        return ['rows' => $rows, 'noteType' => $noteType, 'date' => $date, 'note' => $note, 'memberIds' => array_values($memberIds), 'deltas' => $deltas, 'adjustPayable' => $adjustPayable];
    }

    // ------------------------------------------------------------------------------------ delete

    public function delete($voucherNo = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $redirect = redirect()->route('society.debitCreditNotes');
        $voucherNo = (int) $voucherNo;

        $rows = $voucherNo > 0
            ? DebitCreditNote::where('society_id', $societyId)->where('financial_year_id', $fyId)->where('voucher_no', $voucherNo)->get()
            : collect();
        if ($rows->isEmpty()) {
            return $redirect->with('error', 'Note not found.');
        }

        $reason = $this->deleteBlockedReason($rows->all(), $societyId, $fyId);
        if ($reason !== '') {
            return $redirect->with('error', $reason);
        }

        $copies = [];
        $memberIds = [];
        $deltas = []; // the reverse of what each row did to its member's latest bill
        foreach ($rows as $row) {
            $r = $row->getAttributes();
            $copies[] = $r;
            if (empty($r['manual_component'])) {
                continue; // a row that never touched a bill
            }
            $isDebit = ($r['jv_type'] === 'Debit');
            $id = (int) ($isDebit ? $r['jv_debit_member_head_id'] : $r['jv_credit_member_head_id']);
            if ($id <= 0 || !$this->isCurrentTransfer($id, $r['member_transfer'])) {
                continue;
            }
            $memberIds[$id] = $id;
            if (!isset($deltas[$id])) {
                $deltas[$id] = ['billNo' => (int) $r['manual_bill_no'], 'TAX' => 0, 'INTEREST' => 0, 'PRINCIPAL' => 0, 'PAYABLE' => 0];
            }
            $cents = (int) round(((float) ($isDebit ? $r['jv_amount_debited'] : $r['jv_amount_credited'])) * 100);
            $reverse = ($isDebit ? -1 : 1) * $cents;
            $deltas[$id][$r['manual_component']] += $reverse;
            if (!empty($r['adjust_payable'])) {
                $deltas[$id]['PAYABLE'] += $reverse;
            }
        }

        $snapshot = $this->snapshotBills(array_values($memberIds), $societyId, $fyId);

        try {
            DB::transaction(fn () => $this->deleteVoucherRows($voucherNo, $societyId, $fyId, true));
        } catch (\Throwable $e) {
            Log::error('Debit/Credit Note delete failed (society ' . $societyId . ', voucher ' . $voucherNo . '): ' . $e->getMessage());

            return $redirect->with('error', 'The note could not be deleted. Nothing was changed.');
        }

        $failed = $this->applyDeltas($deltas, $societyId, $fyId);
        if (empty($failed)) {
            return $redirect->with('success', 'The note has been deleted.');
        }

        // Compensation: put the rows back and the latest bill rows as they were.
        foreach ($copies as $copy) {
            DB::table('journal_vouchers')->insert($copy);
        }
        $restored = $this->restoreBills($snapshot);
        Log::error('Debit/Credit Note ' . $voucherNo . ' delete could not be applied to the latest bill of members ' . implode(',', $failed) . '; note restored, bill restore ' . ($restored ? 'succeeded.' : 'FAILED.'));

        return $redirect->with('error', $restored
            ? 'The note was not deleted: the latest bill could not be updated. Nothing was changed.'
            : "The note was not deleted, but the latest bill of a member could not be fully restored. Please contact support before changing these members' bills.");
    }

    /**
     * A note may be deleted only while it still acts on the member's latest bill: a manual row must still be
     * pinned to the latest regular bill, and no row may predate the latest bill (a newer bill already carries its
     * effect forward). Otherwise reverse it with a new opposite note.
     *
     * @param  iterable<DebitCreditNote|array>  $rows
     */
    private function deleteBlockedReason(iterable $rows, int $societyId, int $fyId): string
    {
        $latest = [];
        foreach ($rows as $row) {
            $r = is_array($row) ? $row : $row->getAttributes();
            foreach (['jv_debit_member_head_id', 'jv_credit_member_head_id'] as $f) {
                $id = (int) $r[$f];
                if ($id <= 0 || !$this->isCurrentTransfer($id, $r['member_transfer'])) {
                    continue;
                }
                if (!array_key_exists($id, $latest)) {
                    $latest[$id] = $this->latestRegularBill($id, $societyId, $fyId);
                }
                $bill = $latest[$id];
                if (empty($bill)) {
                    if (!empty($r['manual_component'])) {
                        return 'This note cannot be deleted: the bill it was applied to no longer exists.';
                    }
                    continue;
                }
                if (!empty($r['manual_component']) && (int) $r['manual_bill_no'] !== (int) $bill['bill_no']) {
                    return 'This note cannot be deleted: a newer bill has been generated for the member since it was applied. Enter an opposite note to reverse it.';
                }
                if (strtotime($r['voucher_date']) < strtotime($bill['bill_generated_date'])) {
                    return 'This note cannot be deleted: a newer bill has been generated for the member since it was made. Enter an opposite note to reverse it.';
                }
            }
        }

        return '';
    }

    private function deleteVoucherRows(int $voucherNo, int $societyId, int $fyId, bool $throw = false): bool
    {
        $ids = DebitCreditNote::where('society_id', $societyId)->where('financial_year_id', $fyId)->where('voucher_no', $voucherNo)->pluck('id')->all();
        if (empty($ids)) {
            return true;
        }

        $ok = DebitCreditNote::whereIn('id', $ids)->delete() > 0;
        if (!$ok && $throw) {
            throw new \RuntimeException('The note rows could not be deleted.');
        }

        return $ok;
    }

    // ------------------------------------------------------------------------------------ bills

    /**
     * The member's latest regular bill row, identified the way the rest of the system does it: bill_generated_date
     * DESC, id DESC - never MAX(id) - scoped by member + society + bill type + current transfer + financial year.
     */
    private function latestBillRow($memberId, int $societyId, int $fyId): array
    {
        $transfer = Member::where('id', $memberId)->value('member_transfer') ?? 0;

        $bill = DB::table('member_bill_summaries')
            ->where('member_id', $memberId)->where('society_id', $societyId)->where('bill_type', 'reg')
            ->where('member_transfer', $transfer)->where('financial_year_id', $fyId)
            ->orderByDesc('bill_generated_date')->orderByDesc('id')
            ->first();

        return $bill ? (array) $bill : [];
    }

    private function latestRegularBill($memberId, int $societyId, int $fyId): array
    {
        return $this->latestBillRow($memberId, $societyId, $fyId);
    }

    private function isCurrentTransfer($memberId, $rowTransfer): bool
    {
        $current = (int) (Member::where('id', $memberId)->value('member_transfer') ?? 0);

        return (int) $rowTransfer === $current;
    }

    private function parseCurrentMemberKey(string $key): int
    {
        if (preg_match('/^member-(\d+)$/', $key, $m)) {
            $list = $this->memberList($this->societyId());
            if (isset($list[(int) $m[1]])) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /**
     * The order the society settles a payment / credit in (the BillSettlementOrder the bill engine reads): Tax,
     * Interest, Principal unless the society configured another order.
     */
    private function settleOrder(int $societyId): array
    {
        $settleOrder = DB::table('bill_settlement_order')->where('is_active', 1)->where('society_id', $societyId)->value('settle_order');

        $names = ['Tax', 'Interest', 'Principle'];
        if (!empty($settleOrder)) {
            $names = explode(',', $settleOrder);
        }

        $map = ['tax' => 'TAX', 'interest' => 'INTEREST', 'principle' => 'PRINCIPAL', 'principal' => 'PRINCIPAL'];
        $order = [];
        foreach ($names as $name) {
            $key = strtolower(trim($name));
            if (isset($map[$key]) && !in_array($map[$key], $order, true)) {
                $order[] = $map[$key];
            }
        }
        foreach (self::COMPONENTS as $component) {
            if (!in_array($component, $order, true)) {
                $order[] = $component;
            }
        }

        return $order;
    }

    /**
     * Apply the notes' signed component amounts to each member's LATEST bill, in place: the three component
     * balances and balance_amount of that one row, and amount_payable too when the note asked for it. Nothing else
     * is recalculated and no older bill is touched. The same amounts are re-applied by the bill engine on any
     * later full rebuild, because the journal rows remain the source of truth. Returns the member ids it could not
     * update. Cents in, so nothing accumulates float error.
     */
    private function applyDeltas(array $deltas, int $societyId, int $fyId): array
    {
        $failed = [];
        foreach ($deltas as $memberId => $d) {
            try {
                $bill = $this->latestBillRow($memberId, $societyId, $fyId);
                if (empty($bill) || (int) $bill['bill_no'] !== (int) $d['billNo']) {
                    $failed[] = $memberId; // a newer bill appeared meanwhile: refuse rather than adjust the wrong bill
                    continue;
                }

                $dT = $d['TAX'] / 100;
                $dI = $d['INTEREST'] / 100;
                $dP = $d['PRINCIPAL'] / 100;
                $total = $dT + $dI + $dP;           // balance
                $payable = $d['PAYABLE'] / 100;     // amount_payable: only notes that asked for it

                $ok = DB::table('member_bill_summaries')->where('id', $bill['id'])->update([
                    'tax_balance' => round((float) $bill['tax_balance'] + $dT, 2),
                    'interest_balance' => round((float) $bill['interest_balance'] + $dI, 2),
                    'principal_balance' => round((float) $bill['principal_balance'] + $dP, 2),
                    'balance_amount' => round((float) $bill['balance_amount'] + $total, 2),
                    'amount_payable' => round((float) $bill['amount_payable'] + $payable, 2),
                ]);
                // update() returns the number of changed rows; a note that nets to zero changes nothing and is fine
                if ($ok === false) {
                    $failed[] = $memberId;
                }
            } catch (\Throwable $e) {
                Log::error('Debit/Credit Note: applying to the latest bill failed for member ' . $memberId . ': ' . $e->getMessage());
                $failed[] = $memberId;
            }
        }

        return $failed;
    }

    /** The latest bill row of each member, as it is now (for the compensation step). */
    private function snapshotBills(array $memberIds, int $societyId, int $fyId): array
    {
        $rows = [];
        foreach ($memberIds as $memberId) {
            $bill = $this->latestBillRow($memberId, $societyId, $fyId);
            if (!empty($bill)) {
                $rows[] = $bill;
            }
        }

        return $rows;
    }

    /** Last-resort compensation: put the bill rows captured before the change back. Must never throw. */
    private function restoreBills(array $snapshot): bool
    {
        if (empty($snapshot)) {
            return true;
        }

        try {
            foreach ($snapshot as $bill) {
                $id = $bill['id'];
                unset($bill['id']);
                DB::table('member_bill_summaries')->where('id', $id)->update($bill);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Debit/Credit Note: restoring bill rows failed: ' . $e->getMessage());

            return false;
        }
    }

    // ------------------------------------------------------------------------------------ list

    private function noteList(int $societyId, int $fyId, array $memberList, array $flatNoList): array
    {
        $rows = DebitCreditNote::where('society_id', $societyId)->where('financial_year_id', $fyId)
            ->orderBy('voucher_no')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $ledgerTitles = SocietyLedgerHead::where('society_id', $societyId)->pluck('title', 'id')->all();

        $vouchers = [];
        foreach ($rows as $row) {
            $r = $row->getAttributes();
            $no = $r['voucher_no'];
            if (!isset($vouchers[$no])) {
                $vouchers[$no] = [
                    'voucher_no' => $no,
                    'label' => $r['entry_type'] . '-' . str_pad((string) $no, 5, '0', STR_PAD_LEFT),
                    'type' => $r['entry_type'] === 'DN' ? 'Debit Note' : 'Credit Note',
                    'date' => date('d/m/Y', strtotime($r['voucher_date'])),
                    'note' => $r['note'],
                    'lines' => [],
                    'rows' => [],
                ];
            }

            $isDebit = $r['jv_type'] === 'Debit';
            $memberId = (int) ($isDebit ? $r['jv_debit_member_head_id'] : $r['jv_credit_member_head_id']);
            $ledgerId = (int) ($isDebit ? $r['jv_debit_ledger_head_id'] : $r['jv_credit_ledger_head_id']);

            if ($memberId > 0) {
                $title = $memberList[$memberId] ?? 'Member #' . $memberId;
                if (isset($flatNoList[$memberId]) && $flatNoList[$memberId] !== '') {
                    $title .= ' -- ' . $flatNoList[$memberId];
                }
            } else {
                $title = $ledgerTitles[$ledgerId] ?? 'Account #' . $ledgerId;
            }

            $adjust = 'Auto';
            if (!empty($r['manual_component'])) {
                $adjust = ucfirst(strtolower($r['manual_component'])) . ' (bill #' . $r['manual_bill_no'] . ')' . (!empty($r['adjust_payable']) ? ' + Amount Payable' : '');
            }

            $vouchers[$no]['lines'][] = [
                'type' => $r['jv_type'],
                'account' => $title,
                'is_member' => $memberId > 0,
                'adjust' => $memberId > 0 ? $adjust : '',
                'debit' => $isDebit ? $r['jv_amount_debited'] : '',
                'credit' => $isDebit ? '' : $r['jv_amount_credited'],
            ];
            $vouchers[$no]['rows'][] = $r;
        }

        foreach ($vouchers as $no => $v) {
            $reason = $this->deleteBlockedReason($v['rows'], $societyId, $fyId);
            $vouchers[$no]['can_delete'] = ($reason === '');
            $vouchers[$no]['blocked_reason'] = $reason;
            unset($vouchers[$no]['rows']);
        }

        return array_values($vouchers);
    }
}
