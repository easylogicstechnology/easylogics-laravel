<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BillSettlementService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Old member" flow after a flat/shop transfer (CakePHP's add_member_identifications():
 * a transfer does NOT create a new Member row - it renames the SAME row to the new owner
 * and bumps members.member_transfer, while a new member_identifications snapshot keeps the
 * outgoing owner's name in third_member. Every bill/payment from before the transfer stays
 * tagged with the OLD member_transfer number; anything from after uses the new one).
 *
 * This is a read-mostly audit + regression test of the fix made alongside it: Cake's
 * getLatestTranferredId() reads members.member_transfer directly. BillSettlementService::
 * getLatestTransferNo() instead computed MAX(member_bill_summaries.member_transfer) for that
 * member - which silently returns the OLD generation (or 0) for any member who has been
 * transferred but has no bill yet under the new generation. That's not a rare edge case -
 * checked against dnc_scratch and found common. This is fixed now; the tests below both prove
 * the fix and guard against it regressing.
 *
 * Needs a scratch copy of the CakePHP database (database name must contain "scratch");
 * member 10122 (society 234) is transferred (member_transfer=1) with zero bills - the
 * exact divergence case. Member 49617 (society 5314) has real bills in both generations
 * (8 bills at member_transfer=0, 10 at member_transfer=1) - used to prove the old
 * generation's bills are never touched by an operation scoped to the current one. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/MemberTransferOldMemberFlowTest.php
 */
class MemberTransferOldMemberFlowTest extends TestCase
{
    private const NO_BILLS_MEMBER = 10122;
    private const NO_BILLS_SOCIETY = 234;

    private const CHAIN_MEMBER = 49617;
    private const CHAIN_SOCIETY = 5314;

    private array $chainSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch') && !empty($this->chainSnapshot)) {
            foreach ($this->chainSnapshot as $row) {
                $id = $row['id'];
                unset($row['id']);
                DB::table('member_bill_summaries')->where('id', $id)->update($row);
            }
        }

        parent::tearDown();
    }

    private function asSociety(int $societyId)
    {
        return $this->actingAs(User::findOrFail($societyId));
    }

    // The core bug: a member transferred (members.member_transfer=1) but with zero bills
    // yet under the new generation. getLatestTransferNo() must still return 1.
    public function test_latest_transfer_no_reads_the_member_row_even_with_no_bills_yet(): void
    {
        $memberRow = DB::table('members')->where('id', self::NO_BILLS_MEMBER)->first();
        $this->assertSame(1, (int) $memberRow->member_transfer, 'fixture expected to be a transferred member');
        $this->assertSame(0, DB::table('member_bill_summaries')->where('member_id', self::NO_BILLS_MEMBER)->count(), 'fixture expected to have no bills yet');

        $latest = app(BillSettlementService::class)->getLatestTransferNo(self::NO_BILLS_MEMBER, self::NO_BILLS_SOCIETY);

        $this->assertSame(1, (int) $latest, 'must match members.member_transfer, not MAX(bills.member_transfer) which would wrongly be 0/NULL here');
    }

    // Sanity check: for a member who DOES have bills in both generations, the two approaches
    // happen to agree - confirms the fix does not regress the common case.
    public function test_latest_transfer_no_matches_member_row_when_bills_exist_too(): void
    {
        $memberRow = DB::table('members')->where('id', self::CHAIN_MEMBER)->first();
        $latest = app(BillSettlementService::class)->getLatestTransferNo(self::CHAIN_MEMBER, self::CHAIN_SOCIETY);

        $this->assertSame((int) $memberRow->member_transfer, (int) $latest);
    }

    // The bill-edit modal's "old member" tab (getMemberDetails with oldMember=1) must show
    // the OUTGOING owner's name, pulled from member_identifications.third_member - the
    // "Flat Transfer From" field filled in when the transfer was recorded.
    public function test_old_member_tab_shows_the_outgoing_owners_name(): void
    {
        $identification = DB::table('member_identifications')->where('member_id', self::CHAIN_MEMBER)->first();
        if (!$identification || empty($identification->third_member)) {
            $this->markTestSkipped('Fixture member has no member_identifications.third_member to check against.');
        }

        $response = $this->asSociety(self::CHAIN_SOCIETY)->postJson(route('society.getMemberDetails'), [
            'member_id' => self::CHAIN_MEMBER,
            'oldMember' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('Member.member_name', $identification->third_member);
    }

    // The actual "old member flow" guarantee: recalculating the CURRENT generation
    // (member_transfer = latest) must never write to any bill tagged with an OLDER
    // member_transfer number - every one of those rows must come back byte-for-byte
    // identical.
    public function test_recalculating_the_current_generation_never_touches_the_old_generations_bills(): void
    {
        $allBills = DB::table('member_bill_summaries')->where('member_id', self::CHAIN_MEMBER)->get();
        $this->chainSnapshot = $allBills->map(fn ($b) => (array) $b)->all();

        $latestTransfer = (int) DB::table('members')->where('id', self::CHAIN_MEMBER)->value('member_transfer');
        $oldBillsBefore = $allBills->filter(fn ($b) => (int) $b->member_transfer < $latestTransfer)->keyBy('id');
        $this->assertGreaterThan(0, $oldBillsBefore->count(), 'fixture expected to have at least one bill from an older generation');

        // Recalculate every (billType, financial_year_id) combo that exists under the
        // CURRENT generation only - exactly what "Update" / "Current Bill Update" scope to.
        $currentBills = $allBills->filter(fn ($b) => (int) $b->member_transfer === $latestTransfer);
        $combos = $currentBills->map(fn ($b) => $b->bill_type . '|' . $b->financial_year_id)->unique();

        $service = app(BillSettlementService::class);
        foreach ($combos as $combo) {
            [$billType, $fyId] = explode('|', $combo);
            $service->recalculateMemberBills(self::CHAIN_MEMBER, self::CHAIN_SOCIETY, $billType, (int) $fyId, $latestTransfer);
        }

        $afterBills = DB::table('member_bill_summaries')->where('member_id', self::CHAIN_MEMBER)->get()->keyBy('id');
        foreach ($oldBillsBefore as $id => $beforeRow) {
            $this->assertEquals((array) $beforeRow, (array) $afterBills[$id], "old-generation bill {$id} must be byte-for-byte unchanged");
        }
    }
}
