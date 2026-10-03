<?php

namespace App\Services\Reports;

use App\Support\ReportUtil;
use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;

/**
 * The bills behind the "Bill Print" buttons of Report - Accounts (CakePHP account_reports/bill_half_page,
 * bill_full_page, bill_tax_invoice_gst, bill_with_interest_gst): the member bill summaries a filter picks
 * (with the society and member joined on, as CakePHP's belongsTo does), and the tariff / receipt lines of each.
 *
 * Every amount is read as text (CAST .. AS CHAR), the way CakePHP's PDO hands it over, so the figures and the
 * way they print are the ones the CakePHP bill shows.
 */
class BillPrintReport
{
    public function __construct(private int $societyId, private ?int $financialYearId = null)
    {
    }

    /** PHP empty(): '', '0', 0, null, false and [] */
    public static function phpEmpty($v): bool
    {
        return $v === null || $v === '' || $v === '0' || $v === 0 || $v === false || $v === [];
    }

    /** SocietyTariffOrder joined to its ledger head, in tariff order: [ledger head id => title] (the society's tariff heads) */
    public function tariffTitles(): array
    {
        $out = [];
        $rows = DB::table('society_tariff_orders as o')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'o.ledger_head_id')
            ->where('h.society_id', $this->societyId)
            ->orderBy('o.tariff_serial')
            ->get(['h.id', 'h.title']);
        foreach ($rows as $r) {
            $out[$r->id] = $r->title;
        }

