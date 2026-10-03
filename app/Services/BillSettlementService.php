<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberBillSettlement;
use App\Models\MemberBillSummary;
use App\Models\MemberPayment;
use App\Models\SocietyParameter;
use App\Support\JournalNotes;
use Illuminate\Support\Facades\DB;

class BillSettlementService
{
    /**
     * Master recalculation — matches CakePHP's updateMemberBillSummaryById() exactly.
     * Sequential processing: for each bill, setup (recalc interest) → allocate → next bill.
     */
    public function recalculateMemberBills($memberId, $societyId, $billType, $financialYearId, $memberTransfer = 0)
    {
        return DB::transaction(function () use ($memberId, $societyId, $billType, $financialYearId, $memberTransfer) {

            $bills = MemberBillSummary::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->where('member_transfer', $memberTransfer)
                ->orderBy('id', 'asc')
                ->get();

            if ($bills->isEmpty()) {
                return false;
            }

            // Get cheque return payment IDs to exclude
            $chequeReturnPaymentIds = $this->getChequeReturnPaymentIds($memberId, $societyId, $memberTransfer, $financialYearId);

            // Fetch payments excluding cheque returns
            $payments = MemberPayment::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->where('member_transfer', $memberTransfer)
                ->when(!empty($chequeReturnPaymentIds), function ($q) use ($chequeReturnPaymentIds) {
                    $q->whereNotIn('id', $chequeReturnPaymentIds);
                })
                ->orderBy('payment_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Delete existing settlements for these payments
            $allPaymentIds = MemberPayment::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->where('member_transfer', $memberTransfer)
                ->pluck('id')->toArray();
            if (!empty($allPaymentIds)) {
                MemberBillSettlement::whereIn('payment_id', $allPaymentIds)->delete();
            }

            // Load society parameters for interest recalculation
            $societyParams = SocietyParameter::where('society_id', $societyId)->first();
            $interestTypeId = $societyParams->interest_type_id ?? 1;
            $interestRate = floatval($societyParams->interest_rate ?? 0);
            $methodId = $societyParams->method_id ?? 5;

            // Build bills array
            $billsArr = [];
            // Manual Debit/Credit Note rows are applied to their pinned bill (CakePHP attachManualNoteDeltas()).
            $noteDeltas = ($billType == 'reg')
                ? JournalNotes::manualDeltasByBillNo($memberId, $societyId, $memberTransfer, $financialYearId)
                : [];
            foreach ($bills as $bill) {
                $billsArr[] = $bill->toArray() + JournalNotes::billKeys($noteDeltas, $billType, $bill->bill_no);
            }
            $totalBills = count($billsArr);

            // Settlement data tracking
            $paymentUsed = [];
            $settlementRecords = [];

            // Sequential processing: for each bill, setup → allocate → next bill
            for ($outerIdx = 0; $outerIdx < $totalBills; $outerIdx++) {
                $this->setupBill($billsArr, $outerIdx, $totalBills, $memberId, $societyId,
                    $billType, $memberTransfer, $financialYearId, $interestTypeId, $interestRate, $methodId);

                // Determine payment date boundary
                $boundary = ($outerIdx + 1 < $totalBills)
                    ? $billsArr[$outerIdx + 1]['bill_generated_date']
                    : $billsArr[$outerIdx]['bill_end_date'];

                // Inner loop: allocate payments from bill 0 to current bill
                $this->allocatePayments($billsArr, $outerIdx, $totalBills, $payments,
                    $boundary, $paymentUsed, $settlementRecords, $memberId, $financialYearId, $chequeReturnPaymentIds);
            }

            // Save updated bill summaries
            foreach ($bills as $i => $billModel) {
                $billModel->update([
                    'tax_paid' => $billsArr[$i]['tax_paid'],
                    'interest_paid' => $billsArr[$i]['interest_paid'],
                    'principal_paid' => $billsArr[$i]['principal_paid'],
                    'tax_balance' => $billsArr[$i]['tax_balance'],
                    'interest_balance' => $billsArr[$i]['interest_balance'],
                    'principal_balance' => $billsArr[$i]['principal_balance'],
                    'balance_amount' => $billsArr[$i]['balance_amount'],
                    'monthly_principal_amount' => $billsArr[$i]['monthly_principal_amount'],
                    'monthly_bill_amount' => $billsArr[$i]['monthly_bill_amount'],
                    'amount_payable' => $billsArr[$i]['amount_payable'],
                    'op_principal_arrears' => $billsArr[$i]['op_principal_arrears'],
                    'op_interest_arrears' => $billsArr[$i]['op_interest_arrears'],
                    'op_tax_arrears' => $billsArr[$i]['op_tax_arrears'],
                    'op_due_amount' => $billsArr[$i]['op_due_amount'],
                    'interest_on_due_amount' => $billsArr[$i]['interest_on_due_amount'],
                    'jv_adjustment' => $billsArr[$i]['jv_adjustment'],
                ]);
            }

            // Save settlement records
            foreach ($settlementRecords as $record) {
                MemberBillSettlement::create($record);
            }

            return true;
        });
    }

    /**
     * Setup bill N: reset paid amounts, recalculate arrears from previous bill
     * (which has already been allocated), recalculate interest, compute balances.
     * Matches CakePHP's outer loop body in updateMemberBillSummaryById().
     */
    private function setupBill(array &$billsArr, int $idx, int $totalBills, $memberId, $societyId,
        $billType, $memberTransfer, $financialYearId, $interestTypeId, $interestRate, $methodId)
    {
        $billsArr[$idx]['tax_paid'] = 0;
        $billsArr[$idx]['interest_paid'] = 0;
        $billsArr[$idx]['principal_paid'] = 0;

        // JV adjustment for regular bills
        $jvDebitedAmt = 0;
        if ($billType == 'reg') {
            $jvDebitedAmt = $this->getDebitedJvAmount(
                $billsArr[$idx]['bill_generated_date'],
                $billsArr[$idx]['bill_end_date'],
                $memberId, $societyId, $memberTransfer, $financialYearId
            );
        }
        $billsArr[$idx]['jv_adjustment'] = $jvDebitedAmt;

        if ($idx == 0) {
            // First bill: use existing op_arrears (or zero if transferred)
            if (!empty($memberTransfer)) {
                $billsArr[$idx]['op_principal_arrears'] = 0;
                $billsArr[$idx]['op_tax_arrears'] = 0;
                $billsArr[$idx]['op_interest_arrears'] = 0;
            }

            $billsArr[$idx]['monthly_principal_amount'] = $billsArr[$idx]['monthly_amount'] - $billsArr[$idx]['discount'];
            $billsArr[$idx]['monthly_bill_amount'] = $billsArr[$idx]['monthly_principal_amount']
                + $billsArr[$idx]['interest_on_due_amount']
                + $billsArr[$idx]['tax_total'];
            $billsArr[$idx]['amount_payable'] = $billsArr[$idx]['op_due_amount']
                + $billsArr[$idx]['monthly_bill_amount'] + $jvDebitedAmt + $billsArr[$idx]['_dn_payable'];

            $billsArr[$idx]['principal_balance'] = ($billsArr[$idx]['op_principal_arrears']
                + $billsArr[$idx]['monthly_principal_amount'])
                - $billsArr[$idx]['principal_adjusted'] + $jvDebitedAmt + $billsArr[$idx]['_dn_principal'];
            $billsArr[$idx]['interest_balance'] = ($billsArr[$idx]['op_interest_arrears']
                + $billsArr[$idx]['interest_on_due_amount'])
                - $billsArr[$idx]['interest_adjusted'] + $billsArr[$idx]['_dn_interest'];
            $billsArr[$idx]['tax_balance'] = $billsArr[$idx]['tax_total']
                + $billsArr[$idx]['op_tax_arrears'] + $billsArr[$idx]['_dn_tax'];
        } else {
            // Subsequent bills: arrears come from previous bill's POST-ALLOCATION state
            $prev = $billsArr[$idx - 1];

            $billsArr[$idx]['op_tax_arrears'] = $prev['tax_balance'];
            $prevPrincipalBal = $prev['principal_balance'];
            $prevBalance = $prev['balance_amount'];
            $billsArr[$idx]['op_principal_arrears'] = ($prevBalance < 0) ? $prevBalance : $prevPrincipalBal;
            $billsArr[$idx]['op_interest_arrears'] = $prev['interest_balance'];
            $billsArr[$idx]['op_due_amount'] = $prev['balance_amount'];

            // Recalculate interest based on previous bill's post-allocation balance
            if ($interestTypeId != 4) { // 4 = manual, don't recalculate
                $billsArr[$idx]['interest_on_due_amount'] = $this->calculateInterest(
                    $billsArr[$idx], $prev, $interestTypeId, $interestRate, $methodId
                );
            }

            $billsArr[$idx]['monthly_principal_amount'] = $billsArr[$idx]['monthly_amount'] - $billsArr[$idx]['discount'];
            $billsArr[$idx]['monthly_bill_amount'] = $billsArr[$idx]['monthly_principal_amount']
                + $billsArr[$idx]['interest_on_due_amount']
                + $billsArr[$idx]['tax_total'];
            $billsArr[$idx]['amount_payable'] = $billsArr[$idx]['op_due_amount']
                + $billsArr[$idx]['monthly_bill_amount'] + $billsArr[$idx]['_dn_payable'];

            $billsArr[$idx]['principal_balance'] = $billsArr[$idx]['monthly_principal_amount']
                + $prev['principal_balance'] + $jvDebitedAmt + $billsArr[$idx]['_dn_principal'];
            $billsArr[$idx]['interest_balance'] = $billsArr[$idx]['interest_on_due_amount']
                + $prev['interest_balance'] + $billsArr[$idx]['_dn_interest'];
            $billsArr[$idx]['tax_balance'] = $billsArr[$idx]['tax_total']
                + $prev['tax_balance'] + $billsArr[$idx]['_dn_tax'];
        }

        $billsArr[$idx]['balance_amount'] = $billsArr[$idx]['principal_balance']
            + $billsArr[$idx]['interest_balance']
            + $billsArr[$idx]['tax_balance'];
    }

    /**
     * Inner loop: allocate payments to bills 0..outerIdx.
     * Matches CakePHP's addPaymentInUpdation() + deductPaidAmount() + setPaymentData().
     */
    private function allocatePayments(array &$billsArr, int $outerIdx, int $totalBills,
        $payments, $boundary, array &$paymentUsed, array &$settlementRecords,
        $memberId, $financialYearId, $chequeReturnPaymentIds)
    {
        $currentBillId = $billsArr[$outerIdx]['id'];

        for ($innerIdx = 0; $innerIdx <= $outerIdx; $innerIdx++) {
            if ($billsArr[$innerIdx]['id'] > $currentBillId) {
                break;
            }

            if ($billsArr[$innerIdx]['tax_balance'] <= 0
                && $billsArr[$innerIdx]['interest_balance'] <= 0
                && $billsArr[$innerIdx]['principal_balance'] <= 0) {
                continue;
            }

            $hasPayment = false;
            foreach ($payments as $payment) {
                if (strtotime($payment->payment_date) > strtotime($boundary)) {
                    continue;
                }

                $remaining = $payment->amount_paid - ($paymentUsed[$payment->id] ?? 0);
                if ($remaining <= 0) {
                    continue;
                }

                $hasPayment = true;
                $paidAmount = $remaining;
                $taxPaid = 0;
                $interestPaid = 0;
                $principalPaid = 0;

                // Tax deduction
                if ($billsArr[$innerIdx]['tax_balance'] > 0 && $paidAmount > 0) {
                    $deduct = min($paidAmount, $billsArr[$innerIdx]['tax_balance']);
                    $billsArr[$innerIdx]['tax_balance'] -= $deduct;
                    $billsArr[$innerIdx]['tax_paid'] += $deduct;
                    $paidAmount -= $deduct;
                    $taxPaid = $deduct;
                    // Propagate: recalculate subsequent bills' tax_balance
                    for ($t = $innerIdx + 1; $t < $totalBills; $t++) {
                        $billsArr[$t]['tax_balance'] = $billsArr[$t]['tax_total']
                            + $billsArr[$t - 1]['tax_balance'] + $billsArr[$t]['_dn_tax'];
                        $billsArr[$t]['balance_amount'] = $billsArr[$t]['principal_balance']
                            + $billsArr[$t]['interest_balance']
                            + $billsArr[$t]['tax_balance'];
                    }
                }

                // Interest deduction
                if ($billsArr[$innerIdx]['interest_balance'] > 0 && $paidAmount > 0) {
                    $deduct = min($paidAmount, $billsArr[$innerIdx]['interest_balance']);
                    $billsArr[$innerIdx]['interest_balance'] -= $deduct;
                    $billsArr[$innerIdx]['interest_paid'] += $deduct;
                    $paidAmount -= $deduct;
                    $interestPaid = $deduct;
                    // Propagate: recalculate subsequent bills' interest_balance
                    for ($in = $innerIdx + 1; $in < $totalBills; $in++) {
                        $billsArr[$in]['interest_balance'] = $billsArr[$in]['interest_on_due_amount']
                            + $billsArr[$in - 1]['interest_balance'] + $billsArr[$in]['_dn_interest'];
                        $billsArr[$in]['balance_amount'] = $billsArr[$in]['principal_balance']
                            + $billsArr[$in]['interest_balance']
                            + $billsArr[$in]['tax_balance'];
                    }
                }

                // Principal deduction
                if ($billsArr[$innerIdx]['principal_balance'] > 0 && $paidAmount > 0) {
                    $deduct = min($paidAmount, $billsArr[$innerIdx]['principal_balance']);
                    $billsArr[$innerIdx]['principal_balance'] -= $deduct;
                    $billsArr[$innerIdx]['principal_paid'] += $deduct;
                    $paidAmount -= $deduct;
                    $principalPaid = $deduct;
                    // Propagate: recalculate subsequent bills' principal_balance
                    for ($p = $innerIdx + 1; $p < $totalBills; $p++) {
                        $billsArr[$p]['principal_balance'] = $billsArr[$p]['monthly_principal_amount']
                            + $billsArr[$p]['jv_adjustment']
                            + $billsArr[$p - 1]['principal_balance'] + $billsArr[$p]['_dn_principal'];
                        $billsArr[$p]['balance_amount'] = $billsArr[$p]['principal_balance']
                            + $billsArr[$p]['interest_balance']
                            + $billsArr[$p]['tax_balance'];
                    }
                }

                $billsArr[$innerIdx]['balance_amount'] = $billsArr[$innerIdx]['principal_balance']
                    + $billsArr[$innerIdx]['interest_balance']
                    + $billsArr[$innerIdx]['tax_balance'];

                $used = $remaining - $paidAmount;
                $paymentUsed[$payment->id] = ($paymentUsed[$payment->id] ?? 0) + $used;

                if ($taxPaid > 0 || $interestPaid > 0 || $principalPaid > 0) {
                    $settlementRecords[] = [
                        'member_id' => $memberId,
                        'payment_id' => $payment->id,
                        'bill_summary_id' => $billsArr[$innerIdx]['id'],
                        'bill_no' => $billsArr[$innerIdx]['bill_no'],
                        'bill_type' => $billsArr[$innerIdx]['bill_type'],
                        'bill_month' => $billsArr[$innerIdx]['month'],
                        'principal_paid' => $principalPaid,
                        'interest_paid' => $interestPaid,
                        'tax_paid' => $taxPaid,
                        'payable_amount' => $principalPaid + $interestPaid + $taxPaid,
                        'financial_year_id' => $financialYearId,
                    ];
                }

                if ($billsArr[$innerIdx]['tax_balance'] <= 0
                    && $billsArr[$innerIdx]['interest_balance'] <= 0
                    && $billsArr[$innerIdx]['principal_balance'] <= 0) {
                    break;
                }
            }

            if (!$hasPayment) {
                break;
            }
        }

        // Matches CakePHP's updateMemberBillSummaryById(): after addPaymentInUpdation()
        // returns, it subtracts whatever payment pool is still left over (couldn't be
        // absorbed by any bill's own tax/interest/principal balance, which the loop above
        // floors at 0 - same as Cake's own deductPaidAmount()) directly from THIS bill's
        // balance_amount, not from the three component balances. That is what lets
        // balance_amount go negative to represent an advance/credit, which setupBill()'s
        // ($prevBalance < 0) check then carries into the next bill's arrears - without this
        // step an overpayment beyond all currently-existing bills' dues was simply discarded.
        $leftover = 0;
        foreach ($payments as $payment) {
            if (strtotime($payment->payment_date) > strtotime($boundary)) {
                continue;
            }
            $leftover += $payment->amount_paid - ($paymentUsed[$payment->id] ?? 0);
        }
        if ($leftover > 0) {
            $billsArr[$outerIdx]['balance_amount'] -= $leftover;
        }
    }

    /**
     * Calculate interest for a bill based on previous bill's post-allocation balance.
     * Matches CakePHP's completeMonths() for method_id=5.
     */
    private function calculateInterest(array $currentBill, array $prevBill, int $interestTypeId, float $interestRate, int $methodId): float
    {
        if ($interestRate <= 0) {
            return 0;
        }

        // For method_id=5 (Complete Months), use billing frequency
        if ($methodId == 5) {
            $delayMonth = $this->getMonthNoFromBillFreq($prevBill['bill_frequency_id'] ?? 1);

            if ($interestTypeId == 1) {
                // No interest
                return 0;
            } elseif ($interestTypeId == 2) {
                // Simple: interest on principal balance only
                $principalForInterest = $prevBill['principal_balance'] - ($prevBill['interest_free_amount'] ?? 0);
                if ($principalForInterest <= 0) return 0;
                $interestPerMonth = $principalForInterest * (($interestRate / 12) / 100);
                return round($interestPerMonth * $delayMonth);
            } elseif ($interestTypeId == 3) {
                // Compound: interest on total balance (principal + interest)
                $balanceForInterest = $prevBill['balance_amount'] - ($prevBill['interest_free_amount'] ?? 0);
                if ($balanceForInterest <= 0) return 0;
                $interestPerMonth = $balanceForInterest * (($interestRate / 12) / 100);
                return round($interestPerMonth * $delayMonth);
            }
        }

        // For other methods (1=DelayDays, 2=DelayMonths, 3=CompleteCycleDays, 4=CompleteCycleMonthly)
        // These require payment-date-based calculation which is more complex.
        // Fall back to existing interest_on_due_amount if method not fully supported.
        if ($prevBill['principal_balance'] <= 0 && $prevBill['balance_amount'] <= 0) {
            return 0;
        }

        return floatval($currentBill['interest_on_due_amount']);
    }

    private function getMonthNoFromBillFreq($billFreq): int
    {
        return match ((int)$billFreq) {
            1 => 1,   // monthly
            2 => 2,   // bi-monthly
            3 => 3,   // quarterly
            4 => 4,   // quadruple
            5 => 6,   // half-yearly
            default => 12, // yearly
        };
    }

    /**
     * Get JV debit amount for a bill period.
     * Matches CakePHP's getDebitedJvAmount().
     */
    private function getDebitedJvAmount($fromDate, $toDate, $memberId, $societyId, $memberTransfer, $financialYearId): float
    {
        $query = DB::table('journal_vouchers')
            ->where('jv_debit_member_head_id', $memberId)
            ->where('society_id', $societyId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_date', '>=', $fromDate)
            ->where('voucher_date', '<=', $toDate);

        // MANUAL Debit/Credit Note rows are applied to their pinned bill instead (CakePHP jvNoManualSql()).
        $result = JournalNotes::excludeManual($query)->sum('jv_amount_debited');

        return floatval($result);
    }

    /**
     * Get payment IDs that have been returned (cheque bounce).
     * Matches CakePHP's getChequeRetPaymentId().
     */
    private function getChequeReturnPaymentIds($memberId, $societyId, $memberTransfer, $financialYearId): array
    {
        try {
            return DB::table('cheque_return_details')
                ->where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('member_transfer', $memberTransfer)
                ->where('financial_year_id', $financialYearId)
                ->where('payment_id', '>', 0)
                ->pluck('payment_id')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // Matches CakePHP's getLatestTranferredId() exactly: reads members.member_transfer
    // directly, NOT a MAX() over that member's bills. Those two disagree in a common,
    // real case - a member who has just been transferred (member_transfer bumped) but has
    // no bill yet for that new generation - where MAX(bills.member_transfer) still returns
    // the OLD generation's number (or NULL/0 if they have no bills at all). Every caller of
    // this method (payment save/delete, cheque return, old Update, Current Bill Update,
    // tariff edits) would then silently operate against the transferred-out owner's old
    // bills instead of the new owner's, until the next bill happened to be generated.
    public function getLatestTransferNo($memberId, $societyId)
    {
        return Member::where('id', $memberId)
            ->where('society_id', $societyId)
            ->value('member_transfer') ?? 0;
    }

    /**
     * "Current Bill Update" (CakePHP updateCurrentMemberBillSummaryById): recalculates
     * $currentBillId only and never writes a change to any other bill in the chain.
     *
     * recalculateMemberBills() above is the only recalculation engine that exists (it
     * mirrors Cake's own sequential algorithm, where each bill's numbers depend on the
     * previous bill's already-processed state) - rather than hand-porting Cake's
     * separate ~150-line single-bill calculation path as new, unreviewed financial
     * logic, this reuses that same trusted engine: it runs the full chain, then
     * restores every bill (and every settlement row) except the current one back to
     * its exact pre-run state, so only the current bill's outcome is ever persisted.
     *
     * Caveat: if an earlier bill's saved values were already out of sync with what a
     * fresh recalc would produce (data drift), the current bill's freshly computed
     * numbers are derived from the fresh (in-run) previous-bill values, not the stale
     * saved ones - CakePHP's own single-bill code instead trusts the saved previous
     * bill's balances as-is. This only differs from Cake when such drift already
     * exists; the normal case (earlier bills already consistent) produces identical
     * results either way.
     */
    public function recalculateCurrentBillOnly($memberId, $societyId, $billType, $financialYearId, $memberTransfer, $currentBillId)
    {
        return DB::transaction(function () use ($memberId, $societyId, $billType, $financialYearId, $memberTransfer, $currentBillId) {
            $billIds = MemberBillSummary::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->where('member_transfer', $memberTransfer)
                ->pluck('id');

            if (!$billIds->contains($currentBillId)) {
                return false;
            }

            $billSnapshot = MemberBillSummary::whereIn('id', $billIds)->get()->keyBy('id')
                ->map(fn ($b) => $b->getAttributes());

            $paymentIds = MemberPayment::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->where('member_transfer', $memberTransfer)
                ->pluck('id');

            $settlementSnapshot = $paymentIds->isEmpty() ? collect()
                : MemberBillSettlement::whereIn('payment_id', $paymentIds)->get()
                    ->map(fn ($s) => $s->getAttributes());

            $ok = $this->recalculateMemberBills($memberId, $societyId, $billType, $financialYearId, $memberTransfer);
            if (!$ok) {
                return false;
            }

            $freshCurrentBillSettlements = $paymentIds->isEmpty() ? collect()
                : MemberBillSettlement::whereIn('payment_id', $paymentIds)
                    ->where('bill_summary_id', $currentBillId)->get()
                    ->map(fn ($s) => $s->getAttributes());

            foreach ($billSnapshot as $id => $attrs) {
                if ((int) $id === (int) $currentBillId) {
                    continue;
                }
                unset($attrs['id']);
                DB::table('member_bill_summaries')->where('id', $id)->update($attrs);
            }

            if (!$paymentIds->isEmpty()) {
                MemberBillSettlement::whereIn('payment_id', $paymentIds)->delete();
                foreach ($settlementSnapshot as $row) {
                    unset($row['id']);
                    DB::table('member_bill_settlements')->insert($row);
                }
                MemberBillSettlement::whereIn('payment_id', $paymentIds)
                    ->where('bill_summary_id', $currentBillId)->delete();
                foreach ($freshCurrentBillSettlements as $row) {
                    unset($row['id']);
                    DB::table('member_bill_settlements')->insert($row);
                }
            }

            return true;
        });
    }
}
