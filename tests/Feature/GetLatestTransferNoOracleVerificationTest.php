<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BillSettlementService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mandatory verification suite for the BillSettlementService::getLatestTransferNo() fix,
 * treating CakePHP's getLatestTranferredId() as the oracle. That Cake function is exactly
 * "read members.member_transfer for this member" - nothing more - so every "Cake reference
 * value" in the assertions below is a direct read of that same column, which IS Cake's
 * algorithm, not a simulation of it.
 *
 * Read-only where possible; every mutating test snapshots and restores its own rows.
 * Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch"). Fixtures
 * used (all real data already in dnc_scratch, not created by this suite):
 *   - Member 27834715 / society 104723: normal member, never transferred (member_transfer=0).
 *   - Member 10122 / society 234: transferred (member_transfer=1) with ZERO bills yet under
 *     the new generation - the exact case the bug affected.
 *   - Member 49617 / society 5314: transferred (member_transfer=1) WITH bills under both
 *     generations (8 at transfer=0, 10 at transfer=1).
 *   - Member 90899 / society 10122: transferred twice (member_transfer=2); has bills at
 *     transfer=0 and transfer=2, none at the intermediate transfer=1.
 *
 * Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/GetLatestTransferNoOracleVerificationTest.php
 */
class GetLatestTransferNoOracleVerificationTest extends TestCase
{
    private const NORMAL_MEMBER = 27834715;
    private const NORMAL_SOCIETY = 104723;

    private const NO_BILLS_MEMBER = 10122;
    private const NO_BILLS_SOCIETY = 234;

    private const CHAIN_MEMBER = 49617;
    private const CHAIN_SOCIETY = 5314;
    private const CHAIN_FY = 21;
    private const CHAIN_LATEST_REG_BILL = 236306; // bill_no 68, member_transfer 1, reg

    private const MULTI_MEMBER = 90899;
    private const MULTI_SOCIETY = 10122;

    private array $chainBillSnapshot = [];
    private array $chainPaymentIdsCreated = [];
    private ?int $chainParamSnapshot = null;
    private bool $chainParamTouched = false;
    private array $chainGenerateIdsBefore = [];
    private bool $chainSnapshotTaken = false;

