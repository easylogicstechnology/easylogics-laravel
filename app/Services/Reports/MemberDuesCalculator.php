<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * The member dues figure used by "Dues From Members" and "Dues-Advance From Members"
 * (CakePHP AccountReportsController::getMembersDueAmount() + getMembersDueAmountBackData()).
 *
 * Per member: opening balance + (bills + journal-voucher debits) - (payments + journal-voucher credits),
 * all dated 2019-04-01 .. the "As on Date", for the member's current or previous (transfer - 1) record.
 * CakePHP runs its queries member by member; here they run once per chunk of members and are
 * split per member afterwards - the figures are the same.
 *
 * Amount columns are read as text (CAST .. AS CHAR) like CakePHP's text protocol, so the additions
 * happen on the same doubles.
 */
class MemberDuesCalculator
{
    private const CHUNK = 400;

    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $members rows with id, member_transfer, op_principal, op_interest, op_tax
     * @param string $reportType Regular | Supplementary | anything else (= all bill types)
     * @param string|null $memberRecord 'Current' | 'Old' | null (the Dues-Advance form has no such field)
     * @return array<int, array{dueAmount: float|int, date: string}> keyed by member id
     */
    public function dueAmounts(array $members, string $date, string $reportType, ?string $memberRecord): array
    {
        $out = [];
        foreach (array_chunk($members, self::CHUNK) as $chunk) {
            $out += $this->chunk($chunk, $date, $reportType, $memberRecord);
        }

        return $out;
    }

    private function chunk(array $members, string $date, string $reportType, ?string $memberRecord): array
    {
        $ids = array_map(fn ($m) => (int) $m['id'], $members);

        $billType = $reportType === 'Regular' ? 'reg' : ($reportType === 'Supplementary' ? 'sup' : null);

        // Opening balance rules (getMembersDueAmount).
        $identified = DB::table('member_identifications')
            ->where('status', 1)->where('society_id', $this->societyId)->whereIn('member_id', $ids)
            ->pluck('member_id')->flip()->all();

        $closing = $this->previousYearClosing($ids, $billType);

        // Bills / journal vouchers / payments up to the date, per member.
        $bills = $jvDr = $jvCr = $pays = [];
        if ($date !== '') {
            $q = DB::table('member_bill_summaries')
                ->select('member_id', 'member_transfer', 'bill_generated_date', DB::raw('CAST(monthly_bill_amount AS CHAR) as amt'))
                ->where('society_id', $this->societyId)->whereIn('member_id', $ids)
                ->where('bill_generated_date', '>=', '2019-04-01')->where('bill_generated_date', '<=', $date);
            if ($billType) {
                $q->where('bill_type', $billType);
            }
            foreach ($q->orderBy('month')->orderBy('id')->get() as $r) {
                $bills[$r->member_id][] = $r;
            }

            foreach (DB::table('journal_vouchers')
                ->select('jv_debit_member_head_id as mid', 'member_transfer', 'voucher_date', DB::raw('CAST(jv_amount_debited AS CHAR) as amt'))
                ->where('society_id', $this->societyId)->whereIn('jv_debit_member_head_id', $ids)
                ->where('voucher_date', '>=', '2019-04-01')->where('voucher_date', '<=', $date)
                ->orderBy('voucher_date')->get() as $r) {
                $jvDr[$r->mid][] = $r;
            }
            foreach (DB::table('journal_vouchers')
                ->select('jv_credit_member_head_id as mid', 'member_transfer', 'voucher_date', DB::raw('CAST(jv_amount_credited AS CHAR) as amt'))
                ->where('society_id', $this->societyId)->whereIn('jv_credit_member_head_id', $ids)
                ->where('voucher_date', '>=', '2019-04-01')->where('voucher_date', '<=', $date)
                ->orderBy('voucher_date')->get() as $r) {
                $jvCr[$r->mid][] = $r;
            }

            $q = DB::table('member_payments as a')
                ->leftJoin('cheque_return_details as b', function ($j) {
                    $j->on('a.member_id', '=', 'b.member_id')
                        ->on('b.cheque_no', '=', 'a.cheque_reference_number')
                        ->on('a.id', '=', 'b.payment_id');
                })
                ->select('a.id', 'a.member_id', 'a.member_transfer', 'a.payment_date', 'a.receipt_id', 'a.bill_generated_id', DB::raw('CAST(a.amount_paid AS CHAR) as amt'))
                ->where('a.society_id', $this->societyId)->whereIn('a.member_id', $ids)
                ->where('a.payment_date', '>=', '2019-04-01')->where('a.payment_date', '<=', $date)
                ->whereNull('b.cheque_no');
            if ($billType) {
                $q->where('a.bill_type', $billType);
            }
            foreach ($q->orderBy('a.bill_month')->get() as $r) {
                $pays[$r->member_id][] = $r;
            }
        }

        $result = [];
        foreach ($members as $m) {
            $id = (int) $m['id'];

            // opening balance
            $opening = $m['op_principal'] + $m['op_interest'] + $m['op_tax'];
            $hasIdentity = isset($identified[$id]);
            if ($hasIdentity && $memberRecord === 'Old') {
                if ($reportType === 'Regular') {
                    $opening = $m['op_principal'] + $m['op_interest'] + $m['op_tax'];
                }
                if ($reportType === 'Supplementary') {
                    $opening = 0; // CakePHP reads supplementary_* fields it never selected: null + null + null
                }
            } elseif ($hasIdentity && $memberRecord === 'Current') {
                $opening = 0;
            } elseif (!$hasIdentity && $memberRecord === 'Current') {
                if ($reportType === 'Regular') {
                    $opening = $m['op_principal'] + $m['op_interest'] + $m['op_tax'];
                }
                if ($reportType === 'Supplementary') {
                    $opening = 0;
                }
            }
            if (isset($closing[$id])) {
                $opening = $closing[$id];
            }

            // member_transfer of the record asked for
            $transfer = (int) $m['member_transfer'];
            if ($memberRecord === 'Old') {
                // CakePHP: member_transfer - 1, then "if ($val <= -1) member_transfer = 0" with $val never set;
                // null <= -1 is true in PHP, so the old record always reads member_transfer 0.
                $transfer = 0;
            }

            // getMembersDueAmountBackData: everything into one list, ordered by date
            $entries = [];
            foreach ($bills[$id] ?? [] as $r) {
                if ((int) $r->member_transfer === $transfer) {
                    $entries[] = ['ts' => strtotime($r->bill_generated_date), 'bill' => $r->amt, 'dr' => 0, 'cr' => 0, 'paid' => 0];
                }
            }
            foreach ($jvDr[$id] ?? [] as $r) {
                if ((int) $r->member_transfer === $transfer) {
                    $entries[] = ['ts' => strtotime($r->voucher_date), 'bill' => 0, 'dr' => $r->amt, 'cr' => 0, 'paid' => 0];
                }
            }
            foreach ($jvCr[$id] ?? [] as $r) {
                if ((int) $r->member_transfer === $transfer) {
                    $entries[] = ['ts' => strtotime($r->voucher_date), 'bill' => 0, 'dr' => 0, 'cr' => $r->amt, 'paid' => 0];
                }
            }
            foreach ($pays[$id] ?? [] as $r) {
                if ((int) $r->member_transfer === $transfer) {
                    $entries[] = ['ts' => strtotime($r->payment_date), 'bill' => 0, 'dr' => 0, 'cr' => 0, 'paid' => $r->amt];
                }
            }
            usort($entries, fn ($a, $b) => $a['ts'] <=> $b['ts']);

            if (!$entries) {
                // CakePHP: nothing to add up, $dateName is never set -> date(..., false) = the epoch
                $result[$id] = ['dueAmount' => 0 + $opening, 'date' => '01/01/1970'];
                continue;
            }

            $prev = 0;
            $due = 0;
            foreach ($entries as $e) {
                $totalPaid = $e['paid'] + $e['cr'];
                $totalBill = $e['bill'] + $e['dr'] + $prev;
                $due = $totalBill - $totalPaid;
                $prev = $due;
            }
            $result[$id] = ['dueAmount' => $due + $opening, 'date' => date('d/m/Y', strtotime($date))];
        }

        return $result;
    }