        return $out;
    }

    /** Wing::find('list'): [wing id => wing name] of the society */
    public function wingList(): array
    {
        return DB::table('wings')->where('society_id', $this->societyId)->orderBy('id')->pluck('wing_name', 'id')->toArray();
    }

    /** The society's parameter row (first one), text values; [] when it has none. */
    public function societyParameters(): array
    {
        $row = DB::table('society_parameters')->select(TextSelect::columns('society_parameters'))->where('society_id', $this->societyId)->first();

        return $row ? (array) $row : [];
    }

    /**
     * MemberBillSummary::find('all') for the filter as the bill screens read it (data[MemberBillSummary][..]):
     * each row is ['MemberBillSummary' => .., 'Society' => .., 'Member' => ..], ordered by bill no.
     *
     * @param  bool  $ofThisYear  the tax-invoice screen alone limits the bills to the financial year in use
     */
    public function summaries(array $in, bool $ofThisYear = false): array
    {
        $q = DB::table('member_bill_summaries as b')
            ->leftJoin('societies as s', 's.id', '=', 'b.society_id')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select(array_merge(
                TextSelect::columns('member_bill_summaries', 'b'),
                TextSelect::columns('societies', 's', 's__'),
                TextSelect::columns('members', 'm', 'm__')
            ))
            ->where('b.society_id', $this->societyId);

        $month = $this->val($in, 'month');
        $monthTo = $this->val($in, 'month_to');
        if (!self::phpEmpty($month) && !self::phpEmpty($monthTo)) {
            $q->whereBetween('b.month', [$month, $monthTo]);
        } elseif (!self::phpEmpty($month)) {
            $q->where('b.month', $month);
        } elseif (!self::phpEmpty($monthTo)) {
            $q->where('b.month', $monthTo);
        }

        $date = $this->val($in, 'bill_date');
        $dateTo = $this->val($in, 'bill_date_to');
        if (!self::phpEmpty($date) && !self::phpEmpty($dateTo)) {
            $q->whereBetween('b.bill_generated_date', [$date, $dateTo]);
        } elseif (!self::phpEmpty($date)) {
            $q->where('b.bill_generated_date', $date);
        } elseif (!self::phpEmpty($dateTo)) {
            $q->where('b.bill_generated_date', $dateTo);
        }

        $flat = $this->val($in, 'flat_no');
        $flatTo = $this->val($in, 'flat_no_to');
        if (!self::phpEmpty($flat) && !self::phpEmpty($flatTo)) {
            $q->whereBetween('b.flat_no', [$flat, $flatTo]);
        } elseif (!self::phpEmpty($flatTo)) {
            $q->where('b.flat_no', $flatTo);
        } elseif (!self::phpEmpty($flat)) {
            $q->where('b.flat_no', $flat);
        }

        $billNo = $this->val($in, 'bill_no');
        $billNoTo = $this->val($in, 'bill_no_to');
        if (!self::phpEmpty($billNo) && !self::phpEmpty($billNoTo)) {
            $q->whereBetween('b.bill_no', [$billNo, $billNoTo]);
        } elseif (!self::phpEmpty($billNo)) {
            $q->where('b.bill_no', $billNo);
        } elseif (array_key_exists('bill_date_to', $in) && $in['bill_date_to'] !== null && !self::phpEmpty($billNoTo)) {
            // CakePHP reads the bill-no "To" box as the bill DATE "To" value here
            $q->where('b.bill_no', $in['bill_date_to']);
        }

        if ($ofThisYear) {
            $q->where('b.financial_year_id', $this->financialYearId);
        }

        $rows = [];
        foreach ($q->orderBy('b.bill_no')->get() as $r) {
            $row = ['MemberBillSummary' => [], 'Society' => [], 'Member' => []];
            foreach ((array) $r as $col => $v) {
                if (str_starts_with($col, 's__')) {
                    $row['Society'][substr($col, 3)] = $v;
                } elseif (str_starts_with($col, 'm__')) {
                    $row['Member'][substr($col, 3)] = $v;
                } else {
                    $row['MemberBillSummary'][$col] = $v;
                }
            }
            // headings show plain text, same as the other report views
            foreach (['society_name', 'registration_no', 'address'] as $k) {
                if (isset($row['Society'][$k])) {
                    $row['Society'][$k] = ReportUtil::plain($row['Society'][$k]);
                }
            }
            $row['MemberBillSummary']['monthName'] = $this->monthName($row['MemberBillSummary']['month']);
            $rows[] = $row;
        }

        return $rows;
    }

    private ?array $frequency = null;

    /** SocietyBill::monthWordFormatByBillingFrequency */
    public function monthName($monthNo)
    {
        if ($this->frequency === null) {
            $p = DB::table('society_parameters')->where('society_id', $this->societyId)->orderBy('id')->first(['billing_frequency_id']);
            $this->frequency = $p ? ReportUtil::billingFrequency($p->billing_frequency_id) : [];
        }

        return $this->frequency[$monthNo] ?? false;
    }

    /**
     * The tariff lines of a bill (MemberBillGenerate joined to its ledger head, amount not 0): [n => [ledger_head_id, amount, title, is_tax_applicable]].
     * n keeps counting the way CakePHP's find() numbers the rows.
     */
    public function tariffLines(array $summary, bool $ofThisYear = false): array
    {
        $q = DB::table('member_bill_generates as g')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'g.ledger_head_id')
            ->select('g.ledger_head_id', DB::raw('CAST(g.amount AS CHAR) as amount'), 'h.title', 'h.is_tax_applicable');
        if ($ofThisYear) {
            $q->where('g.financial_year_id', $this->financialYearId);
        }
        $q->where('g.society_id', $this->societyId)
            ->where('g.month', $summary['month'])
            ->where('g.member_id', $summary['member_id'])
            ->where('g.amount', '!=', 0);

        return $q->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * member_bill_settlements: [payment id => [bill_no, bill_month]]. Read the way CakePHP reads it - every settlement of the
     * society, all four joined tables' columns - because the row that comes LAST for a payment settled on several bills is the
     * one CakePHP prints, and that order is the one this query shape gives.
     */
    public function receiptBillInfo(array $paymentIds): array
    {
        if (empty($paymentIds)) {
            return [];
        }
        $wanted = array_flip($paymentIds);
        $sql = 'SELECT t.*, m.*, b.*, p.* FROM member_bill_settlements AS t'
            . ' LEFT JOIN members AS m ON (t.member_id = m.id)'
            . ' LEFT JOIN member_bill_summaries AS b ON (t.bill_summary_id = b.id)'
            . ' LEFT JOIN member_payments AS p ON (t.payment_id = p.id)'
            . ' WHERE b.society_id = ' . (int) $this->societyId;
        $stmt = DB::connection()->getPdo()->query($sql, \PDO::FETCH_NUM);
        $out = [];
        // t.* comes first: id 0, member_id 1, payment_id 2, bill_summary_id 3, bill_no 4, bill_type 5, bill_month 6
        while (($r = $stmt->fetch()) !== false) {
            if (isset($wanted[$r[2]])) {
                $out[$r[2]] = ['bill_no' => $r[4], 'bill_month' => $r[6]];
            }
        }

        return $out;
    }

    /** MemberPayment rows of a member, newest first, as ['MemberPayment' => [text columns]] */
    public function payments(array $where, ?array $between = null, $memberId = null): array
    {
        $q = DB::table('member_payments as p')->select(TextSelect::columns('member_payments', 'p'));
        foreach ($where as $col => $v) {
            $q->where('p.' . $col, $v);
        }
        if ($between) {
            $q->where('p.payment_date', '>=', $between[0])->where('p.payment_date', '<=', $between[1]);
        }

        return $q->orderByDesc('p.id')->get()->map(fn ($r) => ['MemberPayment' => (array) $r])->all();
    }

    /** Half-page / full-page bill: the bills of the filter, each with its 'MemberTariff' lines (and the member's wing name when $wingNames is given). */
    public function bills(array $in, ?array $wingNames = null): array
    {
        $rows = $this->summaries($in);
        foreach ($rows as $i => $row) {
            if ($wingNames !== null) {
                $rows[$i]['Member']['wing_name'] = $wingNames[$row['Member']['wing_id']] ?? '';
            }
            foreach ($this->tariffLines($row['MemberBillSummary']) as $k => $t) {
                $rows[$i]['MemberTariff'][$k] = $this->tariffRow($t);
            }
        }

        return $rows;
    }

    /**
     * Tax-invoice bill (GST): the bills of the year in use, each with its tax-applicable lines under 'taxGst' and the others
     * under 'noTaxGst', and - when a receipt period is given - the receipts of the period under 'receipts'.
     */
    public function taxInvoiceBills(array $in): array
    {
        $rows = $this->summaries($in, true);
        $from = $this->val($in, 'from_date');
        $to = $this->val($in, 'to_date');
        foreach ($rows as $i => $row) {
            foreach ($this->tariffLines($row['MemberBillSummary'], true) as $k => $t) {
                $bucket = (isset($t['is_tax_applicable']) && $t['is_tax_applicable'] == 1) ? 'taxGst' : 'noTaxGst';
                $rows[$i][$bucket]['MemberTariff'][$k] = $this->tariffRow($t);
            }
            if (!self::phpEmpty($from) && !self::phpEmpty($to)) {
                // CakePHP compares the two dates as d-m-Y TEXT: a period starting before "01-04-<this calendar year>" is read from last year's payments
                $beforeApril = date('d-m-Y', strtotime($from)) < date('d-m-Y', strtotime('01-04-' . date('Y')));
                $rows[$i]['receipts'] = $this->payments([
                    'financial_year_id' => $beforeApril ? $this->financialYearId - 1 : $this->financialYearId,
                    'member_id' => $row['MemberBillSummary']['member_id'],
                    'bill_type' => 'reg',
                ], [$from, $to]);
            }
        }

        return $rows;
    }

    /** Bill with interest & GST: the bills of the filter, each with its tariff lines, the taxable total and the member's receipts. */
    public function interestGstBills(array $in): array
    {
        $rows = $this->summaries($in);
        $from = $this->val($in, 'from_date');
        $to = $this->val($in, 'to_date');
        foreach ($rows as $i => $row) {
            $memberId = $row['MemberBillSummary']['member_id'];
            if ($row['MemberBillSummary']['month'] == 4) {
                // CakePHP asks last year's March receipts from a controller that has no financial year set: nothing comes back
                $receipts = [];
            } elseif (!self::phpEmpty($from) && !self::phpEmpty($to)) {
                $receipts = $this->payments(['member_id' => $memberId], [$from, $to]);
            } else {
                $receipts = $this->payments(['member_id' => $memberId]);
            }
            if (count($receipts) > 0) {
                $rows[$i]['receipts'] = $receipts;
            }

            $rows[$i]['MemberBillSummary']['total_taxable_amt'] = 0;
            foreach ($this->tariffLines($row['MemberBillSummary']) as $k => $t) {
                if ($t['is_tax_applicable'] == 1) {
                    $rows[$i]['MemberBillSummary']['total_taxable_amt'] = $rows[$i]['MemberBillSummary']['total_taxable_amt'] + $t['amount'];
                }
                $rows[$i]['MemberTariff'][$k] = $this->tariffRow($t);
            }
        }

        return $rows;
    }

    private function tariffRow(array $t): array
    {
        return [
            'ledger_head_id' => $t['ledger_head_id'] ?? '',
            'amount' => $t['amount'] ?? '',
            'title' => $t['title'] ?? '',
        ];
    }

    private function val(array $in, string $key)
    {
        return $in[$key] ?? null;
    }
}
