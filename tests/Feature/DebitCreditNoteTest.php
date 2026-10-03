<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BillSettlementService;
use App\Support\JournalNotes;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Debit Note / Credit Note (CakePHP DebitCreditNotesController + the bill-engine hooks in SocietyBillsController).
 *
 * Needs a scratch copy of the CakePHP database WITH society, member and bill data (it writes note rows and edits the
 * latest bill of one member, and puts everything back afterwards) - it refuses to run against any database whose name
 * does not contain "scratch". Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/DebitCreditNoteTest.php
 */
class DebitCreditNoteTest extends TestCase
{
    private const TAG = 'zzdcn';

    private User $society;
    private object $member;      // members row
    private array $fy;           // ['id' => , 'start' => , 'end' => ]
    private array $snapshot = [];    // member_bill_summaries rows of the member, restored afterwards
    private array $settlements = []; // member_bill_settlements rows of the member's payments, restored afterwards
    private array $paymentIds = [];
    private object $ledger;      // a ledger head of the society that may be used on a note

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }
        if (!JournalNotes::hasNoteColumns()) {
            $this->markTestSkipped('The scratch database has not been migrated for Debit/Credit Notes.');
        }

        // a member with a current-transfer regular bill that has Tax, Interest and Principal all outstanding
        $bill = DB::table('member_bill_summaries as b')
            ->join('members as m', function ($j) {
                $j->on('m.id', '=', 'b.member_id')->on('m.member_transfer', '=', 'b.member_transfer')->on('m.society_id', '=', 'b.society_id');
            })
            ->join('users as u', 'u.id', '=', 'b.society_id')
            ->where('u.role', 'Society')->where('m.status', 1)->where('b.bill_type', 'reg')
            ->where('b.tax_balance', '>', 20)->where('b.interest_balance', '>', 20)->where('b.principal_balance', '>', 200)
            ->whereRaw('b.id = (select id from member_bill_summaries x where x.member_id=b.member_id and x.society_id=b.society_id and x.bill_type="reg" and x.member_transfer=b.member_transfer and x.financial_year_id=b.financial_year_id order by x.bill_generated_date desc, x.id desc limit 1)')
            ->orderByDesc('b.id')->select('b.*')->first();
        if (!$bill) {
            $this->markTestSkipped('No member with Tax, Interest and Principal outstanding in the scratch database.');
        }

        $this->society = User::findOrFail($bill->society_id);
        $this->member = DB::table('members')->where('id', $bill->member_id)->first();
        $fy = DB::table('financial_year_master')->where('id', $bill->financial_year_id)->first();
        $this->fy = ['id' => (int) $fy->id, 'start' => $fy->year_start_date, 'end' => $fy->year_end_date];
        $this->ledger = DB::table('society_ledger_heads')->where('society_id', $this->society->id)->where('status', 1)
            ->whereNotIn('society_head_sub_category_id', [20, 21])->orderBy('id')->first();

        $this->snapshot = DB::table('member_bill_summaries')->where('member_id', $this->member->id)->where('society_id', $this->society->id)->get()->map(fn ($r) => (array) $r)->all();
        $this->paymentIds = DB::table('member_payments')->where('member_id', $this->member->id)->pluck('id')->all();
        $this->settlements = DB::table('member_bill_settlements')->whereIn('payment_id', $this->paymentIds ?: [0])->get()->map(fn ($r) => (array) $r)->all();

        $this->cleanNotes();
    }

    protected function tearDown(): void
    {
        if (isset($this->snapshot) && str_contains((string) config('database.connections.mysql.database'), 'scratch') && !empty($this->snapshot)) {
            $this->cleanNotes();
            foreach ($this->snapshot as $row) {
                $id = $row['id'];
                unset($row['id']);
                DB::table('member_bill_summaries')->where('id', $id)->update($row);
            }
            DB::table('member_bill_settlements')->whereIn('payment_id', $this->paymentIds ?: [0])->delete();
            foreach ($this->settlements as $row) {
                DB::table('member_bill_settlements')->insert($row);
            }
        }

        parent::tearDown();
    }

    private function cleanNotes(): void
    {
        DB::table('journal_vouchers')->where('note', 'like', self::TAG . '%')->delete();
    }

    // ---------------------------------------------------------------- helpers

    private function fySession(): array
    {
        return ['fy.year_id' => $this->fy['id'], 'fy.year_start_date' => $this->fy['start'], 'fy.year_end_date' => $this->fy['end']];
    }

    private function latest(): object
    {
        return DB::table('member_bill_summaries')->where('member_id', $this->member->id)->where('society_id', $this->society->id)
            ->where('bill_type', 'reg')->where('member_transfer', $this->member->member_transfer)->where('financial_year_id', $this->fy['id'])
            ->orderByDesc('bill_generated_date')->orderByDesc('id')->first();
    }

    private function bal(object $b): array
    {
        return [round((float) $b->tax_balance, 2), round((float) $b->interest_balance, 2), round((float) $b->principal_balance, 2), round((float) $b->balance_amount, 2), round((float) $b->amount_payable, 2)];
    }

    /** the voucher date: today, but never before the member's latest bill (and inside the financial year) */
    private function date(): string
    {
        $d = max(strtotime($this->latest()->bill_generated_date), strtotime($this->fy['start']));

        return date('Y-m-d', min($d + 86400, strtotime($this->fy['end'])));
    }

    private function member(): string
    {
        return 'member-' . $this->member->id;
    }

    private function ledger(): string
    {
        return 'ledger-' . $this->ledger->id;
    }

    /** POST a note. $rows: [type, account, adjust, amount] */
    private function note(string $type, array $rows, array $extra = [])
    {
        $in = ['note_type' => $type, 'voucher_date' => $this->date(), 'note' => self::TAG, 'type' => [], 'account' => [], 'adjust' => [], 'debit' => [], 'credit' => []];
        foreach ($rows as [$t, $acct, $adjust, $amount]) {
            $in['type'][] = $t;
            $in['account'][] = $acct;
            $in['adjust'][] = $adjust;
            $in['debit'][] = $t === 'Debit' ? $amount : '0';
            $in['credit'][] = $t === 'Credit' ? $amount : '0';
        }

        return $this->actingAs($this->society)->withSession($this->fySession())->post(route('society.debitCreditNotes'), ['DebitCreditNote' => $extra + $in]);
    }

    private function noteRows(): \Illuminate\Support\Collection
    {
        return DB::table('journal_vouchers')->where('note', 'like', self::TAG . '%')->orderBy('id')->get();
    }

    private function rebuild(): void
    {
        app(BillSettlementService::class)->recalculateMemberBills($this->member->id, $this->society->id, 'reg', $this->fy['id'], $this->member->member_transfer);
    }

    // ---------------------------------------------------------------- screen

    public function test_the_screen_and_menu_item_load(): void
    {
        $html = $this->actingAs($this->society)->withSession($this->fySession())->get(route('society.debitCreditNotes'))->assertOk()
            ->assertSee('Debit Note / Credit Note')->assertSee('Society Members List')->assertSee('Society Ledger List')
            ->assertSee('member-' . $this->member->id, false)->getContent();

        $this->assertStringContainsString('>Debit Note / Credit Note</a>', $html);
        $this->assertStringContainsString('name="DebitCreditNote[note_type]"', $html);
    }

    public function test_only_a_society_may_open_it(): void
    {
        $this->get(route('society.debitCreditNotes'))->assertRedirect(route('login'));
        $this->actingAs(User::where('role', 'Admin')->firstOrFail())->get(route('society.debitCreditNotes'))->assertForbidden();
    }

    public function test_latest_bill_json_reports_the_latest_regular_bill(): void
    {
        $b = $this->latest();

        $this->actingAs($this->society)->withSession($this->fySession())->getJson(route('society.debitCreditNotes.latestBill', $this->member()))
            ->assertOk()->assertJson([
                'found' => 1, 'bill_no' => (int) $b->bill_no,
                'tax' => round((float) $b->tax_balance, 2), 'interest' => round((float) $b->interest_balance, 2),
                'principal' => round((float) $b->principal_balance, 2), 'balance' => round((float) $b->balance_amount, 2),
            ]);

        $this->getJson(route('society.debitCreditNotes.latestBill', 'member-999999999'))->assertOk()->assertExactJson(['found' => 0]);
        $this->getJson(route('society.debitCreditNotes.latestBill', 'ledger-1'))->assertOk()->assertExactJson(['found' => 0]);
    }

    // ---------------------------------------------------------------- Debit Note

    public function test_an_auto_debit_note_adds_to_principal_of_the_latest_bill_only(): void
    {
        $before = $this->latest();
        $olderBefore = DB::table('member_bill_summaries')->where('member_id', $this->member->id)->where('id', '!=', $before->id)->get()->map(fn ($r) => (array) $r)->all();

        $this->note('DN', [['Debit', $this->member(), 'AUTO', '150.50'], ['Credit', $this->ledger(), 'AUTO', '150.50']])
            ->assertRedirect(route('society.debitCreditNotes'))->assertSessionHas('success');

        $after = $this->latest();
        $this->assertEquals([$before->tax_balance, $before->interest_balance, round($before->principal_balance + 150.5, 2), round($before->balance_amount + 150.5, 2), $before->amount_payable],
            [$after->tax_balance, $after->interest_balance, $after->principal_balance, $after->balance_amount, $after->amount_payable]);
        $this->assertSame($olderBefore, DB::table('member_bill_summaries')->where('member_id', $this->member->id)->where('id', '!=', $before->id)->get()->map(fn ($r) => (array) $r)->all(), 'older bills are never touched');

        $rows = $this->noteRows();
        $this->assertCount(2, $rows);
        $this->assertSame(['DN', 'DN'], $rows->pluck('entry_type')->all());
        $this->assertSame(1, $rows->pluck('voucher_no')->unique()->count());
        [$memberRow, $ledgerRow] = $rows[0]->jv_debit_member_head_id ? [$rows[0], $rows[1]] : [$rows[1], $rows[0]];
        $this->assertSame('PRINCIPAL', $memberRow->manual_component);
        $this->assertSame((int) $before->bill_no, (int) $memberRow->manual_bill_no);
        $this->assertEquals(150.5, (float) $memberRow->jv_amount_debited);
        $this->assertSame((int) $this->member->member_transfer, (int) $memberRow->member_transfer);
        $this->assertNull($ledgerRow->manual_component);
        $this->assertSame((int) $this->ledger->id, (int) $ledgerRow->jv_credit_ledger_head_id);
        $this->assertEquals(150.5, (float) $ledgerRow->jv_amount_credited);
    }

    public function test_adjust_payable_moves_the_amount_payable_only_when_asked(): void
    {
        $before = $this->latest();

        $this->note('DN', [['Debit', $this->member(), 'INTEREST', '40'], ['Credit', $this->ledger(), 'AUTO', '40']], ['adjust_payable' => '1'])->assertSessionHas('success');

        $after = $this->latest();
        $this->assertEquals([round($before->interest_balance + 40, 2), round($before->amount_payable + 40, 2), $before->principal_balance], [(float) $after->interest_balance, (float) $after->amount_payable, (float) $after->principal_balance]);
        $this->assertSame(1, (int) $this->noteRows()->firstWhere('manual_component', 'INTEREST')->adjust_payable);
    }

    // ---------------------------------------------------------------- Credit Note

    public function test_an_auto_credit_note_settles_tax_then_interest_then_principal(): void
    {
        $before = $this->latest();
        $tax = round((float) $before->tax_balance, 2);
        $interest = round((float) $before->interest_balance, 2);
        $amount = round($tax + $interest + 10, 2);   // clears Tax and Interest, then 10 into Principal

        $this->note('CN', [['Credit', $this->member(), 'AUTO', (string) $amount], ['Debit', $this->ledger(), 'AUTO', (string) $amount]])->assertSessionHas('success');

        $after = $this->latest();
        $this->assertEquals([0.0, 0.0, round($before->principal_balance - 10, 2), round($before->balance_amount - $amount, 2)],
            [(float) $after->tax_balance, (float) $after->interest_balance, (float) $after->principal_balance, (float) $after->balance_amount]);

        $memberRows = $this->noteRows()->where('jv_credit_member_head_id', $this->member->id);
        $this->assertEquals(['TAX' => $tax, 'INTEREST' => $interest, 'PRINCIPAL' => 10.0],
            $memberRows->mapWithKeys(fn ($r) => [$r->manual_component => (float) $r->jv_amount_credited])->all(), 'one row per component, adding up to the amount');
        $this->assertEquals($amount, round($memberRows->sum(fn ($r) => (float) $r->jv_amount_credited), 2));
        $this->assertEquals($amount, round((float) $this->noteRows()->where('jv_debit_ledger_head_id', $this->ledger->id)->sum('jv_amount_debited'), 2));
    }

    public function test_a_manual_credit_note_takes_only_the_chosen_component(): void
    {
        $before = $this->latest();

        $this->note('CN', [['Credit', $this->member(), 'INTEREST', '10'], ['Debit', $this->ledger(), 'AUTO', '10']])->assertSessionHas('success');

        $after = $this->latest();
        $this->assertEquals([$before->tax_balance, round($before->interest_balance - 10, 2), $before->principal_balance, round($before->balance_amount - 10, 2)],
            [$after->tax_balance, $after->interest_balance, $after->principal_balance, $after->balance_amount]);
    }

    public function test_a_credit_note_cannot_take_a_component_below_zero_or_exceed_the_outstanding(): void
    {
        $b = $this->latest();
        $tooMuchTax = (string) round($b->tax_balance + 1, 2);

        $this->note('CN', [['Credit', $this->member(), 'TAX', $tooMuchTax], ['Debit', $this->ledger(), 'AUTO', $tooMuchTax]])
            ->assertSessionHas('error', 'Row 1: the Tax balance of the latest bill is ' . number_format($b->tax_balance, 2) . '; a credit note cannot take it below zero.');

        $all = round($b->tax_balance + $b->interest_balance + $b->principal_balance + 1, 2);
        $this->note('CN', [['Credit', $this->member(), 'AUTO', (string) $all], ['Debit', $this->ledger(), 'AUTO', (string) $all]])
            ->assertSessionHas('error', "Row 1: the credit is more than the member's outstanding balance on the latest bill (" . number_format($b->tax_balance + $b->interest_balance + $b->principal_balance, 2) . ').');

        $this->assertCount(0, $this->noteRows());
    }

    // ---------------------------------------------------------------- validation (all server side)

    public function test_validation_messages_match_cake(): void
    {
        $m = $this->member();
        $l = $this->ledger();
        $dn = [['Debit', $m, 'AUTO', '10'], ['Credit', $l, 'AUTO', '10']];

        $this->note('XX', $dn)->assertSessionHas('error', 'Please choose Debit Note or Credit Note.');

        $this->actingAs($this->society)->withSession($this->fySession())
            ->post(route('society.debitCreditNotes'), ['DebitCreditNote' => ['note_type' => 'DN', 'voucher_date' => 'nope', 'type' => ['Debit', 'Credit'], 'account' => [$m, $l], 'debit' => ['10', '0'], 'credit' => ['0', '10']]])
            ->assertSessionHas('error', 'Please enter a valid voucher date.');
        $this->actingAs($this->society)->withSession($this->fySession())
            ->post(route('society.debitCreditNotes'), ['DebitCreditNote' => ['note_type' => 'DN', 'voucher_date' => date('Y-m-d', strtotime($this->fy['end']) + 86400 * 400), 'type' => ['Debit', 'Credit'], 'account' => [$m, $l], 'debit' => ['10', '0'], 'credit' => ['0', '10']]])
            ->assertSessionHas('error', 'The voucher date must be within the current financial year.');

        $this->note('DN', [['Debit', $m, 'AUTO', '10']])->assertSessionHas('error', 'A note needs at least two rows.');
        $this->note('DN', [['Debit', $m, 'AUTO', '10'], ['Credit', $l, 'AUTO', '11']])->assertSessionHas('error', 'Total Debit and total Credit must be equal.');
        $this->note('DN', [['Debit', $m, 'AUTO', '0'], ['Credit', $l, 'AUTO', '0']])->assertSessionHas('error', 'Row 1: enter an amount greater than zero (at most two decimals).');
        $this->note('DN', [['Debit', $m, 'AUTO', '1.234'], ['Credit', $l, 'AUTO', '1.234']])->assertSessionHas('error', 'Row 1: enter an amount greater than zero (at most two decimals).');
        $this->note('DN', [['Debit', '', 'AUTO', '10'], ['Credit', $l, 'AUTO', '10']])->assertSessionHas('error', 'Row 1: choose an account.');
        $this->note('DN', [['Debit', 'member-999999999', 'AUTO', '10'], ['Credit', $l, 'AUTO', '10']])->assertSessionHas('error', 'Row 1: the selected member is not valid for this society.');
        $this->note('DN', [['Debit', 'ledger-999999999', 'AUTO', '10'], ['Credit', $l, 'AUTO', '10']])->assertSessionHas('error', 'Row 1: the selected account is not valid (Bank and Cash accounts are not allowed here).');
        $this->note('DN', [['Debit', $m, 'BOGUS', '10'], ['Credit', $l, 'AUTO', '10']])->assertSessionHas('error', 'Row 1: choose Auto, Tax, Interest or Principal.');

        $needsMember = 'Debit Note / Credit Note must contain at least one Member transaction. Other Head to Other Head transactions are not allowed.';
        $this->note('DN', [['Debit', $l, 'AUTO', '10'], ['Credit', $l, 'AUTO', '10']])->assertSessionHas('error', $needsMember);

        $this->note('DN', [['Debit', $l, 'AUTO', '10'], ['Credit', $m, 'AUTO', '10']])
            ->assertSessionHas('error', 'A Debit Note must debit at least one Member.');
        $this->note('CN', [['Credit', $l, 'AUTO', '10'], ['Debit', $m, 'AUTO', '10']])
            ->assertSessionHas('error', 'A Credit Note must credit at least one Member.');
        $this->note('DN', [['Debit', $m, 'AUTO', '30'], ['Credit', $m, 'AUTO', '10'], ['Credit', $l, 'AUTO', '30'], ['Debit', $l, 'AUTO', '10']])
            ->assertSessionHas('error', 'In a Debit Note an Other Head can only be credited (the Member is debited). To debit an Other Head, use a Credit Note.');
        $this->note('CN', [['Credit', $m, 'AUTO', '30'], ['Debit', $m, 'AUTO', '10'], ['Debit', $l, 'AUTO', '30'], ['Credit', $l, 'AUTO', '10']])
            ->assertSessionHas('error', 'In a Credit Note an Other Head can only be debited (the Member is credited). To credit an Other Head, use a Debit Note.');

        $this->assertCount(0, $this->noteRows());
    }

    public function test_the_voucher_date_cannot_precede_the_latest_bill(): void
    {
        $b = $this->latest();
        $early = date('Y-m-d', strtotime($b->bill_generated_date) - 86400);
        if (strtotime($early) < strtotime($this->fy['start'])) {
            $this->markTestSkipped('The latest bill is generated on the first day of the financial year.');
        }

        $this->actingAs($this->society)->withSession($this->fySession())
            ->post(route('society.debitCreditNotes'), ['DebitCreditNote' => ['note_type' => 'DN', 'voucher_date' => $early, 'note' => self::TAG, 'type' => ['Debit', 'Credit'], 'account' => [$this->member(), $this->ledger()], 'adjust' => ['AUTO', 'AUTO'], 'debit' => ['10', '0'], 'credit' => ['0', '10']]])
            ->assertSessionHas('error', "Row 1: the voucher date cannot be earlier than the member's latest bill date (" . date('d/m/Y', strtotime($b->bill_generated_date)) . ').');
    }

    // ---------------------------------------------------------------- the engine keeps a note across rebuilds

    /**
     * The journal rows are the source of truth: a full rebuild of the member's bills (what every payment add / edit /
     * delete triggers) gives the same latest bill as the in-place update the note made.
     */
    public function test_a_rebuild_of_the_bills_keeps_the_notes_effect(): void
    {
        $this->rebuild();
        $base = $this->latest();

        // one DN (auto principal), one manual CN on Interest with Amount Payable, one auto CN across components
        $this->note('DN', [['Debit', $this->member(), 'AUTO', '75'], ['Credit', $this->ledger(), 'AUTO', '75']])->assertSessionHas('success');
        $this->note('CN', [['Credit', $this->member(), 'INTEREST', '5'], ['Debit', $this->ledger(), 'AUTO', '5']], ['adjust_payable' => '1'])->assertSessionHas('success');
        $this->note('CN', [['Credit', $this->member(), 'AUTO', '12.34'], ['Debit', $this->ledger(), 'AUTO', '12.34']])->assertSessionHas('success');

        $applied = $this->latest();
        $jvAdjBefore = (float) $applied->jv_adjustment;

        $this->rebuild();
        $rebuilt = $this->latest();

        $this->assertSame($this->bal($applied), $this->bal($rebuilt), 'rebuilding the bills must reproduce the balances the notes set');
        $this->assertSame($jvAdjBefore, (float) $rebuilt->jv_adjustment, 'manual note rows are not double counted as ordinary JV debits');
        $this->assertNotSame($this->bal($base), $this->bal($rebuilt));

        // ... and deleting them all brings back the bill the engine computes without any note
        foreach (DB::table('journal_vouchers')->where('note', self::TAG)->pluck('voucher_no')->unique()->sortDesc() as $no) {
            $this->actingAs($this->society)->withSession($this->fySession())->post(route('society.debitCreditNotes.delete', $no))->assertSessionHas('success', 'The note has been deleted.');
        }
        $this->assertCount(0, $this->noteRows());
        $this->assertSame($this->bal($base), $this->bal($this->latest()), 'deleting the notes reverses them exactly');
        $this->rebuild();
        $this->assertSame($this->bal($base), $this->bal($this->latest()));
    }

    // ---------------------------------------------------------------- delete rules / Journal Voucher separation

    public function test_a_note_is_locked_once_a_newer_bill_exists(): void
    {
        $this->note('DN', [['Debit', $this->member(), 'AUTO', '20'], ['Credit', $this->ledger(), 'AUTO', '20']])->assertSessionHas('success');
        $voucherNo = $this->noteRows()->first()->voucher_no;

        // pretend a newer bill was generated: the pinned bill number no longer is the latest
        DB::table('journal_vouchers')->where('note', self::TAG)->whereNotNull('manual_component')->update(['manual_bill_no' => 99999]);

        $this->actingAs($this->society)->withSession($this->fySession())->get(route('society.debitCreditNotes'))->assertOk()->assertSee('fa-lock', false);
        $this->post(route('society.debitCreditNotes.delete', $voucherNo))
            ->assertSessionHas('error', 'This note cannot be deleted: a newer bill has been generated for the member since it was applied. Enter an opposite note to reverse it.');
        $this->assertCount(2, $this->noteRows());

        $this->post(route('society.debitCreditNotes.delete', 987654))->assertSessionHas('error', 'Note not found.');
    }

    public function test_notes_never_show_up_on_or_change_through_the_journal_voucher_screen(): void
    {
        $this->note('DN', [['Debit', $this->member(), 'AUTO', '20'], ['Credit', $this->ledger(), 'AUTO', '20']])->assertSessionHas('success');
        $rows = $this->noteRows();
        $voucherNo = $rows->first()->voucher_no;

        // not listed / not opened for edit on the Journal Voucher screen
        $page = $this->actingAs($this->society)->withSession($this->fySession())->get(route('society.journalVoucher'))->assertOk();
        $items = $page->viewData('items');
        $this->assertTrue($items->every(fn ($i) => $i->entry_type === 'JV'), 'only ordinary journal vouchers are listed');
        $this->assertFalse($items->contains('note', self::TAG));
        $edit = $this->get(route('society.journalVoucher', $voucherNo))->assertOk();
        $this->assertTrue($edit->viewData('editRows')->isEmpty(), 'a note is never opened for edit on the Journal Voucher screen');

        // the JV delete route cannot remove a note (same voucher number)
        $this->delete(route('society.deleteJournalVoucher', $voucherNo));
        $this->assertCount(2, $this->noteRows());

        // a crafted JV edit carrying a note row id cannot update it
        $row = $rows->first();
        $this->post(route('society.journalVoucher'), [
            'JournalVoucher' => ['voucher_no' => $voucherNo, 'voucher_date' => $this->date(), 'note' => self::TAG . 'hacked'],
            $row->id => ['JournalVoucher' => ['jv_type' => 'Debit', 'jv_member_head_id' => $this->member->id, 'jv_ledger_head_id' => '', 'jv_amount_debited' => '999', 'jv_amount_credited' => '0']],
        ]);
        $this->assertEquals((float) $row->jv_amount_debited, (float) DB::table('journal_vouchers')->where('id', $row->id)->value('jv_amount_debited'));
    }

    public function test_the_debit_credit_note_model_only_sees_notes(): void
    {
        $this->note('DN', [['Debit', $this->member(), 'AUTO', '20'], ['Credit', $this->ledger(), 'AUTO', '20']])->assertSessionHas('success');
        $jv = DB::table('journal_vouchers')->where('entry_type', 'JV')->first();

        $this->assertNotNull($jv);
        $this->assertNull(\App\Models\DebitCreditNote::find($jv->id), 'a Journal Voucher is invisible to the note model');
        $this->assertSame(2, \App\Models\DebitCreditNote::where('note', self::TAG)->count());
        $this->assertSame(0, \App\Models\DebitCreditNote::where('id', $jv->id)->delete());
        $this->assertTrue(DB::table('journal_vouchers')->where('id', $jv->id)->exists());
    }
}