    /**
     * Previous financial year's closing (principal + interest + tax), summed over the member's rows,
     * per member; only members with at least one row appear.
     *
     * @return array<int, float>
     */
    private function previousYearClosing(array $ids, ?string $billType): array
    {
        $previous = ($this->financialYearId ?? 0) - 1;
        if ($previous <= 0) {
            return [];
        }
        $q = DB::table('member_year_wise_closing_balance')
            ->select('member_id', DB::raw('CAST(principal_balance AS CHAR) as p'), DB::raw('CAST(interest_balance AS CHAR) as i'), DB::raw('CAST(tax_balance AS CHAR) as t'))
            ->where('society_id', $this->societyId)->where('year_id', $previous)->whereIn('member_id', $ids);
        if ($billType) {
            $q->where('bill_type', $billType);
        }
        $out = [];
        foreach ($q->orderBy('id')->get() as $r) {
            $out[$r->member_id] = ($out[$r->member_id] ?? 0) + (floatval($r->p) + floatval($r->i) + floatval($r->t));
        }

        return $out;
    }

    /** "Current Dues-Advance": the balance on the member's last regular bill of this year up to the date. */
    public function currentYearLastBillDue(array $members, string $asOnDate): array
    {
        $out = [];
        $ids = array_map(fn ($m) => (int) $m['id'], $members);
        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            $rows = DB::table('member_bill_summaries')
                ->select('member_id', 'bill_generated_date', DB::raw('CAST(balance_amount AS CHAR) as bal'))
                ->where('society_id', $this->societyId)->where('financial_year_id', $this->financialYearId)
                ->where('bill_type', 'reg')->whereIn('member_id', $chunk)
                ->where('bill_generated_date', '<=', $asOnDate)
                ->orderByDesc('bill_generated_date')->orderByDesc('id')->get();
            foreach ($rows as $r) {
                if (!isset($out[$r->member_id])) {
                    $out[$r->member_id] = ['dueAmount' => (float) $r->bal, 'date' => date('d/m/Y', strtotime($r->bill_generated_date))];
                }
            }
        }
        foreach ($ids as $id) {
            $out[$id] ??= ['dueAmount' => 0, 'date' => date('d/m/Y', strtotime($asOnDate))];
        }

        return $out;
    }
}
