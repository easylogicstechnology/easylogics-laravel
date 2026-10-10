<?php

namespace Tests\Feature;

use App\Http\Controllers\Society\SocietyModuleController;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Business-rule test for the Delay Days (1) and Complete Cycle Days (3) interest methods of
 * SocietyModuleController::getInterestOnDueAmount() - the function bill generation, the Update buttons and the
 * recalculation after a payment all go through. Mirrors the CakePHP test app/Test/interest/test_interest_rules.php.
 *
 * Every expected value comes from an INDEPENDENT oracle (below) written from the business rules:
 *   Delay Days  : interest only for the actual days between the due date and the payment date
 *                 (due 15-Jul: paid 14/15-Jul = 0 days, 16-Jul = 1, 20-Jul = 5, 25-Jul = 10); money still unpaid when the
 *                 next bill is generated is late from the due date up to the new bill's date.
 *   Complete Cycle Days : payment on/before the due date = 0; principal still outstanding after the due date is charged
 *                 for the COMPLETE cycle = the real calendar days between the two bill dates (90 / 91 / 92 ...).
 *   Due date protection : a due date on or after the new bill's date has not been crossed = 0.
 *   Credit Notes inside the window settle principal like a payment; bounced cheques and another owner's payments do not.
 * The oracle never calls application code.
 *
 * Needs a scratch copy of the CakePHP database (the name must contain "scratch"). It only inserts rows for a synthetic
 * society / member (ids 99999001 / 99990001) and deletes them again. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch php artisan test --filter=InterestMethodsRulesTest
 */
