<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Reports\BillSummaryUpdateReport;
use App\Support\MemberClosing;
use App\Support\OldMembers;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Port of the CakePHP fixes of 2026-10-11 (transferred units, old members, Debit/Credit Note search + labels, the
 * single-bill PDF, Bill Summary Update, the Admin Bill Monitor page):
 *
 *  - App\Support\OldMembers      : a transferred unit with no identification row is still an "Old Member"
 *  - App\Support\MemberClosing   : the new owner does not inherit the old owner's closing row
 *  - getMemberDetails            : Old Member tab = year-wise, opening from the old owner's first bill, bill ids + year
 *  - Debit/Credit Note           : "(Old Member)" hint on a refused credit, "(New/Old Member)" labels in the list
 *  - society.billPdf             : one bill as a PDF (inline / ?download=1), own society only, "last received" period
 *  - BillSummaryUpdateReport     : bill-level principal adjustment counts in the closing
 *  - Admin > Reports > Bill Summary Update page, off the dashboard
 *  - deleting bills keeps the identification (transfer) rows (see AllGeneratedBillsTest)
 *
 * Needs a scratch copy of the CakePHP database (name must contain "scratch"); everything here only READS except the
 * adjustment test, which changes one bill's monthly_principal_amount and always puts it back. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/CakeFixesPortTest.php
 */
class CakeFixesPortTest extends TestCase
{
    private const CENTURY = 103955;       // society with 4 transferred units that have no identification row
    private const JAY_LAXMI = 171;        // society with bill head lines in the scratch copy
    private const FY = 22;
    private const FY_START = '2026-04-01';
    private const FY_END = '2027-03-31';

