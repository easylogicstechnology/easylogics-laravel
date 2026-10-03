<?php

namespace App\Services\Reports;

use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * General Ledger (CakePHP account_reports/account_general_ledger): for the chosen ledger head(s) the payments,
 * bill charges, general receipts and journal vouchers in date order. Builds the array the CakePHP view walks
 * ($societyLedgerHeadsData); the view does the opening / running balance.
 * Free text from users (particulars, notes ...) is escaped here because the carried-over view prints it as is.
 */
class GeneralLedgerReport
{
    public function __construct(private int $societyId, private ?int $financialYearId)
    {
    }

    /** the society's active ledger heads (dropdown / "All"): [['id','title','society_head_sub_category_id']] */
    public function ledgerHeads(): array
    {
        return DB::table('society_ledger_heads')->where('society_id', $this->societyId)->where('status', 1)->orderBy('id')
            ->get(['id', 'title', 'society_head_sub_category_id'])->map(fn ($r) => (array) $r)->all();
    }

    /** @return array<int, array{data: array, title: string}> */
    public function run(array $in, array $heads): array
    {
        $from = $in['payment_date'] ?? '';
        $to = $in['payment_date_to'] ?? '';
        $both = !empty($from) && !empty($to);

        // the date condition of every table (CakePHP: a lone From date is compared with the empty To date)
        $dates = function ($q, string $col) use ($from, $to, $both) {
            if ($both) {
                $q->whereBetween($col, [$from, $to]);
            } elseif (!empty($from)) {
                $q->where($col, $to);
            } elseif (!empty($to)) {
                $q->where($col, $to);
            }

            return $q;
        };

        $ledgerFor = $in['ledger_for'] ?? '';
        $ids = [];
        if (!empty($ledgerFor)) {
            if ($ledgerFor == 'Particular Subgroup') {
                if (!empty($in['account_name'])) {
                    $ids[] = $in['account_name'];
                }
            } elseif ($ledgerFor == 'All') {
                foreach ($heads as $h) {
                    $ids[] = $h['id'];
                }
            } else {
                foreach ($heads as $h) {
                    if (!in_array($h['society_head_sub_category_id'], [20, 21])) {
                        $ids[] = $h['id'];
                    }
                }
            }
        }

        $out = [];
        foreach ($ids as $headId) {
            $row = DB::table('society_ledger_heads as h')
                ->leftJoin('account_heads as a', 'a.id', '=', 'h.account_head_id')
                ->where('h.society_id', $this->societyId)->where('h.id', $headId)
                ->select(array_merge(TextSelect::columns('society_ledger_heads', 'h', 'lh__'), [DB::raw('a.title as ah__title'), DB::raw('a.transaction_type as ah__transaction_type')]))
                ->orderBy('h.id')->first();
            $details = ['SocietyLedgerHeads' => [], 'AccountHead' => []];
            if ($row) {
                foreach ((array) $row as $k => $v) {
                    if (str_starts_with($k, 'lh__')) {
                        $details['SocietyLedgerHeads'][substr($k, 4)] = $v;
                    } else {
                        $details['AccountHead'][substr($k, 4)] = $v;
                    }
                }
            }
            $details['SocietyLedgerHeads']['opening_amount'] = $this->openingBalance($headId);

            if ($row) {
                $txnType = $details['AccountHead']['transaction_type'];
                if (stripos((string) $txnType, 'credit') !== false) {
                    $txnType = 'credit';
                } elseif (stripos((string) $txnType, 'debit') !== false) {
                    $txnType = 'debit';
                }
                $isInBill = $details['SocietyLedgerHeads']['is_in_bill_charges'];
                $entries = [];
                $id = $details['SocietyLedgerHeads']['id'];

                if ($isInBill == 0) {
                    $q = DB::table('society_payments as p')->select(TextSelect::columns('society_payments', 'p'))
                        ->where('p.society_id', $this->societyId)->where('p.ledger_head_id', $id);
                    $dates($q, 'p.payment_date');
                    foreach ($q->get() as $p) {
                        $paymentDate = date('d/m/Y', strtotime($p->payment_date));
                        $entries[] = [
                            'payment_date' => $paymentDate, 'amount' => $p->total_amount, 'txn_type' => 'debit',
                            'particularNote' => e($p->particulars), 'reference_no' => $p->bill_voucher_number, 'notes' => e($p->notes),
                            // a cash payment has no cheque date: show its payment date instead of 01/01/1970
                            'cheque_no' => e($p->cheque_reference_number),
                            'cheque_date' => (!empty($p->cheque_date) && strtotime((string) $p->cheque_date) > 0) ? date('d/m/Y', strtotime((string) $p->cheque_date)) : $paymentDate,
                        ];
                    }
                } else {
                    // one line per bill date: the head's charges of the day (members that still exist)
                    $q = DB::table('member_bill_generates as g')->join('members as m', 'm.id', '=', 'g.member_id')
                        ->where('g.society_id', $this->societyId)->where('g.ledger_head_id', $id);
                    $dates($q, 'g.bill_generated_date');
                    $q->groupBy('g.bill_generated_date')->select('g.bill_generated_date', DB::raw('CAST(SUM(g.amount) AS CHAR) as amount'));
                    foreach ($q->get() as $b) {
                        $entries[] = [
                            'payment_date' => date('d/m/Y', strtotime($b->bill_generated_date)), 'amount' => $b->amount, 'txn_type' => $txnType,
                            'particularNote' => '', 'reference_no' => null, 'cheque_no' => '', 'cheque_date' => '', 'notes' => '',
                            'flat_no' => date('F', strtotime($b->bill_generated_date)),
                        ];
                    }
                }

                $q = DB::table('society_other_incomes as o')->select(TextSelect::columns('society_other_incomes', 'o'))
                    ->where('o.society_id', $this->societyId)->where('o.ledger_head_id', $id);
                $dates($q, 'o.payment_date');
                foreach ($q->orderBy('o.payment_date')->get() as $o) {
                    $entries[] = [
                        'payment_date' => date('d/m/Y', strtotime($o->payment_date)), 'formatted_date' => date('Y-m-d', strtotime($o->payment_date)),
                        'amount' => $o->amount_paid, 'txn_type' => 'credit', 'particularNote' => e($o->title), 'reference_no' => $o->general_receipt_number,
                        'cheque_no' => e($o->cheque_no), 'cheque_date' => date('d/m/Y', strtotime($o->payment_date)),
                    ];
                }

                // journal vouchers of this year that touch the head
                $jvs = DB::table('journal_vouchers')->select(TextSelect::columns('journal_vouchers'))
                    ->where('financial_year_id', $this->financialYearId)
                    ->where(function ($w) use ($id) {
                        $w->where('jv_debit_ledger_head_id', $id)->orWhere('jv_credit_ledger_head_id', $id);
                    })->orderBy('voucher_date')->get();
                foreach ($jvs as $jv) {
                    if ($both && !($jv->voucher_date >= $from && $jv->voucher_date <= $to)) {
                        continue;
                    }
                    $type = '';
                    $amt = 0;
                    $particular = '';
                    if ($jv->jv_debit_ledger_head_id != '' && $jv->jv_debit_ledger_head_id != '0') {
                        $type = 'debit';
                        $amt = $jv->jv_amount_debited;
                        $particular = 'JV Debited. V.No ' . $jv->voucher_no . ' ' . e($jv->note);
                    }
                    if ($jv->jv_credit_ledger_head_id != '' && $jv->jv_credit_ledger_head_id != '0') {
                        $type = 'credit';
                        $amt = $jv->jv_amount_credited;
                        $particular = 'JV Credited. V.No ' . $jv->voucher_no . ' ' . e($jv->note);
                    }
                    $entries[] = [
                        'particularNote' => $particular, 'payment_date' => isset($jv->voucher_date) ? date('d/m/Y', strtotime($jv->voucher_date)) : '',
                        'formatted_date' => isset($jv->voucher_date) ? date('Y-m-d', strtotime($jv->voucher_date)) : '', 'amount' => $amt, 'txn_type' => $type,
                    ];
                }

                uasort($entries, function ($a, $b) {
                    $utc = new \DateTimeZone('UTC');

                    return \DateTime::createFromFormat('d/m/Y', $a['payment_date'], $utc)->getTimestamp() - \DateTime::createFromFormat('d/m/Y', $b['payment_date'], $utc)->getTimestamp();
                });
                $details['ledgerPaymentData'] = $entries;
                $details['txn_type'] = $txnType;
            }
            $out[$headId] = ['data' => $details, 'title' => $details['SocietyLedgerHeads']['title'] ?? null];
        }

        return $out;
    }

    /** SocietyBill::getSocietyLedgerOpClosingBal(...)['balance_amount']: last year's closing of the head (latest non-NULL row) */
    private function openingBalance($headId)
    {
        return DB::table('society_ledger_heads_opening_year_wise')
            ->where('ledger_head_id', $headId)->where('society_id', $this->societyId)->where('financial_year_id', ($this->financialYearId ?? 0) - 1)
            ->whereNotNull('balance_amount')->orderByDesc('id')
            ->select(DB::raw('CAST(balance_amount AS CHAR) as balance_amount'))->value('balance_amount');
    }
}