    private array $noBillsPaymentIdsCreated = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            if (!empty($this->chainBillSnapshot)) {
                foreach ($this->chainBillSnapshot as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::table('member_bill_summaries')->where('id', $id)->update($row);
                }
            }
            if (!empty($this->chainPaymentIdsCreated)) {
                DB::table('member_bill_settlements')->whereIn('payment_id', $this->chainPaymentIdsCreated)->delete();
                DB::table('cheque_return_details')->whereIn('payment_id', $this->chainPaymentIdsCreated)->delete();
                DB::table('member_payments')->whereIn('id', $this->chainPaymentIdsCreated)->delete();
            }
            if ($this->chainParamTouched) {
                DB::table('society_parameters')->where('society_id', self::CHAIN_SOCIETY)
                    ->update(['current_bill_update_enabled' => $this->chainParamSnapshot]);
            }
            if ($this->chainSnapshotTaken) {
                // whereNotIn against an empty array (no generate rows existed before this
                // test) correctly deletes everything the test created - unlike the earlier
                // `!empty($this->chainGenerateIdsBefore)` guard, which skipped cleanup
                // entirely whenever the "before" state legitimately had zero rows.
                DB::table('member_bill_generates')->where('member_id', self::CHAIN_MEMBER)
                    ->whereNotIn('id', $this->chainGenerateIdsBefore)->delete();
            }
            if (!empty($this->noBillsPaymentIdsCreated)) {
                DB::table('member_bill_settlements')->whereIn('payment_id', $this->noBillsPaymentIdsCreated)->delete();
                DB::table('cheque_return_details')->whereIn('payment_id', $this->noBillsPaymentIdsCreated)->delete();
                DB::table('member_payments')->whereIn('id', $this->noBillsPaymentIdsCreated)->delete();
            }
        }
        parent::tearDown();
    }

    // Every fixture used here (society 104723/234/5314/10122) already shares FY 21 in this
    // scratch database (confirmed for each before writing these tests); without this, e.g.
    // scenario 7's cheque-return insert hits cheque_return_details.financial_year_id NOT NULL.
    private function asSociety(int $societyId, int $fyId = self::CHAIN_FY)
    {
        return $this->actingAs(User::findOrFail($societyId))->withSession(['fy.year_id' => $fyId]);
    }

    private function snapshotChainBills(): void
    {
        $this->chainSnapshotTaken = true;
        $this->chainBillSnapshot = DB::table('member_bill_summaries')->where('member_id', self::CHAIN_MEMBER)
            ->get()->map(fn ($b) => (array) $b)->all();
        $this->chainGenerateIdsBefore = DB::table('member_bill_generates')->where('member_id', self::CHAIN_MEMBER)->pluck('id')->all();
    }

    private function oldGenBillsBefore(): \Illuminate\Support\Collection
    {
        return collect($this->chainBillSnapshot)->filter(fn ($b) => (int) $b['member_transfer'] === 0)->keyBy('id');
    }

    private function assertOldGenUnchanged(string $label): void
    {
        $after = DB::table('member_bill_summaries')->where('member_id', self::CHAIN_MEMBER)->get()->keyBy('id');
        foreach ($this->oldGenBillsBefore() as $id => $beforeRow) {
            $this->assertEquals((array) $beforeRow, (array) $after[$id], "[$label] old-generation bill $id must stay byte-for-byte unchanged");
        }
    }

    // ===================================================================
    // 1. Normal member - no transfer
    // ===================================================================
    public function test_scenario_1_normal_member_no_transfer(): void
    {
        $cakeOracle = (int) DB::table('members')->where('id', self::NORMAL_MEMBER)->value('member_transfer');
        $this->assertSame(0, $cakeOracle, 'fixture expected to be a never-transferred member');

        $laravel = app(BillSettlementService::class)->getLatestTransferNo(self::NORMAL_MEMBER, self::NORMAL_SOCIETY);

        $this->assertSame($cakeOracle, (int) $laravel);
    }

    // ===================================================================
    // 2. Member transfer with a new bill already existing under the new generation
    // ===================================================================
    public function test_scenario_2_transferred_member_with_new_bill_already_existing(): void
    {
        $cakeOracle = (int) DB::table('members')->where('id', self::CHAIN_MEMBER)->value('member_transfer');
        $this->assertSame(1, $cakeOracle);
        $this->assertGreaterThan(0, DB::table('member_bill_summaries')->where('member_id', self::CHAIN_MEMBER)->where('member_transfer', 1)->count());

        $laravel = app(BillSettlementService::class)->getLatestTransferNo(self::CHAIN_MEMBER, self::CHAIN_SOCIETY);

        $this->assertSame($cakeOracle, (int) $laravel);
    }

    // ===================================================================
    // 3. Member transfer with NO bill yet under the new generation - THE critical case
    // ===================================================================
    public function test_scenario_3_transferred_member_with_no_bill_yet_the_critical_case(): void
    {
        $cakeOracle = (int) DB::table('members')->where('id', self::NO_BILLS_MEMBER)->value('member_transfer');
        $this->assertSame(1, $cakeOracle, 'fixture expected to be transferred (member_transfer=1)');
        $this->assertSame(0, DB::table('member_bill_summaries')->where('member_id', self::NO_BILLS_MEMBER)->count(), 'fixture expected zero bills under any generation');

        $legacyWrongValue = DB::table('member_bill_summaries')->where('member_id', self::NO_BILLS_MEMBER)
            ->where('society_id', self::NO_BILLS_SOCIETY)->max('member_transfer'); // null -> the pre-fix behaviour

        $laravel = app(BillSettlementService::class)->getLatestTransferNo(self::NO_BILLS_MEMBER, self::NO_BILLS_SOCIETY);

        $this->assertSame($cakeOracle, (int) $laravel, 'must match members.member_transfer (Cake oracle)');
        $this->assertNull($legacyWrongValue, 'documents that the pre-fix MAX(bills) approach would have returned NULL/0 here, diverging from Cake');
        $this->assertNotEquals((int) ($legacyWrongValue ?? 0), $cakeOracle, 'confirms this fixture genuinely exercises the divergence');
    }

    // ===================================================================
    // 4. Settlement of old-generation bills - must never be touched by an operation
    //    scoped to the current generation
    // ===================================================================
    public function test_scenario_4_old_generation_bills_settlement_is_isolated(): void
    {
        $this->snapshotChainBills();

        app(BillSettlementService::class)->recalculateMemberBills(self::CHAIN_MEMBER, self::CHAIN_SOCIETY, 'reg', self::CHAIN_FY, 1);

        $this->assertOldGenUnchanged('scenario 4');
    }

    // ===================================================================
    // 5. Payment save - the payment must be stamped with the CURRENT generation number,
    //    even though the member has zero bills (proves the real-world impact of the fix)
    // ===================================================================
    public function test_scenario_5_payment_save_stamps_the_correct_current_generation(): void
    {
        $cakeOracle = (int) DB::table('members')->where('id', self::NO_BILLS_MEMBER)->value('member_transfer');

        $response = $this->asSociety(self::NO_BILLS_SOCIETY)->post(route('society.addMemberPayment'), [
            'member_id' => self::NO_BILLS_MEMBER, 'payment_date' => '2026-04-05', 'amount_paid' => 250,
            'payment_mode' => 1, 'bill_type' => 'reg',
        ]);
        $response->assertRedirect(route('society.memberPayments'));

        $saved = DB::table('member_payments')->where('member_id', self::NO_BILLS_MEMBER)->orderByDesc('id')->first();
        $this->assertNotNull($saved);
        $this->noBillsPaymentIdsCreated[] = $saved->id;

        $this->assertSame($cakeOracle, (int) $saved->member_transfer, 'payment must be stamped with the CURRENT generation (1), not 0');
    }

    // ===================================================================
    // 6. Payment delete - deleting a payment on the old-bills-having member must not
    //    touch the old generation's bills
    // ===================================================================
    public function test_scenario_6_payment_delete_does_not_touch_old_generation(): void
    {
        $this->snapshotChainBills();

        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::CHAIN_SOCIETY, 'receipt_id' => 999999101, 'member_id' => self::CHAIN_MEMBER,
            'member_transfer' => 1, 'bill_type' => 'reg', 'payment_date' => '2025-08-05',
            'financial_year_id' => self::CHAIN_FY, 'amount_paid' => 100, 'payment_mode' => 1,
        ]);

        $response = $this->asSociety(self::CHAIN_SOCIETY)->delete(route('society.deleteMemberPayment', $paymentId));
        $response->assertRedirect(route('society.memberPayments'));

        $this->assertSame(0, DB::table('member_payments')->where('id', $paymentId)->count());
        $this->assertOldGenUnchanged('scenario 6');
    }

    // ===================================================================
    // 7. Cheque return - upsert + revert must operate on the current generation only
    // ===================================================================
    public function test_scenario_7_cheque_return_does_not_touch_old_generation(): void
    {
        $this->snapshotChainBills();

        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::CHAIN_SOCIETY, 'receipt_id' => 999999102, 'member_id' => self::CHAIN_MEMBER,
            'member_transfer' => 1, 'bill_type' => 'reg', 'payment_date' => '2025-08-05',
            'financial_year_id' => self::CHAIN_FY, 'amount_paid' => 100, 'payment_mode' => 3,
            'cheque_reference_number' => 'CHQ-XFER-1',
        ]);
        $this->chainPaymentIdsCreated[] = $paymentId;

        $response = $this->asSociety(self::CHAIN_SOCIETY)->post(route('society.addMemberPayment', $paymentId), [
            'cheque_return_date' => '2025-08-10',
            'cheque_return_reason' => 'Test bounce',
        ]);
        $response->assertRedirect(route('society.memberPayments'));

        $returnRow = DB::table('cheque_return_details')->where('payment_id', $paymentId)->first();
        $this->assertNotNull($returnRow);
        $this->assertSame((string) self::CHAIN_MEMBER, (string) $returnRow->member_id);

        $this->assertOldGenUnchanged('scenario 7');
    }

    // ===================================================================
    // 8. Old Update (updateMemberBillSummaryById) - scoped strictly to the current
    //    generation
    // ===================================================================
    public function test_scenario_8_old_update_scoped_to_current_generation_only(): void
    {
        $this->snapshotChainBills();

        $response = $this->asSociety(self::CHAIN_SOCIETY)->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::CHAIN_LATEST_REG_BILL,
        ]);

        $response->assertOk();
        $response->assertJsonPath('error', 0);
        $this->assertOldGenUnchanged('scenario 8');
    }

    // ===================================================================
    // 9. Current Bill Update - isCurrentBill detection and recalculation scoped to the
    //    current generation
    // ===================================================================
    public function test_scenario_9_current_bill_update_scoped_to_current_generation_only(): void
    {
        $this->snapshotChainBills();
        $this->chainParamTouched = true;
        $this->chainParamSnapshot = DB::table('society_parameters')->where('society_id', self::CHAIN_SOCIETY)->value('current_bill_update_enabled');
        DB::table('society_parameters')->where('society_id', self::CHAIN_SOCIETY)->update(['current_bill_update_enabled' => 1]);

        $gate = $this->asSociety(self::CHAIN_SOCIETY)->postJson(route('society.getAllMembersBillSummaryDetails'), [
            'id' => self::CHAIN_LATEST_REG_BILL,
        ]);
        $gate->assertJsonPath('currentBillUpdateEnabled', 1);
        $gate->assertJsonPath('isCurrentBill', 1);

        $response = $this->asSociety(self::CHAIN_SOCIETY)->postJson(route('society.updateCurrentMemberBillSummaryById'), [
            'id' => self::CHAIN_LATEST_REG_BILL,
        ]);
        $response->assertOk();
        $response->assertJsonPath('error', 0);

        $this->assertOldGenUnchanged('scenario 9');
    }

    // ===================================================================
    // 10. Tariff update - editing a Particulars/Amount line is scoped to the current
    //     generation's bill only
    // ===================================================================
    public function test_scenario_10_tariff_update_scoped_to_current_generation_only(): void
    {
        $this->snapshotChainBills();
        $ledgerHeadId = DB::table('society_ledger_heads')->where('society_id', self::CHAIN_SOCIETY)
            ->where('is_in_bill_charges', 1)->value('id');
        $this->assertNotNull($ledgerHeadId, 'fixture expected at least one bill-charge ledger head');
        $bill = DB::table('member_bill_summaries')->where('id', self::CHAIN_LATEST_REG_BILL)->first();

        $response = $this->asSociety(self::CHAIN_SOCIETY)->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::CHAIN_LATEST_REG_BILL,
            'member_id' => self::CHAIN_MEMBER,
            'bill_no' => $bill->bill_no,
            'month' => $bill->month,
            'bill_generated_date' => $bill->bill_generated_date,
            'tariff' => [$ledgerHeadId => 321],
        ]);
        $response->assertOk();
        $response->assertJsonPath('error', 0);

        $generated = DB::table('member_bill_generates')->where('member_id', self::CHAIN_MEMBER)
            ->where('ledger_head_id', $ledgerHeadId)->where('bill_number', $bill->bill_no)->first();
        $this->assertNotNull($generated);
        $this->assertEqualsWithDelta(321.0, (float) $generated->amount, 0.01);

        $this->assertOldGenUnchanged('scenario 10');
    }

    // ===================================================================
    // 11. Multiple member transfers / multiple member_transfer generations
    // ===================================================================
    public function test_scenario_11_multiple_transfer_generations(): void
    {
        $cakeOracle = (int) DB::table('members')->where('id', self::MULTI_MEMBER)->value('member_transfer');
        $this->assertSame(2, $cakeOracle, 'fixture expected a member transferred twice');

        $gen0Count = DB::table('member_bill_summaries')->where('member_id', self::MULTI_MEMBER)->where('member_transfer', 0)->count();
        $gen1Count = DB::table('member_bill_summaries')->where('member_id', self::MULTI_MEMBER)->where('member_transfer', 1)->count();
        $gen2Count = DB::table('member_bill_summaries')->where('member_id', self::MULTI_MEMBER)->where('member_transfer', 2)->count();
        $this->assertGreaterThan(0, $gen0Count);
        $this->assertSame(0, $gen1Count, 'fixture expected the intermediate generation to have no bills - its own divergence case');
        $this->assertGreaterThan(0, $gen2Count);

        $laravel = app(BillSettlementService::class)->getLatestTransferNo(self::MULTI_MEMBER, self::MULTI_SOCIETY);
        $this->assertSame($cakeOracle, (int) $laravel, 'must return 2 (the true latest), not stop at the gap generation 1 or fall back to gen 0');
    }

    // ===================================================================
    // 12. Latest members.member_transfer correctly identified across every fixture
    // ===================================================================
    public function test_scenario_12_latest_transfer_number_matches_cake_oracle_for_every_fixture(): void
    {
        $service = app(BillSettlementService::class);
        $cases = [
            [self::NORMAL_MEMBER, self::NORMAL_SOCIETY],
            [self::NO_BILLS_MEMBER, self::NO_BILLS_SOCIETY],
            [self::CHAIN_MEMBER, self::CHAIN_SOCIETY],
            [self::MULTI_MEMBER, self::MULTI_SOCIETY],
        ];

        foreach ($cases as [$memberId, $societyId]) {
            $cakeOracle = (int) DB::table('members')->where('id', $memberId)->value('member_transfer');
            $laravel = (int) $service->getLatestTransferNo($memberId, $societyId);
            $this->assertSame($cakeOracle, $laravel, "member $memberId: Laravel ($laravel) must match Cake oracle ($cakeOracle)");
        }
    }
}
