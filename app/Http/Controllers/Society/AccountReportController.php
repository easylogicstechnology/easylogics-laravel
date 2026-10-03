<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Society;
use App\Models\SocietyHeadSubCategory;
use App\Models\SocietyLedgerHead;
use App\Support\CakeUtil;
use App\Support\LegacySession;
use App\Services\Reports\BankBookReport;
use App\Services\Reports\CashBookReport;
use App\Services\Reports\DuesFromMembersReport;
use App\Services\Reports\JournalVoucherRegisterReport;
use App\Services\Reports\PaymentRegisterReport;
use App\Services\Reports\BankSlipReport;
use App\Services\Reports\CollectionSheetReport;
use App\Services\Reports\MemberCollectionRegisterReport;
use App\Services\Reports\MemberMonthlyContributionReport;
use App\Services\Reports\GstRegisterReport;
use App\Services\Reports\BillRegisterReport;
use App\Services\Reports\MemberLedgerDefaultReport;
use App\Services\Reports\TrialBalanceReport;
use App\Services\Reports\TrialBalanceDifferenceReport;
use App\Services\Reports\GeneralLedgerReport;
use App\Services\Reports\IncomeExpenditureReport;
use App\Services\Reports\BalanceSheetReport;
use App\Services\Reports\ReceiptPaymentReport;
use App\Services\Reports\OpeningBalanceReport;
use App\Services\Reports\BillSummaryUpdateReport;
use App\Services\Reports\MemberChartReport;
use App\Services\Reports\AuditReportService;
use App\Services\Reports\BillPrintReport;
use App\Services\Reports\BillSummaryPrevDataReport;
use App\Services\Reports\BillHalfPageExcel;
use App\Services\Reports\MemberBillPrintReport;
use App\Support\ReportUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The reports behind the buttons of "Report - Accounts" (CakePHP AccountReportsController).
 * One method per report; the report maths lives in App\Services\Reports so it can be checked
 * against the CakePHP figures on its own.
 */
class AccountReportController extends Controller
{
    private function societyId(): int
    {
        return (int) Auth::id();
    }

    /** Society's active bank ledger heads (SocietyBill::getSocietyBankNameList) as [id => title]. */
    private function bankLists(int $societyId): array
    {
        $subCategory = SocietyHeadSubCategory::where('title', 'LIKE', '%Bank Balances%')
            ->where('status', 1)
            ->orderBy('id')
            ->first();
        if (!$subCategory) {
            return [];
        }

        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('society_head_sub_category_id', $subCategory->id)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();
    }

    /** Society's active cash ledger heads (SocietyBill::societyCashBalanceHeadsLists) as [id => title]. */
    private function cashLists(int $societyId): array
    {
        $subCategory = SocietyHeadSubCategory::where('title', 'LIKE', '%Cash Balance%')
            ->where('status', 1)
            ->orderBy('id')
            ->first();
        if (!$subCategory) {
            return [];
        }

        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('society_head_sub_category_id', $subCategory->id)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();
    }

    private function society(): ?Society
    {
        return Society::where('user_id', $this->societyId())->first();
    }

    /** CakePHP: account_reports/account_petty_cash (a form only, nothing behind it) */
    public function pettyCash(Request $request)
    {
        return view('society.reports.petty-cash', ['title' => 'Petty Cash Register']);
    }

    /** CakePHP: account_reports/account_member_list (the CakePHP screen only lists name, unit, phone, area) */
    public function memberList(Request $request)
    {
        $members = DB::table('members')
            ->select('member_name', 'flat_no', 'member_phone', DB::raw('CAST(area AS CHAR) as area_text'))
            ->where('society_id', $this->societyId())
            ->orderBy('id')
            ->get();

        return view('society.reports.member-list', [
            'title' => 'Member List',
            'society' => $this->society(),
            'members' => $members,
        ]);
    }

    /** CakePHP: account_reports/account_bank_reconciliation (fixed sample statement, see the view) */
    public function bankReconciliation(Request $request)
    {
        return view('society.reports.bank-reconciliation', [
            'title' => 'Bank Reconciliation',
            'society' => $this->society(),
            'banks' => $this->bankLists($this->societyId()),
            'input' => $request->isMethod('post') ? $request->only(['report_type', 'society_bank_id']) : [],
        ]);
    }

    /** CakePHP: account_reports/account_payment_register */
    public function paymentRegister(Request $request)
    {
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['payment_date', 'payment_date_to', 'payment_type', 'transaction_type']) : [];
        $rows = $request->isMethod('post') ? (new PaymentRegisterReport($societyId))->run($input) : [];