class InterestMethodsRulesTest extends TestCase
{
    private const SOCIETY = 99999001;
    private const MEMBER = 99990001;
    private const FY = 22;
    private const TRANSFER = 1;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')
            || config('database.default') !== 'mysql') {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_CONNECTION=mysql, DB_DATABASE must contain "scratch").');
        }

        $this->cleanFixture();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->cleanFixture();
        }

        parent::tearDown();
    }

    private function cleanFixture(): void
    {
        DB::table('member_payments')->where('society_id', self::SOCIETY)->where('member_id', self::MEMBER)->delete();
        DB::table('cheque_return_details')->where('society_id', self::SOCIETY)->where('member_id', self::MEMBER)->delete();
        DB::table('journal_vouchers')->where('society_id', self::SOCIETY)->where('jv_credit_member_head_id', self::MEMBER)->delete();
    }

    // ============================================================================================ the oracle
    /** @return array{0: float, 1: int} exact interest and the number of rounding segments */
    private static function oracle(array $s): array
    {
        $last = end($s['bills']);
        [$g0, $due] = [$last[0], $last[1]];
        $g1 = $s['gen'];
        $rate = $s['rate'];
        $compound = $s['type'] == 3;
        $days = fn ($a, $b) => (int) round((strtotime($b) - strtotime($a)) / 86400);

        $principal = $interest = 0;
        foreach ($s['bills'] as $b) { $principal += $b[2]; $interest += $b[3]; }

        // a returned (bounced) cheque is not a payment; a credit note is
        $all = [];
        foreach ($s['pays'] as $i => $p) {
            if (empty($p[2]) && !in_array($i, $s['returned'])) { $all[] = $p; }   // p[2] = 'other owner'
        }
        foreach ($s['credits'] as $c) { $all[] = $c; }
        usort($all, fn ($a, $b) => strcmp($a[0], $b[0]));

        $apply = function ($amount) use (&$principal, &$interest) {
            $toInterest = min($amount, $interest);       // interest first, then principal; any excess is an advance
            $interest -= $toInterest;
            $principal -= min($amount - $toInterest, $principal);
        };
        foreach ($all as $p) { if ($p[0] < $g0) { $apply($p[1]); } }
        $window = array_values(array_filter($all, fn ($p) => $p[0] >= $g0 && $p[0] < $g1));
        foreach ($window as $p) { if ($p[0] <= $due) { $apply($p[1]); } }
        $late = array_values(array_filter($window, fn ($p) => $p[0] > $due));
        // by reference on purpose: the base must follow the payments applied below (an arrow function would freeze it)
        $base = function () use (&$principal, &$interest, $compound) {
            return $compound ? $principal + $interest : $principal;
        };

        if ($g1 <= $due) { return [0.0, 1]; }          // due date not crossed

        if ($s['method'] == 3) {
            $b = $base();
            return $b <= 0 ? [0.0, 1] : [$b * $rate / 100 / 365 * $days($g0, $g1), 1];
        }

        $total = 0.0; $segments = 0; $cursor = $due;
        foreach ($late as $p) {
            $b = $base();
            if ($b > 0) { $total += $b * $rate / 100 / 365 * $days($cursor, $p[0]); $segments++; }
            $apply($p[1]);
            $cursor = $p[0];
        }
        $b = $base();
        if ($b > 0) { $total += $b * $rate / 100 / 365 * $days($cursor, $g1); $segments++; }
        return [$total, max(1, $segments)];
    }

    private static function base(array $over = []): array
    {
        return array_merge([
            'bills' => [['2026-07-01', '2026-07-15', 10000, 0]],      // quarterly bill Rs.10,000, due 15 Jul
            'pays' => [], 'credits' => [], 'returned' => [],
            'gen' => '2026-10-01', 'rate' => 21, 'type' => 2,
        ], $over);
    }

    /** @return array<string, array{0: array}> */
    public static function scenarios(): array
    {
        $b = fn (array $o = []) => self::base($o);
        $two = [['2026-04-01', '2026-04-15', 10000, 0], ['2026-07-01', '2026-07-15', 10000, 0]];
        $list = [
            '01 payment before due date (full 10 Jul)' => $b(['pays' => [['2026-07-10', 10000]]]),
            '01b partial before due date (4,000 on 10 Jul)' => $b(['pays' => [['2026-07-10', 4000]]]),
            '02 payment exactly on due date' => $b(['pays' => [['2026-07-15', 10000]]]),
            '03 payment 1 day after due date' => $b(['pays' => [['2026-07-16', 10000]]]),
            '04 payment 5 days after due date (brief example)' => $b(['pays' => [['2026-07-20', 10000]]]),
            '05 payment 10 days after due date' => $b(['pays' => [['2026-07-25', 10000]]]),
            '06 payment 30 days after due date' => $b(['pays' => [['2026-08-14', 10000]]]),
            '06b month boundary: due 30 Apr, paid 1 May' => $b(['bills' => [['2027-04-01', '2027-04-30', 10000, 0]], 'gen' => '2027-07-01', 'pays' => [['2027-05-01', 10000]]]),
            '07 no payment after due date' => $b(),
            '08 partial payment after due date' => $b(['pays' => [['2026-07-20', 4000]]]),
            '09 multiple partial payments' => $b(['pays' => [['2026-07-10', 3000], ['2026-07-20', 3000], ['2026-08-10', 2000]]]),
            '10 previous outstanding principal' => $b(['bills' => $two]),
            '11 current + previous, 4,000 of old bill paid' => $b(['bills' => $two, 'pays' => [['2026-05-10', 4000]]]),
            '11b previous bill fully settled' => $b(['bills' => $two, 'pays' => [['2026-05-10', 10000]]]),
            '11c carried interest cleared first by an on-time payment' => $b(['bills' => [['2026-07-01', '2026-07-15', 10000, 500]], 'pays' => [['2026-07-10', 600]]]),
            '11d one on-time payment covers two unpaid bills' => $b(['bills' => $two, 'pays' => [['2026-07-10', 20000]]]),
            '11e on-time payment covers one and a half bills' => $b(['bills' => $two, 'pays' => [['2026-07-10', 15000]]]),
            '12 quarterly 90-day cycle' => $b(['bills' => [['2027-01-01', '2027-01-15', 10000, 0]], 'gen' => '2027-04-01']),
            '12b 90-day cycle paid 5 days late' => $b(['bills' => [['2027-01-01', '2027-01-15', 10000, 0]], 'gen' => '2027-04-01', 'pays' => [['2027-01-20', 10000]]]),
            '13 quarterly 91-day cycle (Apr-Jul)' => $b(['bills' => [['2026-04-01', '2026-04-15', 10000, 0]], 'gen' => '2026-07-01']),
            '13b 91-day cycle paid 5 days late' => $b(['bills' => [['2026-04-01', '2026-04-15', 10000, 0]], 'gen' => '2026-07-01', 'pays' => [['2026-04-20', 10000]]]),
            '14 quarterly 92-day cycle paid 5 days late' => $b(['pays' => [['2026-07-20', 10000]]]),
            '14b 92-day cycle Oct-Jan' => $b(['bills' => [['2026-10-01', '2026-10-15', 10000, 0]], 'gen' => '2027-01-01', 'pays' => [['2026-10-20', 10000]]]),
            '15 February monthly cycle (28 days)' => $b(['bills' => [['2027-02-01', '2027-02-15', 10000, 0]], 'gen' => '2027-03-01', 'pays' => [['2027-02-20', 10000]]]),
            '15b February nothing paid' => $b(['bills' => [['2027-02-01', '2027-02-15', 10000, 0]], 'gen' => '2027-03-01']),
            '16 leap year quarter (91 days) paid 29 Feb' => $b(['bills' => [['2028-01-01', '2028-01-15', 10000, 0]], 'gen' => '2028-04-01', 'pays' => [['2028-02-29', 10000]]]),
            '16b leap February (29 days)' => $b(['bills' => [['2028-02-01', '2028-02-15', 10000, 0]], 'gen' => '2028-03-01', 'pays' => [['2028-02-20', 10000]]]),
            '16c leap year: due 29 Feb, paid 1 Mar' => $b(['bills' => [['2028-02-01', '2028-02-29', 10000, 0]], 'gen' => '2028-03-01', 'pays' => [['2028-03-01', 10000]]]),
            '17 year boundary: due 15 Dec, nothing paid' => $b(['bills' => [['2026-12-01', '2026-12-15', 10000, 0]], 'gen' => '2027-01-01']),
            '17b year boundary: quarter Oct-Jan paid 31 Dec' => $b(['bills' => [['2026-10-01', '2026-10-15', 10000, 0]], 'gen' => '2027-01-01', 'pays' => [['2026-12-31', 10000]]]),
            '17c year boundary: due date in next year' => $b(['bills' => [['2026-12-01', '2027-01-05', 10000, 0]], 'gen' => '2027-02-01', 'pays' => [['2027-01-03', 10000]]]),
            '18 rate 12%' => $b(['rate' => 12, 'pays' => [['2026-07-20', 10000]]]),
            '18 rate 18%' => $b(['rate' => 18, 'pays' => [['2026-07-20', 10000]]]),
            '18 rate 24%' => $b(['rate' => 24, 'pays' => [['2026-07-20', 10000]]]),
            '19a due date NOT crossed: new bill 10 Jul (due 15 Jul)' => $b(['gen' => '2026-07-10']),
            '19b new bill ON the due date' => $b(['gen' => '2026-07-15']),
            '19c new bill 1 day after the due date' => $b(['gen' => '2026-07-16']),
            '19d due date 45 days out, new bill 1 Aug' => $b(['bills' => [['2026-07-01', '2026-08-15', 10000, 0]], 'gen' => '2026-08-01']),
            '20a previous bills fully paid before due' => $b(['bills' => $two, 'pays' => [['2026-04-10', 10000], ['2026-07-10', 10000]]]),
            '20b new bill two days after the last' => $b(['gen' => '2026-07-03']),
            '21 advance paid before the bill' => $b(['pays' => [['2026-06-20', 30000]]]),
            '22 credit note 4,000 on 10 Jul = payment' => $b(['credits' => [['2026-07-10', 4000]]]),
            '22b credit note after due date = late payment' => $b(['credits' => [['2026-07-20', 4000]]]),
            '22c credit note + payment on 10 Jul leave nothing' => $b(['credits' => [['2026-07-10', 4000]], 'pays' => [['2026-07-10', 6000]]]),
            '22d credit note dated before the last bill' => $b(['credits' => [['2026-06-20', 4000]]]),
            '22e credit note dated on the last bill date, counted once' => $b(['credits' => [['2026-07-01', 4000]]]),
            '23 bounced cheque is not a payment' => $b(['pays' => [['2026-07-10', 10000]], 'returned' => [0]]),
            '24 payment on the new bill date is next cycle' => $b(['pays' => [['2026-10-01', 10000]]]),
            '25 other owner (member transfer) paid' => $b(['pays' => [['2026-07-10', 10000, 'other']]]),
            '26 compound with carried interest' => $b(['type' => 3, 'bills' => [['2026-07-01', '2026-07-15', 10000, 500]]]),
            '26b compound, late partial payment' => $b(['type' => 3, 'bills' => [['2026-07-01', '2026-07-15', 10000, 500]], 'pays' => [['2026-07-20', 5000]]]),
            '27 two late partial payments' => $b(['pays' => [['2026-07-20', 5000], ['2026-08-20', 5000]]]),
        ];

        $cases = [];
        foreach ($list as $title => $s) {
            foreach ([1 => 'Delay Days', 3 => 'Complete Cycle Days'] as $m => $name) {
                $cases["$title | $name"] = [$s + ['method' => $m]];
            }
        }
        return $cases;
    }

    // ============================================================================================ the test
    #[DataProvider('scenarios')]
    public function test_interest_matches_the_business_rules(array $s): void
    {
        $returnedIds = [];
        foreach ($s['pays'] as $i => $p) {
            $id = DB::table('member_payments')->insertGetId([
                'society_id' => self::SOCIETY, 'receipt_id' => 0, 'member_id' => self::MEMBER,
                'member_transfer' => empty($p[2]) ? self::TRANSFER : 0,            // 'other' = the previous owner's payment
                'amount_paid' => $p[1], 'payment_date' => $p[0], 'bill_type' => 'reg', 'financial_year_id' => self::FY,
                'payment_mode' => 'cash', 'cdate' => now(), 'udate' => now(),
            ]);
            if (in_array($i, $s['returned'])) { $returnedIds[] = $id; }
        }
        foreach ($returnedIds as $id) {
            DB::table('cheque_return_details')->insert([
                'member_id' => self::MEMBER, 'society_id' => self::SOCIETY, 'payment_id' => $id, 'cheque_no' => 'T1',
                'cheque_return_reason' => 'test', 'member_transfer' => self::TRANSFER, 'financial_year_id' => self::FY,
            ]);
        }
        foreach ($s['credits'] as $c) {
            DB::table('journal_vouchers')->insert([
                'society_id' => self::SOCIETY, 'jv_amount_debited' => 0, 'jv_amount_credited' => $c[1], 'member_transfer' => self::TRANSFER,
                'voucher_date' => $c[0], 'voucher_no' => 1, 'jv_credit_member_head_id' => self::MEMBER, 'jv_creadit_type' => 'Credit',
                'jv_type' => 'Credit', 'financial_year_id' => self::FY, 'entry_type' => 'CN', 'adjust_payable' => 0,
                'cdate' => now(), 'udate' => now(),
            ]);
        }

        $bills = [];
        foreach ($s['bills'] as $i => $b) {
            $bills[] = [
                'id' => $i + 1, 'bill_generated_date' => $b[0], 'bill_due_date' => $b[1], 'monthly_amount' => $b[2], 'discount' => 0,
                'tax_total' => 0, 'principal_adjusted' => 0, 'interest_on_due_amount' => $b[3], 'interest_adjusted' => 0, 'tax_adjusted' => 0,
                'op_principal_arrears' => 0, 'op_tax_arrears' => 0, 'op_interest_arrears' => 0, 'bill_frequency_id' => 3,
                'interest_free_amount' => 0, 'financial_year_id' => self::FY,
            ];
        }
        $params = (object) ['method_id' => $s['method'], 'interest_type_id' => $s['type'], 'interest_rate' => $s['rate']];

        $controller = (new ReflectionClass(SocietyModuleController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(SocietyModuleController::class))->getMethod('getInterestOnDueAmount');
        $method->setAccessible(true);
        $actual = $method->invoke($controller, $params, end($bills), $s['gen'], self::MEMBER, self::SOCIETY, $bills, 'reg', self::TRANSFER, self::FY);

        [$exact, $segments] = self::oracle($s);
        $this->assertEqualsWithDelta(
            $exact, (float) $actual, 0.5 * $segments + 1e-9,
            "expected about {$exact} (round " . round($exact) . "), the application charged {$actual}"
        );
    }

    /** Delay Days = payment date - due date, to the day: 1 day late is exactly 1 day, 5 is 5, 10 is 10. */
    public function test_delay_days_counts_exactly_the_days_late(): void
    {
        $controller = (new ReflectionClass(SocietyModuleController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(SocietyModuleController::class))->getMethod('delayDays');
        $method->setAccessible(true);
        $params = (object) ['interest_rate' => 21, 'interest_type_id' => 2, 'method_id' => 1];

        // Rs.10,000 at 21% = Rs.5.7534 a day
        foreach (['2026-07-14' => 0, '2026-07-15' => 0, '2026-07-16' => 6, '2026-07-20' => 29, '2026-07-25' => 58, '2026-08-14' => 173] as $paid => $expected) {
            $days = 0;
            $args = [10000, 10000, $params, '2026-07-15', $paid, '2026-07-01', '2026-10-01', &$days];
            $this->assertSame($expected, (int) $method->invokeArgs($controller, $args), "paid $paid");
        }
    }

    /** Cycle days come from the real calendar: 90, 91 and 92 day quarters, never a fixed 90. */
    public function test_complete_cycle_days_uses_the_actual_cycle_length(): void
    {
        $controller = (new ReflectionClass(SocietyModuleController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(SocietyModuleController::class))->getMethod('completeCycleDays');
        $method->setAccessible(true);
        $params = (object) ['interest_rate' => 21, 'interest_type_id' => 2, 'method_id' => 3];

        // [last bill date, due date, day BEFORE the new bill, expected]  Rs.10,000 at 21%
        foreach ([
            '92 days Jul-Oct' => ['2026-07-01', '2026-07-15', '2026-09-30', 529],
            '90 days Jan-Apr' => ['2027-01-01', '2027-01-15', '2027-03-31', 518],
            '91 days Apr-Jul' => ['2026-04-01', '2026-04-15', '2026-06-30', 524],
            '91 days leap Jan-Apr' => ['2028-01-01', '2028-01-15', '2028-03-31', 524],
            'due date not crossed' => ['2026-07-01', '2026-07-15', '2026-07-09', 0],
        ] as $name => [$last, $due, $dayBefore, $expected]) {
            $this->assertSame($expected, (int) $method->invoke($controller, 10000, 10000, $params, $due, '', $last, $dayBefore), $name);
        }
    }
}