    // E WING-304 / 602 / 1603 / 2305 of Century (transferred, no identification row) and an ordinary unit
    private const U304 = 27817347;
    private const U602 = 27817381;
    private const U1603 = 27817494;
    private const U2305 = 27823288;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }
        if (!DB::table('members')->where('id', self::U602)->exists()) {
            $this->markTestSkipped('The scratch copy has no Century (society 103955) fixture.');
        }
    }

    /** an ordinary unit of Century (never transferred) */
    private function plainMemberId(): int
    {
        return (int) DB::table('members')->where('society_id', self::CENTURY)->where('member_transfer', 0)->where('status', 1)->orderBy('id')->value('id');
    }

    private function fySession(): array
    {
        return ['fy.year_id' => self::FY, 'fy.year_start_date' => self::FY_START, 'fy.year_end_date' => self::FY_END];
    }

    private function asSociety(int $id = self::CENTURY)
    {
        return $this->actingAs(User::findOrFail($id))->withSession($this->fySession());
    }

    /** the Old / Current member ledger the Flat/Shop modal gets: [opening, closing, rows] */
    private function memberDetails(int $memberId, int $old, int $society = self::CENTURY): array
    {
        $d = $this->asSociety($society)->postJson(route('society.getMemberDetails'), ['member_id' => $memberId, 'oldMember' => $old])->assertOk()->json();
        $m = $d['Member'];
        $opening = (float) $m['op_principal'] + (float) $m['op_interest'] + (float) $m['op_tax']
            + (float) $m['supplementary_principal'] + (float) $m['supplementary_interest'] + (float) $m['supplementary_tax'];
        $balance = $opening;
        foreach ($d['memberBillPayment'] as $r) {
            $balance += (float) $r['debit'] - (float) $r['credit'];
        }

        return [$opening, $balance, $d];
    }

    // ------------------------------------------------------------------------------------------ old members list

    public function test_a_transferred_unit_without_identification_rows_is_an_old_member(): void
    {
        $rows = OldMembers::forSociety(self::CENTURY);
        $ids = array_map(fn ($r) => (int) $r->member_id, $rows);

        $this->assertCount(4, $rows);
        foreach ([self::U304, self::U602, self::U1603, self::U2305] as $id) {
            $this->assertContains($id, $ids);
        }
        $this->assertSame(count($ids), count(array_unique($ids)), 'no unit is listed twice');
        $this->assertSame(
            DB::table('members')->where('society_id', self::CENTURY)->where('member_transfer', '>', 0)->where('status', 1)->count(),
            count($rows),
            'every transferred unit is listed'
        );
    }

    public function test_units_that_have_identification_rows_keep_the_previous_owners_name(): void
    {
        $sid = (int) DB::table('members as m')->join('member_identifications as i', 'i.member_id', '=', 'm.id')
            ->where('m.member_transfer', '>', 0)->where('m.status', 1)->value('m.society_id');
        $this->assertGreaterThan(0, $sid);

        $expected = DB::select('select i.member_id, i.third_member from member_identifications i where i.id in (
                select max(id) from member_identifications where member_id in (
                    select id from members where member_transfer > 0 and society_id = ? and status = 1) group by member_id)', [$sid]);
        $names = [];
        foreach (OldMembers::forSociety($sid) as $r) {
            $names[(int) $r->member_id] = $r->third_member;
        }
        foreach ($expected as $r) {
            $this->assertSame($r->third_member, $names[(int) $r->member_id] ?? null);
        }
    }

    // ------------------------------------------------------------------------------------------ opening of the new owner

    public function test_the_new_owner_does_not_inherit_the_old_owners_closing_row(): void
    {
        // E WING-304: transferred, first billed (new owner) in FY22 -> the FY21 row is the OLD owner's
        $this->assertTrue(MemberClosing::rowsForOpening(self::U304, 1, self::FY)->isEmpty());
        // E WING-1603: the new owner has billed since FY21 -> the FY21 row is his own, it stays
        $this->assertFalse(MemberClosing::rowsForOpening(self::U1603, 1, self::FY)->isEmpty());
        // a unit that was never transferred reads the previous year's row exactly as before
        $plain = $this->plainMemberId();
        $this->assertFalse(MemberClosing::rowsForOpening($plain, 0, self::FY)->isEmpty());
        $this->assertSame(self::FY, MemberClosing::firstBilledYearOfTransfer(self::U304, 1, 99), 'first year with a bill of the current owner');
    }

    public function test_the_current_members_modal_opens_with_nothing_inherited(): void
    {
        $d = $this->asSociety()->postJson(route('society.getMemberDetails'), ['member_id' => self::U304, 'oldMember' => 0])->assertOk()->json();

        $this->assertEquals(0, $d['Member']['op_principal']);
        $this->assertEquals(0, $d['Member']['op_interest']);
        $this->assertEquals(0, $d['Member']['op_tax']);
    }

    // ------------------------------------------------------------------------------------------ Old Member tab

    public function test_old_member_tab_lists_only_the_selected_year_with_the_balance_carried_into_it(): void
    {
        // FY22: everything before 2026-04-01 is folded into the opening, the rest is listed
        [$opening, $closing, $d] = $this->memberDetails(self::U602, 1);
        $this->assertEqualsWithDelta(-9116, $opening, 0.005);
        $this->assertSame('01/04/2026', $d['openingdate']);
        $this->assertCount(4, $d['memberBillPayment']);
        $this->assertEqualsWithDelta(1884, $closing, 0.005);
        foreach ($d['memberBillPayment'] as $r) {
            $this->assertGreaterThanOrEqual(strtotime(self::FY_START), strtotime($r['date']));
            $this->assertLessThanOrEqual(strtotime(self::FY_END . ' 23:59:59'), strtotime($r['date']));
        }
    }

    public function test_old_member_tab_of_an_earlier_year_opens_with_the_old_owners_first_opening(): void
    {
        $d = $this->actingAs(User::findOrFail(self::CENTURY))
            ->withSession(['fy.year_id' => 21, 'fy.year_start_date' => '2025-04-01', 'fy.year_end_date' => '2026-03-31'])
            ->postJson(route('society.getMemberDetails'), ['member_id' => self::U304, 'oldMember' => 1])->assertOk()->json();

        $this->assertSame('01/04/2025', $d['openingdate']);
        $this->assertEquals(6600, $d['Member']['op_principal'], 'the first bill carried 6,600 in - the old owner opened with it');
        $closing = (float) $d['Member']['op_principal'];
        foreach ($d['memberBillPayment'] as $r) {
            $closing += (float) $r['debit'] - (float) $r['credit'];
        }
        $this->assertEqualsWithDelta(0, $closing, 0.005, 'and ends the year at nil');
    }

    public function test_old_member_bill_rows_carry_the_bill_id_month_and_year(): void
    {
        [, , $d] = $this->memberDetails(self::U602, 1);
        $bills = array_values(array_filter($d['memberBillPayment'], fn ($r) => ($r['flag'] ?? '') === 'bill'));

        $this->assertNotEmpty($bills);
        foreach ($bills as $b) {
            $this->assertNotEmpty($b['bill_id']);
            $this->assertNotEmpty($b['month']);
            $this->assertSame(self::FY, (int) $b['financial_year_id']);
        }
    }

    public function test_the_same_member_page_is_unchanged_for_a_unit_that_was_never_transferred(): void
    {
        $plain = $this->plainMemberId();
        $d = $this->asSociety()->postJson(route('society.getMemberDetails'), ['member_id' => $plain, 'oldMember' => 1])->assertOk()->json();

        // no previous owner -> the old-member view is the current one (nothing is cut or folded)
        $this->assertSame(0, (int) $d['Member']['member_transfer']);
    }

    // ------------------------------------------------------------------------------------------ Debit / Credit Note

    private function validateNote(array $input, int $society = self::CENTURY): array
    {
        session($this->fySession());
        $controller = app(\App\Http\Controllers\Society\DebitCreditNoteController::class);
        $m = new \ReflectionMethod($controller, 'validateNote');
        $m->setAccessible(true);

        return $m->invoke($controller, $input, $society, self::FY);
    }

    private function note(string $type, array $accounts, array $amounts): array
    {
        return [
            'voucher_date' => '2026-10-11', 'note' => '', 'note_type' => $type,
            'type' => ['Credit', 'Debit'], 'account' => $accounts, 'adjust' => ['AUTO', 'AUTO'],
            'credit' => [(string) $amounts[0], '0'], 'debit' => ['0', (string) $amounts[1]],
        ];
    }

    public function test_crediting_the_current_member_beyond_his_balance_tells_you_to_pick_the_old_member(): void
    {
        $r = $this->validateNote($this->note('CN', ['member-' . self::U304, 'member-' . self::U602], [99999, 99999]));

        $this->assertStringContainsString('outstanding balance', $r['error']);
        $this->assertStringContainsString('(Old Member)', $r['error']);
    }

    public function test_crediting_the_old_member_and_debiting_the_current_member_is_accepted(): void
    {
        $r = $this->validateNote($this->note('DN', ['member-' . self::U304 . '-old', 'member-' . self::U304], [2018, 2018]));

        $this->assertArrayNotHasKey('error', $r);
        $this->assertSame(201800, $r['deltas'][self::U304]['PRINCIPAL'], 'the amount lands on the NEW owner\'s principal');
    }

    public function test_an_old_member_that_is_not_in_the_list_is_still_refused(): void
    {
        $r = $this->validateNote($this->note('DN', ['member-1-old', 'member-' . self::U304], [10, 10]));

        $this->assertStringContainsString('not valid', $r['error']);
    }

    public function test_the_page_lists_old_members_with_the_old_member_label_and_a_search_box(): void
    {
        $html = $this->asSociety()->get(route('society.debitCreditNotes'))->assertOk()->getContent();

        $this->assertStringContainsString('(Old Member)', $html);
        $this->assertStringContainsString('dcn-account-search', $html);
        $this->assertStringContainsString('Search member / flat / ledger', $html);
        $this->assertStringContainsString('member-' . self::U304 . '-old', str_replace('&quot;', '"', $html));
    }

    // ------------------------------------------------------------------------------------------ one bill as a PDF

    private function oneBillOf(int $member, int $society, ?int $transfer = null): object
    {
        $q = DB::table('member_bill_summaries')->where('society_id', $society)->where('member_id', $member);
        if ($transfer !== null) {
            $q->where('member_transfer', $transfer);
        }

        return $q->orderByDesc('id')->firstOrFail();
    }

    public function test_one_bill_opens_as_a_pdf_in_the_browser(): void
    {
        $bill = $this->oneBillOf($this->plainMemberId(), self::CENTURY, 0);
        $r = $this->asSociety()->get(route('society.billPdf', ['bill_id' => $bill->id]))->assertOk();

        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline;', $r->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Bill_' . preg_replace('/[^0-9A-Za-z_-]/', '', (string) $bill->bill_no), $r->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $r->getContent());
    }

    public function test_download_pdf_is_an_attachment(): void
    {
        $bill = $this->oneBillOf($this->plainMemberId(), self::CENTURY, 0);
        $r = $this->asSociety()->get(route('society.billPdf', ['bill_id' => $bill->id, 'download' => 1]))->assertOk();

        $this->assertStringStartsWith('attachment;', $r->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $r->getContent());
    }

    public function test_a_previous_owners_bill_of_an_earlier_year_can_be_printed(): void
    {
        $old = DB::table('member_bill_summaries')->where('society_id', self::CENTURY)->where('member_id', self::U304)->where('member_transfer', 0)->orderBy('id')->first();
        $this->asSociety()->get(route('society.billPdf', ['bill_id' => $old->id]))->assertOk();
    }

    public function test_another_societys_bill_and_a_missing_id_are_not_found(): void
    {
        $other = DB::table('member_bill_summaries')->where('society_id', self::JAY_LAXMI)->orderBy('id')->value('id');

        $this->asSociety()->get(route('society.billPdf', ['bill_id' => $other]))->assertNotFound();
        $this->asSociety()->get(route('society.billPdf'))->assertNotFound();
    }

    public function test_the_receipts_of_a_single_bill_default_to_the_year_up_to_the_bill_date(): void
    {
        $bill = DB::table('member_payments as p')
            ->join('member_bill_summaries as b', function ($j) {
                $j->on('b.member_id', '=', 'p.member_id')->on('b.financial_year_id', '=', 'p.financial_year_id')->on('b.member_transfer', '=', 'p.member_transfer');
            })
            ->where('p.society_id', self::CENTURY)->where('p.financial_year_id', self::FY)->where('b.bill_type', 'reg')->where('p.bill_type', 'reg')
            ->whereColumn('b.bill_generated_date', '>=', 'p.payment_date')
            ->orderByDesc('b.id')->select('b.id', 'b.bill_generated_date', 'p.payment_date')->first();
        if (!$bill) {
            $this->markTestSkipped('No bill with an earlier receipt in the scratch copy.');
        }

        $data = (new \App\Services\Reports\MemberBillPrintReport(self::CENTURY, self::FY))->run(['bill_id' => (string) $bill->id]);

        $this->assertCount(1, $data['printBillDetails']);
        $this->assertSame($bill->bill_generated_date, $data['receiptPeriod']['To']);
        $this->assertGreaterThanOrEqual(strtotime($data['receiptPeriod']['From']), strtotime($bill->payment_date));
        $this->assertNotEmpty(reset($data['printBillDetails'])['receipts'], 'the receipt received before the bill is listed');
    }

    // ------------------------------------------------------------------------------------------ Bill Summary Update

    public function test_a_bill_level_principal_adjustment_counts_in_the_closing(): void
    {
        $bill = DB::table('member_bill_summaries as s')
            ->join('member_bill_generates as g', function ($j) {
                $j->on('g.member_id', '=', 's.member_id')->on('g.bill_generated_date', '=', 's.bill_generated_date')->on('g.bill_type', '=', 's.bill_type');
            })
            ->where('s.society_id', self::JAY_LAXMI)->where('s.financial_year_id', self::FY)->where('s.bill_type', 'reg')
            ->whereBetween('s.bill_generated_date', [self::FY_START, self::FY_END])
            ->groupBy('s.id', 's.member_id', 's.monthly_principal_amount')->orderByDesc('s.id')->select('s.id', 's.member_id', 's.monthly_principal_amount')->first();
        if (!$bill) {
            $this->markTestSkipped('No bill with head lines in the scratch copy.');
        }

        $closing = fn () => (new BillSummaryUpdateReport(self::JAY_LAXMI, self::FY, self::FY_START, self::FY_END))->run()['societyMemberDetails'][$bill->member_id]['closing2018'];
        $before = $closing();

        DB::table('member_bill_summaries')->where('id', $bill->id)->update(['monthly_principal_amount' => DB::raw('monthly_principal_amount + 50')]);
        try {
            $after = $closing();
        } finally {
            DB::table('member_bill_summaries')->where('id', $bill->id)->update(['monthly_principal_amount' => $bill->monthly_principal_amount]);
        }

        $this->assertEqualsWithDelta(50, $after - $before, 0.005, 'a +50 principal adjustment on the bill moves the closing by +50');
        $this->assertEqualsWithDelta($before, $closing(), 0.005, 'and the fixture is back as it was');
    }

    public function test_the_bill_summary_update_all_button_waits_for_jquery(): void
    {
        $js = file_get_contents(resource_path('views/society/reports/_bill_summary_update_js.blade.php'));

        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded'", explode('</script>', $js)[0]);
    }

    // ------------------------------------------------------------------------------------------ Admin page

    public function test_the_admin_bill_summary_update_page_replaces_the_dashboard_widget(): void
    {
        $admin = User::where('role', 'Admin')->firstOrFail();

        $page = $this->actingAs($admin)->get(route('admin.reports.billSummaryUpdate'))->assertOk()->getContent();
        $this->assertStringContainsString('id="bm_root"', $page);
        $this->assertStringContainsString('Bill Summary Update</a>', $page);
        $this->assertStringContainsString('Unique total', $page);
        $this->assertStringContainsString('openSociety', $page);

        $dash = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="bm_root"', $dash);
    }

    public function test_the_bill_summary_update_page_is_admin_only(): void
    {
        $this->actingAs(User::findOrFail(self::CENTURY))->get(route('admin.reports.billSummaryUpdate'))->assertForbidden();
    }
}