        return view('society.reports.payment-register', [
            'title' => 'Payment Register',
            'input' => $input,
            'society' => $this->society(),
            'banks' => $this->bankLists($societyId),
            'rows' => $rows,
        ]);
    }

    private function fyId(): ?int
    {
        $id = session('fy.year_id');

        return $id === null ? null : (int) $id;
    }

    /** Society's buildings as [id => name] (SocietyBill::getSocietyBuildingListsById). */
    private function buildingLists(int $societyId): array
    {
        return DB::table('buildings')->where('society_id', $societyId)->orderBy('id')->pluck('building_name', 'id')->toArray();
    }

    /** CakePHP: account_reports/account_dues_from_member (incl. the "Reminder Letter" type) */
    public function duesFromMembers(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['payment_date', 'report_type', 'operator', 'amount', 'member_record', 'clear_dues_by_date', 'building_id', 'wing_id', 'flat_no', 'flat_no_to', 'letter_action']) : [];
        $society = $this->society();
        $report = new DuesFromMembersReport($societyId, $this->fyId());
        $summary = $post ? $report->dues($input) : [];

        if ($post && ($input['report_type'] ?? '') === 'Reminder Letter') {
            return $this->reminderLetters($summary, $society, $input);
        }

        return view('society.reports.dues-from-members', [
            'title' => 'Dues From Member',
            'input' => $input,
            'society' => $society,
            'buildings' => $this->buildingLists($societyId),
            'summary' => $summary,
        ]);
    }

    /** CakePHP: account_reports/account_dues_advance_from_member */
    public function duesAdvanceFromMembers(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['payment_date', 'report_type', 'operator', 'amount', 'building_id', 'wing_id', 'flat_no', 'flat_no_to']) : [];

        return view('society.reports.dues-advance-from-members', [
            'title' => 'Dues-Advance From Members',
            'input' => $input,
            'society' => $this->society(),
            'buildings' => $this->buildingLists($societyId),
            'summary' => $post ? (new DuesFromMembersReport($societyId, $this->fyId()))->advances($input) : [],
        ]);
    }

    /** CakePHP: handleReminderLetters() - preview, PDF (view / download) and e-mail of the reminder letters. */
    private function reminderLetters(array $summary, ?Society $society, array $input)
    {
        $societyId = $this->societyId();
        $asOnDate = !empty($input['payment_date']) ? $input['payment_date'] : date('Y-m-d');
        $clearDuesByDate = !empty($input['clear_dues_by_date']) ? $input['clear_dues_by_date'] : '';
        $action = $input['letter_action'] ?? '';

        $buildings = $this->buildingLists($societyId);
        $wings = DB::table('wings')->where('society_id', $societyId)->pluck('wing_name', 'id')->toArray();
        $letters = [];
        foreach ($summary as $row) {
            $due = $row['total_dues_amount'] ?? 0;
            if ($due <= 0) {
                continue;
            }
            $member = DB::table('members')->select('member_email', 'member_phone')->where('id', $row['member_id'])->first();
            $letters[] = [
                'member_id' => $row['member_id'],
                'member_name' => ($row['member_prefix'] ?? '') . ($row['member_name'] ?? ''),
                'flat_no' => $row['flat_no'] ?? '',
                'building_name' => $buildings[$row['building_id']] ?? '',
                'wing_name' => $wings[$row['wing_id']] ?? '',
                'due_amount' => $due,
                'email' => isset($member->member_email) ? trim($member->member_email) : '',
                'phone' => isset($member->member_phone) ? trim($member->member_phone) : '',
            ];
        }

        $data = ['reminderLetters' => $letters, 'society' => $society, 'asOnDate' => $asOnDate, 'clearDuesByDate' => $clearDuesByDate, 'input' => $input];

        if ($action === 'download_pdf' || $action === 'view_pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('society.reports.reminder-letters-pdf', $data);
            $name = 'Reminder_Letters_' . date('Ymd_His') . '.pdf';

            return $action === 'download_pdf' ? $pdf->download($name) : $pdf->stream($name);
        }
        if ($action === 'email_all') {
            $data['flash'] = $this->emailReminderLetters($letters, $data);
        }

        return view('society.reports.reminder-letters', $data);
    }

    /** Each member gets their own one-page letter (PDF attached, same html as body); no e-mail on file = skipped. */
    private function emailReminderLetters(array $letters, array $data): string
    {
        $sent = 0;
        $skipped = 0;
        foreach ($letters as $letter) {
            if (empty($letter['email'])) {
                $skipped++;
                continue;
            }
            $one = array_merge($data, ['reminderLetters' => [$letter]]);
            $html = view('society.reports.reminder-letters-pdf', $one)->render();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
            $safeFlat = str_replace([' ', '/'], ['_', '-'], $letter['flat_no']);
            $this->callEmailApi(
                [$letter['email']],
                'Reminder - Outstanding Dues - Unit ' . $letter['flat_no'],
                $html,
                [['name' => 'Reminder_Letter_' . $safeFlat . '.pdf', 'type' => 'application/pdf', 'data' => base64_encode($pdf)]]
            );
            $sent++;
        }
        if ($sent > 0) {
            return 'Reminder letters emailed to ' . $sent . ' member(s).' . ($skipped > 0 ? ' ' . $skipped . ' member(s) skipped - no email on file.' : '');
        }

        return 'No reminder letters were emailed - none of the ' . count($letters) . ' member(s) with dues have an email on file.';
    }

    /** Same send-email endpoint the CakePHP bill e-mailing uses. */
    private function callEmailApi(array $recipients, string $subject, string $htmlBody, array $attachments = []): array
    {
        if (empty($recipients) || $subject === '' || $htmlBody === '') {
            return ['success' => false, 'error' => 'Missing required email fields'];
        }
        $payload = ['recipients' => $recipients, 'subject' => $subject, 'htmlBody' => $htmlBody];
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->post('https://api.easylogicstechnology.com/api/send-email', $payload);

            return ['success' => $res->successful(), 'httpCode' => $res->status(), 'response' => $res->json()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'cURL error: ' . $e->getMessage()];
        }
    }

    /** CakePHP: account_reports/account_journal_voucher_register (lists every voucher even before submitting) */
    public function journalVoucherRegister(Request $request)
    {
        $input = $request->isMethod('post') ? $request->only(['voucher_no']) : [];
        $report = (new JournalVoucherRegisterReport($this->societyId()))->run($input);

        return view('society.reports.journal-voucher-register', [
            'title' => 'Journal Voucher Register',
            'input' => $input,
            'society' => $this->society(),
            'data' => $report['data'],
            'members' => $report['members'],
            'ledgers' => $report['ledgers'],
        ]);
    }


    /** CakePHP: account_reports/account_bank_slip */
    public function bankSlip(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['society_bank_id', 'payment_date', 'payment_date_to', 'slip_no', 'slip_no_to']) : [];
        $report = $post ? (new BankSlipReport($societyId))->run($input) : ['rows' => [], 'bank' => null];

        return view('society.reports.bank-slip', [
            'title' => 'Bank Slip',
            'input' => $input,
            'society' => $this->society(),
            'banks' => $this->bankLists($societyId),
            'rows' => $report['rows'],
            'bankRow' => $report['bank'],
        ]);
    }

    /** CakePHP: account_reports/account_collection_sheet (lists the society's bills even before submitting) */
    public function collectionSheet(Request $request)
    {
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['payment_date', 'payment_date_to', 'building_id', 'wing_id', 'flat_no', 'flat_no_to']) : [];
        $report = (new CollectionSheetReport($societyId))->run($input);

        return view('society.reports.collection-sheet', [
            'title' => 'Collection Sheet',
            'input' => $input,
            'society' => $this->society(),
            'buildings' => $this->buildingLists($societyId),
            'rows' => $report['rows'],
            'showGst' => $report['showGst'],
        ]);
    }

    /** CakePHP: account_reports/member_collection_register */
    public function memberCollectionRegister(Request $request)
    {
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['payment_date', 'payment_date_to', 'building_id', 'wing_id', 'flat_no', 'flat_no_to']) : [];

        return view('society.reports.member-collection-register', [
            'title' => 'Member Collection Register',
            'input' => $input,
            'society' => $this->society(),
            'buildings' => $this->buildingLists($societyId),
            'data' => (new MemberCollectionRegisterReport($societyId))->run($input),
            'bankHeads' => $this->bankLists($societyId),
            'cashHeads' => $this->cashLists($societyId),
        ]);
    }

    /** CakePHP: account_reports/account_member_monthly_contribution */
    public function memberMonthlyContribution(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['building_id', 'wing_id', 'member_id', 'flat_no']) : [];
        $report = $post ? (new MemberMonthlyContributionReport($societyId))->run($input) : ['contribution' => [], 'tax' => [], 'heads' => []];

        return view('society.reports.member-monthly-contribution', [
            'title' => 'Member Monthly Contribution',
            'input' => $input,
            'society' => $this->society(),
            'buildings' => $this->buildingLists($societyId),
            'members' => DB::table('members')->where('society_id', $societyId)->where('status', 1)->orderBy('id')->pluck('member_name', 'id')->all(),
            'contribution' => $report['contribution'],
            'tax' => $report['tax'],
            'heads' => $report['heads'],
        ]);
    }

    /** CakePHP: account_reports/account_gst_register (lists the year's bills even before submitting) */
    public function gstRegister(Request $request)
    {
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['building_id', 'wing_id', 'flat_no', 'flat_no_to', 'bill_generated_date', 'bill_generated_date_to']) : [];
        $report = (new GstRegisterReport($societyId, $this->fyId()))->run($input);

        return view('society.reports.gst-register', [
            'title' => 'GST Register',
            'input' => $input,
            'society' => $this->society(),
            'buildings' => $this->buildingLists($societyId),
            'data' => $report['data'],
            'members' => $report['members'],
            'tariffs' => $report['tariffs'],
            'params' => $report['params'],
        ]);
    }

    /** CakePHP: account_reports/account_bill_register */
    public function billRegister(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['from_month', 'to_month', 'bill_type', 'member_record', 'member_record_range', 'building_id', 'wing_id', 'flat_no', 'flat_no_to', 'bill_generated_date', 'bill_generated_date_to']) : [];
        $report = new BillRegisterReport($societyId, $this->fyId());
        $result = $post ? $report->run($input) : ['data' => [], 'members' => [], 'tariffs' => []];

        return view('society.reports.bill-register', [
            'title' => 'Bill Register',
            'input' => $input,
            'society' => $post ? $this->society() : null,
            'buildings' => $this->buildingLists($societyId),
            'billMonths' => $report->billMonths(),
            'labelOf' => fn ($month) => $report->monthLabel($month),
            'data' => $result['data'],
            'members' => $result['members'],
            'tariffs' => $result['tariffs'],
        ]);
    }

    /**
     * Renders a view that was carried over from CakePHP almost line by line. Those views lean on PHP 7
     * leniency (reading array keys that are not there prints nothing), so notices / warnings are ignored
     * while the view renders instead of being turned into exceptions.
     */
    private function legacyView(string $view, array $data)
    {
        $data += ['utilObj' => new CakeUtil(), 'societyObj' => new CakeUtil(), 'session' => new LegacySession()];
        set_error_handler(fn () => true);
        try {
            $html = view($view, $data)->render();
        } finally {
            restore_error_handler();
        }

        return response($html);
    }

    /** The society as the carried-over CakePHP views read it ($societyDetails['Society'][...]); markup in the text is dropped. */
    private function societyDetails(): array
    {
        $society = $this->society();
        $row = $society ? $society->toArray() : [];
        foreach (['society_name', 'registration_no', 'address'] as $k) {
            if (isset($row[$k])) {
                $row[$k] = \App\Support\ReportUtil::plain($row[$k]);
            }
        }

        return ['Society' => $row];
    }

    /** CakePHP: account_reports/account_member_ledger_default */
    public function memberLedgerDefault(Request $request)
    {
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['report_type', 'payment_date', 'payment_date_to', 'member_record', 'member_record_range', 'building_id', 'wing_id', 'flat_no', 'flat_no_to']) : [];
        $report = ['ledger' => [], 'memberArr' => []];
        if ($post) {
            @set_time_limit(2400);
            $report = (new MemberLedgerDefaultReport($societyId, $this->fyId()))->run($input);
        }

        return $this->legacyView('society.reports.member-ledger-default', [
            'title' => 'Member Ledger',
            'postData' => $post ? ['MemberBillSummary' => $input] : [],
            'societyDetails' => $this->societyDetails(),
            'buildings' => $this->buildingLists($societyId),
            'accountMemberLedgerDetails' => $report['ledger'],
            'memberArr' => $report['memberArr'],
        ]);
    }

    /** CakePHP: account_reports/account_trial_balance (also runs on a plain page open, for the whole year) */
    public function trialBalance(Request $request)
    {
        @set_time_limit(2700);
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['payment_date', 'payment_date_to']) : [];
        $report = (new TrialBalanceReport($societyId, $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date')))->run($input);
        $session = new LegacySession();

        return view('society.reports.trial-balance', [
            'title' => 'Trial Balance',
            'post' => $report['post'],
            'heads' => $report['heads'],
            'subCats' => $report['subCats'],
            'bankIds' => $report['bankIds'],
            'cashIds' => $report['cashIds'],
            'societyId' => $societyId,
            'startYear' => $session->read('Auth.start_year'),
            'endYear' => $session->read('Auth.end_year'),
        ]);
    }

    /** Debit / credit transaction totals of a Trial Balance run (the "Transaction" columns added up). */
    private function trialBalanceTotals(array $heads): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($heads as $group) {
            if (empty($group['ledgers'])) {
                continue;
            }
            foreach ($group['ledgers'] as $ledger) {
                $debit += (float) $ledger['transactions']['debit'];
                $credit += (float) $ledger['transactions']['credit'];
            }
        }

        return [$debit, $credit];
    }

    private function trialBalanceRun(array $post): array
    {
        return (new TrialBalanceReport($this->societyId(), $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date')))->run($post);
    }

    /** CakePHP: account_reports/trial_balance_diff (Debit vs Credit of the Trial Balance, month / day drill-down) */
    public function trialBalanceDiff(Request $request)
    {
        @set_time_limit(2700);
        $report = $this->trialBalanceRun([]);
        [$debit, $credit] = $this->trialBalanceTotals($report['heads']);
        $diff = round($debit - $credit, 2);
        $post = $report['post'];

        $dateRows = [];
        $granularity = 'month';
        $drillMonth = $request->query('month');
        $fyStart = !empty($post['payment_date']) ? $post['payment_date'] : null;
        $fyEnd = !empty($post['payment_date_to']) ? $post['payment_date_to'] : null;
        if ($fyStart && $fyEnd && abs($diff) >= 0.01) {
            if ($drillMonth && preg_match('/^\d{4}-\d{2}$/', $drillMonth)) {
                $granularity = 'day';
                $monthStart = $drillMonth . '-01';
                $monthEnd = date('Y-m-t', strtotime($monthStart));
                if ($monthStart < $fyStart) {
                    $monthStart = $fyStart;
                }
                if ($monthEnd > $fyEnd) {
                    $monthEnd = $fyEnd;
                }
                $cur = strtotime($monthStart);
                $endTs = strtotime($monthEnd);
                while ($cur <= $endTs) {
                    $d = date('Y-m-d', $cur);
                    $dateRows[] = ['label' => date('d/m/Y', $cur), 'from' => $d, 'to' => $d, 'key' => $d];
                    $cur = strtotime('+1 day', $cur);
                }
            } else {
                $cur = strtotime(date('Y-m-01', strtotime($fyStart)));
                $endTs = strtotime($fyEnd);
                while ($cur <= $endTs) {
                    $mStart = date('Y-m-d', $cur);
                    if ($mStart < $fyStart) {
                        $mStart = $fyStart;
                    }
                    $mEndTs = min(strtotime(date('Y-m-t', $cur)), $endTs);
                    $dateRows[] = ['label' => date('M Y', $cur), 'from' => $mStart, 'to' => date('Y-m-d', $mEndTs), 'key' => date('Y-m', $cur)];
                    $cur = strtotime('+1 month', $cur);
                }
            }
        }

        return view('society.reports.trial-balance-diff', [
            'title' => 'Trial Balance Diff (Debit vs Credit)',
            'debit' => $debit, 'credit' => $credit, 'diff' => $diff, 'post' => $post,
            'heads' => $report['heads'], 'subCats' => $report['subCats'],
            'dateRows' => $dateRows, 'dateGranularity' => $granularity, 'drillMonth' => $drillMonth,
        ]);
    }

    /** CakePHP: account_reports/trial_balance_diff_range_ajax (JSON for one date range) */
    public function trialBalanceDiffRange(Request $request)
    {
        @set_time_limit(2700);
        $from = $request->query('from');
        $to = $request->query('to');
        $result = ['debit' => 0, 'credit' => 0, 'diff' => 0, 'error' => null];
        if (empty($from) || empty($to)) {
            $result['error'] = 'Missing date range';
        } else {
            try {
                $report = $this->trialBalanceRun(['payment_date' => $from, 'payment_date_to' => $to]);
                [$rd, $rc] = $this->trialBalanceTotals($report['heads']);
                $result['debit'] = $rd;
                $result['credit'] = $rc;
                $result['diff'] = round($rd - $rc, 2);
            } catch (\Throwable $e) {
                $result['error'] = $e->getMessage();
            }
        }

        return response()->json($result);
    }

    /** CakePHP: account_reports/trial_balance_day_transactions (one day's payments, receipts and vouchers) */
    public function trialBalanceDay(Request $request)
    {
        $societyId = $this->societyId();
        $date = $request->query('date');
        if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404, 'Invalid date.');
        }

        return view('society.reports.trial-balance-day', [
            'date' => $date,
            'ledgerHeadTitles' => DB::table('society_ledger_heads')->where('society_id', $societyId)->pluck('title', 'id')->all(),
            'societyPayments' => DB::table('society_payments as p')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'p.ledger_head_id')
                ->where('p.society_id', $societyId)->where('p.payment_date', $date)->orderByDesc('p.id')->select('p.*', 'h.title as ledger_title')->get(),
            'societyOtherIncomes' => DB::table('society_other_incomes as o')->leftJoin('society_ledger_heads as h', 'h.id', '=', 'o.ledger_head_id')
                ->where('o.society_id', $societyId)->where('o.payment_date', $date)->orderByDesc('o.id')->select('o.*', 'h.title as ledger_title')->get(),
            'journalVouchers' => DB::table('journal_vouchers')->where('society_id', $societyId)->where('voucher_date', $date)->orderByDesc('id')->get(),
        ]);
    }

    /** CakePHP: account_reports/account_trial_balance_difference - Member Bill Difference, or (?t=1) Bank/Cash Difference */
    public function trialBalanceDifference(Request $request)
    {
        @set_time_limit(2700);
        $societyId = $this->societyId();
        $report = new TrialBalanceDifferenceReport($societyId, $this->fyId());
        $session = new LegacySession();

        if ($request->query('t') == '1') {
            $bookData = $report->bankCash($this->bankLists($societyId), $this->cashLists($societyId));
            set_error_handler(fn () => true);
            try {
                $html = view('society.reports.trial-balance-difference', [
                    'title' => 'Bank/Cash Difference', 'mode' => 'bank', 'bookData' => $bookData,
                    'utilObj' => new CakeUtil(), 'session' => $session, 'societyDetails' => [],
                ])->render();
            } finally {
                restore_error_handler();
            }

            return response($html);
        }

        $post = $request->isMethod('post') ? $request->only(['from_date', 'to_date', 'member_id', 'diff_filter']) : [];
        $data = $report->memberBills($post);

        return view('society.reports.trial-balance-difference', [
            'title' => 'Member Bill Difference', 'mode' => 'member', 'post' => $post, 'diffFilter' => $post['diff_filter'] ?? '',
            'members' => $data['members'], 'bills' => $data['bills'], 'memberList' => $data['memberList'],
        ]);
    }

    /**
     * CakePHP: society_bills/bulk_recalculate_member_bills - the "Update Selected" button of the Member Bill
     * Difference page. Each "member:year:type" first gets its bill summaries' monthly_amount / tax_total set to
     * the sum of that month's charge lines, then the member's bills are recalculated.
     */
    public function bulkRecalculate(Request $request)
    {
        @set_time_limit(120);
        $response = ['error' => 1, 'message' => 'No rows to update'];
        $rows = (string) $request->input('rows', '');
        if ($rows === '') {
            return response()->json($response);
        }

        $societyId = $this->societyId();
        $service = app(\App\Services\BillSettlementService::class);
        $done = 0;
        $failed = 0;
        $seen = [];
        foreach (explode(',', $rows) as $tuple) {
            $tuple = trim($tuple);
            if ($tuple === '' || isset($seen[$tuple])) {
                continue;
            }
            $seen[$tuple] = true;
            $parts = explode(':', $tuple);
            if (count($parts) < 3) {
                continue;
            }
            $memberId = (int) $parts[0];
            $yearId = (int) $parts[1];
            $billType = ($parts[2] === 'sup') ? 'sup' : 'reg';
            if ($memberId <= 0 || $yearId <= 0) {
                continue;
            }

            $transfer = (int) (DB::table('members')->where('id', $memberId)->value('member_transfer') ?? 0);
            $this->syncSummaryAmountFromCharges($memberId, $societyId, $billType, $yearId, $transfer);
            $ok = $service->recalculateMemberBills($memberId, $societyId, $billType, $yearId, $transfer);
            $ok ? $done++ : $failed++;
        }
        $response['error'] = ($done > 0) ? 0 : 1;
        $response['message'] = $done . ' recalculated' . ($failed ? (', ' . $failed . ' failed') : '');

        return response()->json($response);
    }

    /** SocietyBillsController::syncSummaryAmountFromCharges: monthly_amount / tax_total = the month's charge lines */
    private function syncSummaryAmountFromCharges(int $memberId, int $societyId, string $billType, int $yearId, int $transfer): void
    {
        $summaries = DB::table('member_bill_summaries')
            ->where('member_id', $memberId)->where('society_id', $societyId)->where('bill_type', $billType)
            ->where('financial_year_id', $yearId)->where('member_transfer', $transfer)
            ->get(['id', 'month', 'monthly_amount', 'tax_total']);
        if ($summaries->isEmpty()) {
            return;
        }
        $totals = DB::table('member_bill_generates')
            ->where('member_id', $memberId)->where('society_id', $societyId)->where('bill_type', $billType)->where('financial_year_id', $yearId)
            ->groupBy('month')
            ->select('month', DB::raw('COALESCE(SUM(amount),0) AS contri'), DB::raw('COALESCE(SUM(tax_total),0) AS gst'))->get();
        $byMonth = [];
        foreach ($totals as $t) {
            $byMonth[(string) $t->month] = ['contri' => (float) $t->contri, 'gst' => (float) $t->gst];
        }
        foreach ($summaries as $s) {
            $month = (string) $s->month;
            if (!isset($byMonth[$month])) {
                continue;
            }
            $contri = $byMonth[$month]['contri'];
            $gst = $byMonth[$month]['gst'];
            if (abs($contri - (float) $s->monthly_amount) < 0.005 && abs($gst - (float) $s->tax_total) < 0.005) {
                continue;
            }
            DB::table('member_bill_summaries')->where('id', $s->id)->update([
                'monthly_amount' => number_format($contri, 2, '.', ''),
                'tax_total' => number_format($gst, 2, '.', ''),
            ]);
        }
    }

    /** CakePHP: account_reports/account_general_ledger */
    public function generalLedger(Request $request)
    {
        @ini_set('memory_limit', '-1');
        @set_time_limit(0);
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['report_type', 'ledger_for', 'account_name', 'payment_date', 'payment_date_to']) : [];
        $report = new GeneralLedgerReport($this->societyId(), $this->fyId());
        $heads = $report->ledgerHeads();

        return $this->legacyView('society.reports.general-ledger', [
            'title' => 'General Ledger',
            'postData' => $post ? ['SocietyPayment' => $input] : [],
            // CakePHP never passes the society to this report, so its heading lines stay empty
            'societyDetails' => [],
            'societyLedgerHeads' => $heads,
            'societyLedgerHeadsData' => $post ? $report->run($input, $heads) : [],
        ]);
    }

    /** CakePHP: account_reports/account_income_exp_statement_details */
    public function incomeExpDetails(Request $request)
    {
        @set_time_limit(2700);
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['from_date', 'to_date']) : [];
        $result = [
            'totalOpeningExpenses' => 0, 'totalOpeningIncome' => 0, 'totalIncome' => 0, 'totalExpenses' => 0,
            'expenseHeadsWithSubCat' => [], 'incomHeadsWithSubCat' => [], 'incomeIdex' => 0, 'expIndex' => 0,
        ];
        if ($post) {
            $result = (new IncomeExpenditureReport($this->societyId()))->run($input['from_date'] ?? null, $input['to_date'] ?? null);
        }

        return $this->legacyView('society.reports.income-exp-details', $result + [
            'title' => 'Income Expenditure Statement Details',
            'post' => $input,
            'postData' => $input,
            'societyDetails' => $this->societyDetails(),
        ]);
    }


    /**
     * The three links of the Balance Sheet (a head's name, "Dues From Members", "Advance From Members") opened small
     * CakePHP pop-ups. Here a head opens its General Ledger for the year, the other two open the matching report.
     */
    public function ledgerHeadDetails(Request $request, $headId)
    {
        $input = ['report_type' => 'Detail', 'ledger_for' => 'Particular Subgroup', 'account_name' => (string) $headId];
        $report = new GeneralLedgerReport($this->societyId(), $this->fyId());
        $heads = $report->ledgerHeads();

        return $this->legacyView('society.reports.general-ledger', [
            'title' => 'General Ledger',
            'postData' => ['SocietyPayment' => $input],
            'societyDetails' => [],
            'societyLedgerHeads' => $heads,
            'societyLedgerHeadsData' => $report->run($input, $heads),
        ]);
    }

    public function balanceDueFromMembers()
    {
        return redirect()->route('society.reports.duesFromMembers');
    }

    public function balanceDuesAdvance()
    {
        return redirect()->route('society.reports.duesAdvanceFromMembers');
    }

    /** CakePHP: account_reports/account_balance_sheet (the member dues part is also worked out on a plain page open) */
    public function balanceSheet(Request $request)
    {
        @set_time_limit(2700);
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['from_date', 'to_date', 'set_order', 'order_liabilities', 'order_assets']) : [];
        $report = (new BalanceSheetReport($this->societyId(), $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date')))->run($input, $post);

        return $this->legacyView('society.reports.balance-sheet', $report + [
            'title' => 'Balance Sheet',
            'postData' => $input,
            'societyDetails' => $this->societyDetails(),
            'resellerDetails' => [],
        ]);
    }

    /** CakePHP: account_reports/account_receipt_payment (also runs on a plain page open, for the whole year) */
    public function receiptPayment(Request $request)
    {
        @set_time_limit(2700);
        $societyId = $this->societyId();
        $input = $request->isMethod('post') ? $request->only(['payment_date', 'payment_date_to']) : [];
        $report = (new ReceiptPaymentReport($societyId, $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date')))
            ->run($input, $this->bankLists($societyId), $this->cashLists($societyId));

        return $this->legacyView('society.reports.receipt-payment', [
            'title' => 'Receipt & Payment',
            'postData' => ['MemberPayment' => $report['post']],
            'societyDetails' => $this->societyDetails(),
            'receipts' => $report['receipts'], 'payments' => $report['payments'], 'receiptHeads' => $report['receiptHeads'],
            'BalanceData' => $report['BalanceData'], 'bankClosingData' => $report['bankClosingData'],
        ]);
    }

    /** CakePHP: account_reports/bill_opening_balance (Members' Closing Balance) */
    public function openingBalance(Request $request)
    {
        @set_time_limit(2700);

        return $this->legacyView('society.reports.opening-balance', [
            'title' => "Members' Closing Balance",
            'societyDetails' => $this->societyDetails(),
            'societyMemberName' => (new OpeningBalanceReport($this->societyId()))->run(),
        ]);
    }

    /** CakePHP: account_reports/comparison_2018closing_2019opening (Bill Summary Update) */
    public function billSummaryUpdate(Request $request)
    {
        @set_time_limit(2700);
        $report = new BillSummaryUpdateReport($this->societyId(), $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date'));
        $data = $report->run();

        return $this->legacyView('society.reports.bill-summary-update', [
            'title' => 'Bill Summary Update',
            // CakePHP never passes the society to this report, so its heading lines stay empty
            'societyDetails' => [],
            'societyMemberDetails' => $data['societyMemberDetails'],
            'differenceTotalAmount' => $data['differenceTotalAmount'],
        ]);
    }

    /** CakePHP: account_reports/reconcile_member_ledger_preview and _apply (JSON, per selected member) */
    private function reconcileMembers(Request $request, bool $save)
    {
        @set_time_limit(900);
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('memberIds', []))));
        if (!$ids) {
            return response()->json(['success' => false, 'error' => 'No members selected']);
        }
        $societyId = $this->societyId();
        $report = new BillSummaryUpdateReport($societyId, $this->fyId(), session('fy.year_start_date'), session('fy.year_end_date'));
        $results = [];
        foreach ($ids as $memberId) {
            // only members of this society
            if (!DB::table('members')->where('id', $memberId)->where('society_id', $societyId)->exists()) {
                $results[$memberId] = ['success' => false, 'error' => 'Member not found'];
                continue;
            }
            try {
                $results[$memberId] = $report->reconcile($memberId, $save);
            } catch (\Throwable $e) {
                $results[$memberId] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    public function reconcilePreview(Request $request)
    {
        return $this->reconcileMembers($request, false);
    }

    public function reconcileApply(Request $request)
    {
        return $this->reconcileMembers($request, true);
    }

    /**
     * CakePHP: account_reports/fix_balance_mismatches - the page's "Update Bill" button, which writes the opening
     * balance through the bill engine (SocietyBillsController::applyMemberOpeningBalance). That write path is not
     * part of the Laravel bill module yet, so nothing is changed here; "Confirm & Update Selected" of the second
     * block (reconcile) does the correction.
     */
    public function fixBalanceMismatches(Request $request)
    {
        return response()->json(['success' => false, 'error' => 'Update Bill is not available in this version yet - use "Check All & Select Wrong" and "Confirm & Update Selected" below.']);
    }

    /** CakePHP: account_reports/member_chart */
    public function memberChart(Request $request)
    {
        @set_time_limit(2700);
        $societyId = $this->societyId();
        $post = $request->isMethod('post');
        $input = $post ? $request->only(['from_date', 'to_date', 'bill_type', 'building_id', 'wing_id', 'flat_no', 'flat_no_to']) : [];
        $data = $post ? (new MemberChartReport($societyId, $this->fyId()))->run($input) : ['memberChartData' => [], 'uniqueLedgerHeads' => []];

        return $this->legacyView('society.reports.member-chart', $data + [
            'title' => 'Member Chart',
            'postData' => $post ? ['MemberChart' => $input] : [],
            'societyDetails' => $this->societyDetails(),
            'buildings' => $this->buildingLists($societyId),
        ]);
    }

    /** CakePHP: audit_reports/edit - the Statutory Audit Report form (draft / finalized), for the year in use or the year asked for */
    public function auditReport(Request $request, $financialYearId = null)
    {
        $societyId = $this->societyId();
        $financialYearId = $financialYearId ?: session('fy.year_id');
        if (empty($financialYearId)) {
            return redirect()->route('society.reportAccounts')->with('audit_flash', 'No financial year is selected for this account.');
        }
        $financialYear = DB::table('financial_year_master')->where('id', $financialYearId)->first();
        if (!$financialYear) {
            abort(404);
        }
        $service = new AuditReportService($societyId);
        $report = $service->report($financialYearId);

        if ($request->isMethod('post')) {
            $service->save($report, $financialYearId, $request->all(), $request->has('finalize'), Auth::id());

            return redirect()->route('society.reports.auditReportYear', $financialYearId)->with('audit_flash', 'Audit report saved.');
        }

        return view('society.reports.audit-report-edit', array_merge($service->answers($report), $service->questions(), [
            'society' => $this->society(),
            'financialYear' => $financialYear,
            'report' => $report,
            'memberCount' => $service->memberCount(),
            'financialSnapshot' => $service->snapshot(),
        ]));
    }

    /** CakePHP: audit_reports/pdf - the printable report as a PDF, in Marathi ('mr') or English ('en') */
    public function auditReportPdf(Request $request, $id, $lang = 'mr')
    {
        @set_time_limit(300);
        $societyId = $this->societyId();
        $service = new AuditReportService($societyId);
        $report = $service->reportById($id);
        if (!$report) {
            return redirect()->route('society.reports.auditReport')->with('audit_flash', 'Audit report not found.');
        }
        $society = $this->society();
        $financialYear = DB::table('financial_year_master')->where('id', $report['financial_year_id'])->first();
        $lang = in_array($lang, ['mr', 'en']) ? $lang : 'mr';

        $data = array_merge($service->answers($report), $service->questions(), [
            'society' => ['Society' => $society ? $society->toArray() : []],
            'financialYear' => ['FinancialYearMaster' => (array) $financialYear],
            'report' => ['SocietyAuditReport' => $report],
            'memberCount' => $service->memberCount(),
            'financialSnapshot' => json_decode((string) $report['financial_snapshot'], true) ?: [],
            'lang' => $lang,
            'opinionParagraphs' => \App\Support\AuditReportData::auditorsOpinionParagraphs($lang == 'mr'),
        ]);
        set_error_handler(fn () => true);
        try {
            $html = view('society.reports.audit-report-pdf', $data)->render();
        } finally {
            restore_error_handler();
        }
        $pdf = $service->renderPdf($html);
        $name = 'Audit-Report-' . preg_replace('/[^A-Za-z0-9]+/', '-', $society->society_name ?? '') . '-' . ($financialYear->year ?? '') . '-' . strtoupper($lang) . '.pdf';

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . $name . '"']);
    }

    /** CakePHP: account_reports/account_bank_book */
    public function bankBook(Request $request)
    {
        $societyId = $this->societyId();
        $banks = $this->bankLists($societyId);
        $society = Society::where('user_id', $societyId)->first();

        $input = $request->isMethod('post')
            ? $request->only(['society_bank_id', 'payment_date', 'payment_date_to', 'report_type', 'operator', 'amount'])
            : [];

        $report = ['bankBookData' => [], 'openingBalance' => 0, 'openingBalanceAsOfDate' => null];
        if ($request->isMethod('post')) {
            $report = (new BankBookReport($societyId, session('fy.year_id'), session('fy.year_start_date')))->run($input);
        }

        return view('society.reports.bank-book', [
            'title' => 'Bank Book',
            'input' => $input,
            'banks' => $banks,
            'society' => $society,
            'bankBookData' => $report['bankBookData'],
            'openingBalance' => $report['openingBalance'],
            'openingBalanceAsOfDate' => $report['openingBalanceAsOfDate'],
            'financialYearStart' => session('fy.year_start_date'),
        ]);
    }

    /** CakePHP: account_reports/account_cash_book */
    public function cashBook(Request $request)
    {
        $societyId = $this->societyId();
        $cashHeads = $this->cashLists($societyId);
        $society = Society::where('user_id', $societyId)->first();

        $input = $request->isMethod('post')
            ? $request->only(['cash_ledger_head_id', 'payment_date', 'payment_date_to', 'report_type', 'operator', 'amount'])
            : [];

        $report = ['cashBookData' => [], 'openingBalance' => 0, 'ledgerHeadSelected' => false];
        if ($request->isMethod('post')) {
            $report = (new CashBookReport($societyId, session('fy.year_id'), session('fy.year_start_date')))->run($input);
        }

        return view('society.reports.cash-book', [
            'title' => 'Cash Book',
            'input' => $input,
            'cashHeads' => $cashHeads,
            'society' => $society,
            'cashBookData' => $report['cashBookData'],
            'openingBalance' => $report['openingBalance'],
            'ledgerHeadSelected' => $report['ledgerHeadSelected'],
            'financialYearStart' => session('fy.year_start_date'),
        ]);
    }

    // ─── Bill print (CakePHP account_reports: bill_half_page, bill_full_page, bill_tax_invoice_gst, bill_with_interest_gst, bill_summary_with_prev_data) ───

    private const BILL_FILTER = ['month', 'month_to', 'bill_date', 'bill_date_to', 'bill_no', 'bill_no_to', 'flat_no', 'flat_no_to', 'from_date', 'to_date', 'unit_area'];

    /** The bill filter as posted (blank boxes stay '', as CakePHP reads them); [] on a plain page open. */
    private function billInput(Request $request): array
    {
        if (!$request->isMethod('post')) {
            return [];
        }
        $in = [];
        foreach ($request->only(self::BILL_FILTER) as $k => $v) {
            $in[$k] = $v ?? '';
        }

        return $in;
    }

    /** Months of the society's billing frequency, [month no => label], for the "From / To" boxes. */
    private function billMonths(): array
    {
        $p = DB::table('society_parameters')->where('society_id', $this->societyId())->orderBy('id')->first(['billing_frequency_id']);

        return ReportUtil::billingFrequency($p->billing_frequency_id ?? 0);
    }

    /** The parts every bill print screen hands to bill-print.blade.php */
    private function billScreen(Request $request, string $title, string $route, string $body, string $printId, array $filterFlags = []): array
    {
        return [
            'title' => $title,
            'action' => route($route),
            'post' => $request->isMethod('post'),
            'input' => $this->billInput($request),
            'months' => $this->billMonths(),
            'body' => $body,
            'printId' => $printId,
        ] + $filterFlags;
    }

    /** CakePHP: account_reports/bill_half_page (two bills to a sheet) */
    public function billHalfPage(Request $request)
    {
        $data = $this->billScreen($request, 'Bill Half Page', 'society.reports.billHalfPage', 'society.reports._bill_half_body', 'print_member_new_bill');
        $report = new BillPrintReport($this->societyId(), $this->fyId());
        $bills = $data['post'] ? $report->bills($data['input']) : [];

        // the Excel / PDF links carry the filter base64-encoded, and leave the unit range out
        $in = $data['input'];
        $enc = fn ($k) => !BillPrintReport::phpEmpty($in[$k] ?? null) ? base64_encode($in[$k]) : '';
        $query = [
            'bill_generated_date_to' => $enc('bill_date_to'), 'bill_generated_date' => $enc('bill_date'),
            'month' => $enc('month'), 'month_to' => $enc('month_to'), 'bill_no' => $enc('bill_no'), 'bill_no_to' => $enc('bill_no_to'),
        ];

        return $this->legacyView('society.reports.bill-print', $data + [
            'bills' => $bills,
            'monthlyBillsSummaryDetails' => $bills,
            'societyDetails' => $this->societyDetails(),
            'societyParameters' => ['SocietyParameter' => $report->societyParameters()],
            'societyLedgerHeadTitleList' => $bills ? $report->tariffTitles() : [],
            'wingList' => $bills ? $report->wingList() : [],
            'excelUrl' => route('society.reports.billHalfPageExcel', $query),
            'pdfUrl' => route('society.reports.billHalfPagePdf', $query),
        ]);
    }

    /** The half-page filter as the Excel / PDF links carry it (base64 in the query string), in the names of the screen filter. */
    private function billHalfPageLinkFilter(Request $request): array
    {
        $get = fn ($k) => $request->query($k) !== null && $request->query($k) !== '' ? base64_decode($request->query($k)) : '';

        return [
            'month' => $get('month'), 'month_to' => $get('month_to'),
            'bill_date' => $get('bill_generated_date'), 'bill_date_to' => $get('bill_generated_date_to'),
            'bill_no' => $get('bill_no'), 'bill_no_to' => $get('bill_no_to'),
            'flat_no' => $get('flat_no'), 'flat_no_to' => $get('flat_no_to'),
        ];
    }

    /** CakePHP: account_reports/export_bill_half_page_pdf - the half-page bills as a PDF download */
    public function billHalfPagePdf(Request $request)
    {
        @set_time_limit(300);
        $in = $this->billHalfPageLinkFilter($request);
        // this link reads the bill-no "To" box properly (unlike the screen filter, which reads the bill date "To" value)
        if (BillPrintReport::phpEmpty($in['bill_no']) && !BillPrintReport::phpEmpty($in['bill_no_to'])) {
            $in['bill_no'] = $in['bill_no_to'];
            $in['bill_no_to'] = '';
        }
        $report = new BillPrintReport($this->societyId(), $this->fyId());
        $bills = $report->bills($in);

        $html = $this->legacyView('society.reports.bill-half-page-pdf', [
            'monthlyBillsSummaryDetails' => $bills,
            'societyDetails' => $this->societyDetails(),
            'societyParameters' => ['SocietyParameter' => $report->societyParameters()],
            'societyLedgerHeadTitleList' => $report->tariffTitles(),
            'wingList' => $report->wingList(),
        ])->getContent();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4')->output();

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Bill_Half_Page_' . date('Ymd_His') . '.pdf"']);
    }

    /** CakePHP: excels_report/download_account_bill_half - the half-page bills as an Excel sheet */
    public function billHalfPageExcel(Request $request)
    {
        @set_time_limit(300);
        $societyId = $this->societyId();
        $society = $this->society();
        $sheet = new BillHalfPageExcel($societyId);
        $society_name = $society ? ReportUtil::plain($society->society_name) : '';
        $name = ($society_name !== '' ? 'Bill Half Page' . $society_name : 'Bill Register -') . '_' . date('Ymd_His');

        return $sheet->download($this->billHalfPageLinkFilter($request), $name . '.xlsx');
    }

    /** CakePHP: account_reports/bill_full_page (one bill to a sheet) */
    public function billFullPage(Request $request)
    {
        $data = $this->billScreen($request, 'Bill Full Page', 'society.reports.billFullPage', 'society.reports._bill_full_body', 'print_member_new_bill');
        $report = new BillPrintReport($this->societyId(), $this->fyId());
        $bills = $data['post'] ? $report->bills($data['input'], $report->wingList()) : [];

        return $this->legacyView('society.reports.bill-print', $data + [
            'bills' => $bills,
            'monthlyBillsSummaryDetails' => $bills,
            'societyDetails' => $this->societyDetails(),
            'societyParameters' => ['SocietyParameter' => $report->societyParameters()],
            'societyLedgerHeadTitleList' => $bills ? $report->tariffTitles() : [],
        ]);
    }

    /** CakePHP: account_reports/bill_tax_invoice_gst (bill split into non-taxable and taxable charges, with GST and the period's receipts) */
    public function billTaxInvoiceGst(Request $request)
    {
        @set_time_limit(1800);
        $data = $this->billScreen($request, 'Bill Tax Invoice (GST)', 'society.reports.billTaxInvoiceGst', 'society.reports._bill_tax_invoice_body', 'society_print_gst_bill', [
            'showUnitArea' => true, 'showReceiptPeriod' => true, 'showEmail' => true,
        ]);
        if ($data['post'] && $request->input('submit') === 'email') {
            return redirect()->route('society.reports.billTaxInvoiceGst')->with('bill_flash',
                'Nothing was e-mailed. The CakePHP screen only built the PDFs on its server for this button - its mail sending is switched off - so this screen sends no mail either.');
        }
        $report = new BillPrintReport($this->societyId(), $this->fyId());
        $bills = $data['post'] ? $report->taxInvoiceBills($data['input']) : [];

        return $this->legacyView('society.reports.bill-print', $data + [
            'bills' => $bills,
            'taxBillsSummaryDetails' => $bills,
            'postData' => $data['post'] ? ['MemberBillSummary' => $data['input']] : [],
            'unitArea' => $data['input']['unit_area'] ?? null,
            'societyDetails' => $this->societyDetails(),
            'societyParameters' => ['SocietyParameter' => $report->societyParameters()],
            'societyLedgerHeadTitleList' => $bills ? $report->tariffTitles() : [],
        ]);
    }

    /** CakePHP: account_reports/bill_with_interest_gst (maintenance bill with interest, GST and the member's receipts) */
    public function billWithInterestGst(Request $request)
    {
        $data = $this->billScreen($request, 'Bill With Interest & GST', 'society.reports.billWithInterestGst', 'society.reports._bill_interest_gst_body', 'print_int_with_gst_bill', [
            'showReceiptPeriod' => true,
        ]);
        $report = new BillPrintReport($this->societyId(), $this->fyId());
        $bills = $data['post'] ? $report->interestGstBills($data['input']) : [];
        $paymentIds = [];
        foreach ($bills as $b) {
            foreach ($b['receipts'] ?? [] as $r) {
                $paymentIds[] = $r['MemberPayment']['id'];
            }
        }

        return $this->legacyView('society.reports.bill-print', $data + [
            'bills' => $bills,
            'monthlyBillsSummaryDetails' => $bills,
            'societyDetails' => $this->societyDetails(),
            'societyParameters' => ['SocietyParameter' => $report->societyParameters()],
            'wingDetails' => $bills ? $report->wingList() : [],
            'societyLedgerHeadTitleList' => $bills ? $report->tariffTitles() : [],
            'receiptBillInfoArray' => $report->receiptBillInfo($paymentIds),
        ]);
    }

    /** CakePHP: account_reports/bill_summary_with_prev_data (a bill block for every member: previous bills, received, balance, current bill) */
    public function billSummaryWithPrevData(Request $request)
    {
        @set_time_limit(1800);
        $data = $this->billScreen($request, 'Bill Summary With PrevData', 'society.reports.billSummaryWithPrevData', 'society.reports._bill_prev_data_body', 'print_member_new_bill', [
            'prevDataFilter' => true,
        ]);
        // the bill-type box has its own name on this screen
        $billType = $request->isMethod('post') ? ($request->input('bill_type') ?? '') : '';
        $data['input']['bill_type'] = $billType;
        $input = $data['input'];
        $bills = $data['post'] ? (new BillSummaryPrevDataReport($this->societyId()))->run(['month' => $input['month'] ?? '', 'bill_type' => $billType]) : [];

        return $this->legacyView('society.reports.bill-print', $data + [
            'bills' => $bills,
            'billsSummaryDetails' => $bills,
            'societyParameters' => ['SocietyParameter' => (new BillPrintReport($this->societyId(), $this->fyId()))->societyParameters()],
            'requestData' => ['reqeust' => ['month_text' => $data['months'][$input['month'] ?? ''] ?? '']],
        ]);
    }

    // ─── Bill With Receipt Tabular / Print Bill (CakePHP reports/bill_with_receipt_tabular + society_bills/print_member_bills) ───

    /** CakePHP: reports/bill_with_receipt_tabular - the form of the member bill print */
    public function billWithReceiptTabular(Request $request)
    {
        return view('society.reports.bill-with-receipt-tabular', ['months' => $this->billMonths()]);
    }

    /** The bill print's query as CakePHP reads it (the remove-details list may be absent when nothing is picked). */
    private function memberBillQuery(Request $request): array
    {
        $q = $request->query();
        $q['remove_details'] = $q['remove_details'] ?? [];

        return $q;
    }

    private function memberBillData(Request $request): array
    {
        $q = $this->memberBillQuery($request);
        $data = (new MemberBillPrintReport($this->societyId(), $this->fyId()))->run($q);
        // CakePHP: "English" picks the English words, anything else (also nothing) the Marathi ones
        $words = ($q['words_type'] ?? null) == 'English'
            ? fn ($n) => \App\Support\BillWords::english($n)
            : fn ($n) => \App\Support\BillWords::marathi($n);

        return $data + ['q' => $q, 'wordsFunction' => $words, 'html' => new \App\Support\LegacyHtml()];
    }

    /** CakePHP: society_bills/print_member_bills - the member bills (charges and the member's receipts) of a bill month */
    public function printMemberBills(Request $request)
    {
        @set_time_limit(1800);

        return $this->legacyView('society.reports.member-bill-print', $this->memberBillData($request) + [
            'pdfUrl' => route('society.printMemberBillsPdf', $request->query()),
        ]);
    }

    /** CakePHP: society_bills/export_member_bills_pdf - the same bills as a PDF download */
    public function printMemberBillsPdf(Request $request)
    {
        @set_time_limit(1800);
        $html = $this->legacyView('society.reports.member-bill-pdf', $this->memberBillData($request))->getContent();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="Bill_' . date('Ymd_His') . '.pdf"']);
    }
}
