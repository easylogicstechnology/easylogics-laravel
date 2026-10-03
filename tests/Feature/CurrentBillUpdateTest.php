<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Current Bill Update" society parameter gate (CakePHP SocietyBillsController::
 * getAllMembersBillSummaryDetails() / updateMemberBillSummaryById() /
 * updateCurrentMemberBillSummaryById()), ported to
 * SocietyModuleController::getAllMembersBillSummaryDetails() / updateMemberBillSummaryById() /
 * updateCurrentMemberBillSummaryById() + BillSettlementService::recalculateCurrentBillOnly().
 *
 * Needs a scratch copy of the CakePHP database with a real bill chain (database name must
 * contain "scratch"); society 104723 / member 27834715 / financial_year_id 21 has a 10-bill
 * chain (regular, member_transfer 0) there. Snapshots and restores society_parameters,
 * member_bill_summaries and member_bill_settlements for this member so the recalculation runs
 * this test performs never leave the scratch DB changed. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/CurrentBillUpdateTest.php
 */
class CurrentBillUpdateTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;

    private ?array $paramSnapshot = null;
    private array $billSnapshot = [];
    private array $settlementSnapshot = [];
    private array $paymentIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $bills = DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderBy('id')->get();
        if ($bills->count() < 2) {
            $this->markTestSkipped('Fixture member does not have the expected multi-bill chain in this database.');
        }

        $this->paramSnapshot = (array) DB::table('society_parameters')->where('society_id', self::SOCIETY)->first();
        $this->billSnapshot = $bills->map(fn ($b) => (array) $b)->all();

        $this->paymentIds = DB::table('member_payments')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->pluck('id')->all();
        $this->settlementSnapshot = empty($this->paymentIds) ? [] :
            DB::table('member_bill_settlements')->whereIn('payment_id', $this->paymentIds)->get()
                ->map(fn ($s) => (array) $s)->all();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch') && $this->paramSnapshot !== null) {
            $this->restore();
        }

        parent::tearDown();
    }

    private function restore(): void
    {
        DB::table('society_parameters')->where('society_id', self::SOCIETY)->update([
            'current_bill_update_enabled' => $this->paramSnapshot['current_bill_update_enabled'],
        ]);

        foreach ($this->billSnapshot as $row) {
            $id = $row['id'];
            unset($row['id']);
            DB::table('member_bill_summaries')->where('id', $id)->update($row);
        }

        if (!empty($this->paymentIds)) {
            DB::table('member_bill_settlements')->whereIn('payment_id', $this->paymentIds)->delete();
            foreach ($this->settlementSnapshot as $row) {
                unset($row['id']);
                DB::table('member_bill_settlements')->insert($row);
            }
        }
    }

    private function setParam($value): void
    {
        DB::table('society_parameters')->where('society_id', self::SOCIETY)
            ->update(['current_bill_update_enabled' => $value]);
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    private function latestBillId(): int
    {
        return (int) DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderByDesc('bill_generated_date')->orderByDesc('id')
            ->value('id');
    }

    private function earlierBillId(): int
    {
        return (int) DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderBy('bill_generated_date')->orderBy('id')
            ->value('id');
    }

    // Test 3 (spec): Parameter = NOT SET -> old behaviour: old Update allowed on any bill,
    // Current Bill Update not available.
    public function test_param_not_set_old_update_allowed_and_current_bill_update_blocked(): void
    {
        $this->setParam(null);
        $billId = $this->earlierBillId();

        $gate = $this->asSociety()->postJson(route('society.getAllMembersBillSummaryDetails'), ['id' => $billId]);
        $gate->assertOk();
        $gate->assertJsonPath('oldUpdateAvailable', 1);
        $gate->assertJsonPath('currentBillUpdateEnabled', 0);

        $old = $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), ['id' => $billId]);
        $old->assertJsonPath('error', 0);

        $current = $this->asSociety()->postJson(route('society.updateCurrentMemberBillSummaryById'), ['id' => $billId]);
        $current->assertJsonPath('error', 1);
        $this->assertStringContainsString('not enabled', $current->json('error_message'));
    }

    // Test 1 + 2 (spec): Parameter = YES -> Current Bill Update allowed on the latest bill,
    // and every other (previous) bill's row is left byte-for-byte unchanged.
    public function test_param_yes_current_bill_update_works_and_never_touches_previous_bills(): void
    {
        $this->setParam(1);
        $billId = $this->latestBillId();

        $before = DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderBy('id')->get()->keyBy('id');

        $gate = $this->asSociety()->postJson(route('society.getAllMembersBillSummaryDetails'), ['id' => $billId]);
        $gate->assertJsonPath('currentBillUpdateEnabled', 1);
        $gate->assertJsonPath('isCurrentBill', 1);

        $resp = $this->asSociety()->postJson(route('society.updateCurrentMemberBillSummaryById'), ['id' => $billId]);
        $resp->assertOk();
        $resp->assertJsonPath('error', 0);

        $after = DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderBy('id')->get()->keyBy('id');

        foreach ($before as $id => $row) {
            if ($id === $billId) {
                continue;
            }
            $this->assertEquals((array) $row, (array) $after[$id], "bill {$id} (not the current bill) must be byte-for-byte unchanged");
        }
    }

    // Test 2 (spec): Parameter = YES -> All Bill Update is blocked, and Current Bill Update
    // itself refuses to run against a bill that isn't the member's latest.
    public function test_param_yes_blocks_all_bill_update_and_blocks_non_latest_bill(): void
    {
        $this->setParam(1);
        $latest = $this->latestBillId();
        $earlier = $this->earlierBillId();
        $this->assertNotSame($latest, $earlier);

        $old = $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), ['id' => $latest]);
        $old->assertJsonPath('error', 1);
        $this->assertStringContainsString('disabled', $old->json('error_message'));

        $gate = $this->asSociety()->postJson(route('society.getAllMembersBillSummaryDetails'), ['id' => $earlier]);
        $gate->assertJsonPath('currentBillUpdateEnabled', 1);
        $gate->assertJsonPath('isCurrentBill', 0);

        $current = $this->asSociety()->postJson(route('society.updateCurrentMemberBillSummaryById'), ['id' => $earlier]);
        $current->assertJsonPath('error', 1);
        $this->assertStringContainsString('not the current/latest bill', $current->json('error_message'));
    }

    // Parameter = NO -> matches Cake's documented "No" behaviour: no manual update path at
    // all, old Update AND Current Bill Update both blocked.
    public function test_param_no_blocks_both_old_update_and_current_bill_update(): void
    {
        $this->setParam(0);
        $billId = $this->latestBillId();

        $gate = $this->asSociety()->postJson(route('society.getAllMembersBillSummaryDetails'), ['id' => $billId]);
        $gate->assertJsonPath('oldUpdateAvailable', 0);
        $gate->assertJsonPath('currentBillUpdateEnabled', 0);

        $old = $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), ['id' => $billId]);
        $old->assertJsonPath('error', 1);
        $this->assertStringContainsString('disabled', $old->json('error_message'));

        $current = $this->asSociety()->postJson(route('society.updateCurrentMemberBillSummaryById'), ['id' => $billId]);
        $current->assertJsonPath('error', 1);
        $this->assertStringContainsString('not enabled', $current->json('error_message'));
    }
}
