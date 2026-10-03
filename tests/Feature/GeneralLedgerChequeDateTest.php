<?php

namespace Tests\Feature;

use App\Services\Reports\GeneralLedgerReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * General Ledger (Cake account_reports/account_general_ledger): the 5th date column of a society payment is its cheque
 * date; a cash payment has none, and must show its payment date (not 01/01/1970). Needs a scratch copy of the CakePHP
 * database (name must contain "scratch").
 */
class GeneralLedgerChequeDateTest extends TestCase
{
    private const SOCIETY = 10405;

    private int $headId;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $this->cleanUp();
        $this->headId = DB::table('society_ledger_heads')->insertGetId([
            'society_id' => self::SOCIETY, 'title' => 'GLTEST head', 'is_in_bill_charges' => 0, 'status' => 1, 'account_head_id' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->cleanUp();
        }

        parent::tearDown();
    }

    private function cleanUp(): void
    {
        $ids = DB::table('society_ledger_heads')->where('title', 'GLTEST head')->pluck('id');
        DB::table('society_payments')->whereIn('ledger_head_id', $ids)->delete();
        DB::table('society_ledger_heads')->whereIn('id', $ids)->delete();
    }

    private function payment(string $date, ?string $chequeDate, string $ref): void
    {
        DB::table('society_payments')->insert([
            'society_id' => self::SOCIETY, 'ledger_head_id' => $this->headId, 'payment_date' => $date, 'cheque_date' => $chequeDate,
            'total_amount' => 100, 'amount' => 100, 'particulars' => 'p ' . $ref, 'cheque_reference_number' => $ref, 'bill_voucher_number' => 1,
        ]);
    }

    public function test_a_payment_without_a_cheque_date_shows_its_payment_date(): void
    {
        $this->payment('2025-05-24', null, 'cash-null');
        $this->payment('2025-12-15', '0000-00-00', 'cash-zero');
        $this->payment('2025-07-03', '2025-07-05', 'bank');

        $report = new GeneralLedgerReport(self::SOCIETY, 21);
        $out = $report->run(['ledger_for' => 'Particular Subgroup', 'account_name' => $this->headId], $report->ledgerHeads());

        $rows = [];
        foreach ($out as $head) {
            foreach ($head['data']['ledgerPaymentData'] ?? [] as $e) {
                $rows[$e['cheque_no']] = [$e['payment_date'], $e['cheque_date']];
            }
        }

        $this->assertSame(['24/05/2025', '24/05/2025'], $rows['cash-null']);
        $this->assertSame(['15/12/2025', '15/12/2025'], $rows['cash-zero']);
        $this->assertSame(['03/07/2025', '05/07/2025'], $rows['bank'], 'a real cheque date is kept');
        foreach ($rows as [, $chequeDate]) {
            $this->assertStringNotContainsString('1970', $chequeDate);
            $this->assertStringNotContainsString('1969', $chequeDate);
        }
    }
}
