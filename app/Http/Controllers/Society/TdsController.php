<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\FinancialYearMaster;
use App\Models\Society;
use App\Models\TdsCertificate;
use App\Models\TdsChallanTransaction;
use App\Services\Tds\TdsService;
use Dompdf\Dompdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Port of CakePHP TdsController (TDS Management & Report module): dashboard, sections, deductees,
 * transactions (+ calculation ajax, reversal), report (+ PDF / Excel), ledger, challans, certificates.
 * Society scoping: society id == Auth::id() (Cake: Session Auth.User.id); financial year: session fy.year_id.
 */
class TdsController extends Controller
{
    private const PAID_STATUSES = ['Paid', 'Challan Verified', 'Filed'];
    private const PENDING_STATUSES = ['Pending', 'Challan Prepared', 'Payment Pending'];
    private const CHALLAN_STATUSES = ['Pending', 'Challan Prepared', 'Payment Pending', 'Paid', 'Challan Verified', 'Filed'];

    /** varchar / text columns - CakePHP keeps '' there, everything else becomes NULL */
    private const SECTION_STRING_COLS = ['section_code', 'nature_of_payment', 'description'];
    private const DEDUCTEE_STRING_COLS = ['lower_deduction_cert_no'];
    private const CHALLAN_STRING_COLS = ['challan_number', 'bsr_code', 'cin', 'remarks'];

    private function societyId()
    {
        return (string) Auth::id();
    }

    private function fyId()
    {
        $id = session('fy.year_id');
        return $id === null ? null : (string) $id;
    }

    private function tds(): TdsService
    {
        return new TdsService($this->societyId(), $this->fyId(), (string) Auth::id());
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    /** The allowed posted fields, as strings and in the order they were posted (CakePHP kept the request order in $this->request->data) */
    private function postedFields(Request $request, array $allowed): array
    {
        $out = [];
        foreach ($request->post() as $f => $v) {
            if (in_array($f, $allowed, true) && is_scalar($v)) {
                $out[$f] = (string) $v;
            }
        }
        return $out;
    }

    private function financialYearsList()
    {
        return FinancialYearMaster::orderBy('id')->pluck('year', 'id');
    }

    private function society()
    {
        return Society::where('user_id', $this->societyId())->first();
    }

    /* ------------------------------------------------------------------ Dashboard */

    public function dashboard()
    {
        $societyId = $this->societyId();
        $fy = $this->fyId();

        $base = fn () => DB::table('tds_transactions as t')
            ->where('t.society_id', $societyId)
            ->where('t.financial_year_id', $fy)
            ->where('t.is_reversed', 0);

        $deducted = $base()->selectRaw('SUM(t.tds_amount) as total_tds, COUNT(DISTINCT t.vendor_detail_id) as deductee_count')->first();
        $currentFyTdsDeducted = isset($deducted->total_tds) ? $deducted->total_tds : 0;
        $deducteeCount = isset($deducted->deductee_count) ? $deducted->deductee_count : 0;

        $paid = $base()->whereIn('t.challan_status', self::PAID_STATUSES)->selectRaw('SUM(t.tds_amount) as total_paid')->first();
        $totalPaid = isset($paid->total_paid) ? $paid->total_paid : 0;

        $tdsPayable = round(floatval($currentFyTdsDeducted) - floatval($totalPaid), 2);

        $challans = fn () => DB::table('tds_challans')->where('society_id', $societyId)->where('financial_year_id', $fy);
        $challanPendingCount = $challans()->whereIn('payment_status', self::PENDING_STATUSES)->count();
        $challanPaidCount = $challans()->whereIn('payment_status', self::PAID_STATUSES)->count();

        $sectionWise = $base()
            ->join('tds_sections as s', 't.tds_section_id', '=', 's.id')
            ->selectRaw('s.section_code, s.nature_of_payment, SUM(t.tds_amount) as section_total')
            ->groupBy('t.tds_section_id', 's.section_code', 's.nature_of_payment')
            ->get();

        return view('society.tds.dashboard', compact('currentFyTdsDeducted', 'tdsPayable', 'challanPendingCount', 'challanPaidCount', 'deducteeCount', 'sectionWise'));
    }

    /* ------------------------------------------------------------------ TDS Section master */

    public function sections()
    {
        $sectionsList = DB::table('tds_sections as s')
            ->leftJoin('society_ledger_heads as l', 'l.id', '=', 's.tds_payable_ledger_head_id')
            ->where('s.society_id', $this->societyId())
            ->orderBy('s.section_code')
            ->select('s.*', 'l.title as ledger_title')
            ->get();

        return view('society.tds.sections', compact('sectionsList'));
    }

    public function addSection(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $tds = $this->tds();

        $d = [];
        if ($request->isMethod('get') && $id) {
            $row = DB::table('tds_sections')->where('id', $id)->where('society_id', $societyId)->first();
            if (empty($row)) {
                return redirect()->route('society.tdsSections')->with('error', 'TDS Section not found.');
            }
            $d = (array) $row;
        }

        if ($request->isMethod('post')) {
            $posted = $this->postedFields($request, ['id', 'section_code', 'nature_of_payment', 'description', 'deductee_type', 'resident_type', 'rate_percent', 'threshold_limit', 'threshold_basis', 'effective_from', 'effective_to', 'tds_payable_ledger_head_id', 'status']);
            $data = $posted;
            $data['society_id'] = $societyId;
            $data['udate'] = $this->now();
            $isNew = empty($data['id']);
            $old = null;
            if ($isNew) {
                $data['cdate'] = $this->now();
            } else {
                $old = DB::table('tds_sections')->where('id', $data['id'])->where('society_id', $societyId)->first();
                if (empty($old)) {
                    return redirect()->route('society.tdsSections')->with('error', 'TDS Section not found.');
                }
            }

            $errors = TdsService::validateSection($data);
            $saved = false;
            $savedId = null;
            if (!$errors) {
                $write = TdsService::cakeWrite($data, self::SECTION_STRING_COLS, ['threshold_limit']);
                try {
                    if ($isNew) {
                        unset($write['id']);
                        $savedId = DB::table('tds_sections')->insertGetId($write);
                    } else {
                        $savedId = $write['id'];
                        unset($write['id']);
                        DB::table('tds_sections')->where('id', $savedId)->update($write);
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    $saved = false; // e.g. uniq_tds_section duplicate
                }
            }

            if ($saved) {
                $tds->writeAuditLog('tds_sections', $savedId, $isNew ? 'create' : 'update', $old ? TdsService::cakeRow($old) : null, $data);
                return redirect()->route('society.tdsSections')->with('success', 'TDS Section saved successfully.');
            }
            session()->now('error', 'The TDS Section could not be saved. Please check the fields.');
            $d = $posted;
        }

        $tdsPayableLedgerHeadsList = DB::table('society_ledger_heads')
            ->where('tds_type', 1)->where('society_id', $societyId)->where('status', 1)
            ->pluck('title', 'id');

        return view('society.tds.add-section', compact('d', 'tdsPayableLedgerHeadsList'));
    }

    public function toggleSectionStatus($id)
    {
        $section = DB::table('tds_sections')->where('id', $id)->where('society_id', $this->societyId())->first();
        if (empty($section)) {
            return response()->json(['error' => 1, 'error_message' => 'TDS Section not found.']);
        }
        $newStatus = $section->status == 1 ? 0 : 1;
        DB::table('tds_sections')->where('id', $id)->update(['status' => $newStatus]);
        $this->tds()->writeAuditLog('tds_sections', $id, 'status_change', ['status' => (bool) $section->status], ['status' => $newStatus]);

        return response()->json(['error' => 0, 'status' => $newStatus]);
    }

    /* ------------------------------------------------------------------ Deductee master (extends vendor details) */

    public function deductees()
    {
        $vendors = DB::table('vendor_details')
            ->where('society_id', $this->societyId())
            ->orderBy('contact_person_name')
            ->get();
        $tdsDeducteeMap = [];
        foreach (DB::table('tds_deductees')->where('society_id', $this->societyId())->get() as $e) {
            $tdsDeducteeMap[$e->vendor_detail_id] = (array) $e;
        }

        return view('society.tds.deductees', compact('vendors', 'tdsDeducteeMap'));
    }

    public function addDeductee(Request $request, $vendorDetailId = null)
    {
        $societyId = $this->societyId();
        $vendor = DB::table('vendor_details')->where('id', $vendorDetailId)->where('society_id', $societyId)->first();
        if (empty($vendor)) {
            return redirect()->route('society.tdsDeductees')->with('error', 'Vendor/Deductee not found.');
        }

        $existing = DB::table('tds_deductees')->where('vendor_detail_id', $vendorDetailId)->first();
        $d = !empty($existing) ? (array) $existing : ['vendor_detail_id' => $vendorDetailId];

        if ($request->isMethod('post')) {
            $data = $this->postedFields($request, ['deductee_type', 'resident_status', 'lower_deduction_cert_no', 'lower_deduction_rate', 'lower_deduction_valid_from', 'lower_deduction_valid_upto']);
            $data['society_id'] = $societyId;
            $data['vendor_detail_id'] = (string) $vendorDetailId;
            $data['udate'] = $this->now();
            if (!empty($existing)) {
                $data['id'] = (string) $existing->id;
            } else {
                $data['cdate'] = $this->now();
            }

            $saved = false;
            $savedId = null;
            if (TdsService::notBlank($data['vendor_detail_id'])) {
                $write = TdsService::cakeWrite($data, self::DEDUCTEE_STRING_COLS);
                try {
                    if (!empty($existing)) {
                        unset($write['id']);
                        DB::table('tds_deductees')->where('id', $existing->id)->update($write);
                        $savedId = $existing->id;
                    } else {
                        $savedId = DB::table('tds_deductees')->insertGetId($write);
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    $saved = false;
                }
            }

            if ($saved) {
                $this->tds()->writeAuditLog('tds_deductees', $savedId, empty($existing) ? 'create' : 'update', !empty($existing) ? TdsService::cakeRow($existing) : null, $data);
                return redirect()->route('society.tdsDeductees')->with('success', 'Deductee TDS details saved successfully.');
            }
            session()->now('error', 'Could not save deductee TDS details.');
            $d = $data;
        }

        return view('society.tds.add-deductee', compact('vendor', 'd'));
    }

    /* ------------------------------------------------------------------ TDS transactions */

    /** TdsTransaction rows with the TdsSection / VendorDetail (/ TdsChallan) belongsTo joins Cake's contain produced */
    private function transactionQuery(bool $withChallan = false): Builder
    {
        $q = DB::table('tds_transactions as t')
            ->leftJoin('tds_sections as s', 's.id', '=', 't.tds_section_id')
            ->leftJoin('vendor_details as v', 'v.id', '=', 't.vendor_detail_id');
        $select = ['t.*', 's.section_code as section_code', 'v.contact_person_name as contact_person_name'];
        if ($withChallan) {
            $q->leftJoin('tds_challans as c', 'c.id', '=', 't.tds_challan_id');
            array_push($select, 'c.challan_number', 'c.challan_date', 'c.bsr_code', 'c.cin');
        }

        return $q->select($select);
    }

    public function transactions(Request $request)
    {
        $societyId = $this->societyId();
        $qs = $request->query();

        $apply = function (Builder $q) use ($societyId, $qs) {
            $q->where('t.society_id', $societyId);
            $q->where('t.financial_year_id', !empty($qs['financial_year_id']) ? $qs['financial_year_id'] : $this->fyId());
            if (!empty($qs['tds_section_id'])) {
                $q->where('t.tds_section_id', $qs['tds_section_id']);
            }
            if (!empty($qs['vendor_detail_id'])) {
                $q->where('t.vendor_detail_id', $qs['vendor_detail_id']);
            }
            if (!empty($qs['challan_status'])) {
                $q->where('t.challan_status', $qs['challan_status']);
            }
            if (!empty($qs['date_from']) && !empty($qs['date_to'])) {
                $q->whereRaw('t.deduction_date BETWEEN ? AND ?', [$qs['date_from'], $qs['date_to']]);
            }
        };

        $transactionsList = $this->transactionQuery();
        $apply($transactionsList);
        $transactionsList = $transactionsList->orderByDesc('t.deduction_date')->get();

        $totals = DB::table('tds_transactions as t');
        $apply($totals);
        $totals = $totals->selectRaw('SUM(t.gross_amount) as total_gross, SUM(t.tds_amount) as total_tds, SUM(t.net_amount) as total_net')->first();

        $financialYearsList = $this->financialYearsList();
        $sectionsList = $this->tds()->activeSectionsList();
        $vendorList = $this->tds()->deducteeVendorList();

        return view('society.tds.transactions', compact('transactionsList', 'totals', 'financialYearsList', 'sectionsList', 'vendorList', 'qs'));
    }

    public function addTransaction(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->fyId();
        $tds = $this->tds();
        $d = [];

        if ($request->isMethod('get') && $id) {
            $row = DB::table('tds_transactions')->where('id', $id)->where('society_id', $societyId)->first();
            if (empty($row)) {
                return redirect()->route('society.tdsTransactions')->with('error', 'TDS Transaction not found.');
            }
            $d = (array) $row;
        }

        // Prefill from a vendor bill (bridge: bill-computed estimate -> transaction)
        if ($request->isMethod('get') && empty($id) && $request->query('vendor_bill_id')) {
            $bill = DB::table('vendor_bills')->where('id', $request->query('vendor_bill_id'))->where('society_id', $societyId)->first();
            if (!empty($bill)) {
                $vendorDetail = DB::table('vendor_details')->where('ledger_head_id', $bill->vendor_ledger_head_id)->where('society_id', $societyId)->first();
                $d['vendor_bill_id'] = $bill->id;
                $d['vendor_detail_id'] = !empty($vendorDetail) ? $vendorDetail->id : '';
                $d['invoice_no'] = $bill->bill_no;
                $d['gross_amount'] = $bill->total_amount;
                $d['deduction_date'] = $bill->bill_date;
            }
        }

        if ($request->isMethod('post')) {
            $in = $request->input();
            $d = [];
            foreach (['id', 'vendor_bill_id', 'vendor_detail_id', 'tds_section_id', 'deduction_date', 'payment_date', 'invoice_no', 'gross_amount', 'remarks'] as $f) {
                if ($request->has($f)) {
                    $d[$f] = (string) $request->input($f);
                }
            }
            $postId = !empty($in['id']) ? $in['id'] : null;
            $vendorDetailId = $in['vendor_detail_id'] ?? null;
            $tdsSectionId = $in['tds_section_id'] ?? null;
            $grossAmount = floatval($in['gross_amount'] ?? 0);
            $deductionDate = $in['deduction_date'] ?? null;
            $vendorBillId = !empty($in['vendor_bill_id']) ? $in['vendor_bill_id'] : null;

            if ($postId && !DB::table('tds_transactions')->where('id', $postId)->where('society_id', $societyId)->exists()) {
                return redirect()->route('society.tdsTransactions')->with('error', 'TDS Transaction not found.');
            }

            // Duplicate prevention: same bill can't be TDS-deducted twice under the same section
            if (empty($postId) && !empty($vendorBillId)) {
                $dupe = DB::table('tds_transactions')
                    ->where('vendor_bill_id', $vendorBillId)->where('tds_section_id', $tdsSectionId)->where('is_reversed', 0)
                    ->count();
                if ($dupe > 0) {
                    session()->now('error', 'TDS has already been deducted for this bill under this section.');
                    return view('society.tds.add-transaction', [
                        'd' => $d,
                        'sectionsList' => $tds->activeSectionsList(),
                        'vendorList' => $tds->deducteeVendorList(),
                    ]);
                }
            }

            $calc = $tds->calculateTds($vendorDetailId, $tdsSectionId, $grossAmount, $deductionDate, $postId);
            if (!$calc['ok']) {
                session()->now('error', $calc['message']);
            } else {
                $vendorDetail = DB::table('vendor_details')->where('id', $vendorDetailId)->first();

                $saveData = [
                    'id' => $postId,
                    'society_id' => $societyId,
                    'financial_year_id' => $financialYearId,
                    'tds_section_id' => $tdsSectionId,
                    'vendor_detail_id' => $vendorDetailId,
                    'vendor_bill_id' => $vendorBillId,
                    'pan_no' => !empty($vendorDetail->pan_no) ? $vendorDetail->pan_no : null,
                    'deduction_date' => $deductionDate,
                    'payment_date' => !empty($in['payment_date']) ? $in['payment_date'] : null,
                    'invoice_no' => !empty($in['invoice_no']) ? $in['invoice_no'] : null,
                    'gross_amount' => $grossAmount,
                    'tds_rate' => $calc['rate_percent'],
                    'tds_amount' => $calc['tds_amount'],
                    'net_amount' => $calc['net_amount'],
                    'tds_payable_ledger_head_id' => $calc['tds_payable_ledger_head_id'],
                    'remarks' => !empty($in['remarks']) ? $in['remarks'] : null,
                    'created_by' => (string) Auth::id(),
                    'udate' => $this->now(),
                ];
                if (empty($postId)) {
                    $saveData['cdate'] = $this->now();
                    $saveData['challan_status'] = 'Pending';
                }

                $errors = TdsService::validateTransaction($saveData);
                if ($errors) {
                    session()->now('error', 'The TDS Transaction could not be saved: ' . TdsService::lastValidationMessage($errors));
                } else {
                    $saved = false;
                    $savedId = null;
                    try {
                        $write = $saveData;
                        unset($write['id']);
                        if (empty($postId)) {
                            $savedId = DB::table('tds_transactions')->insertGetId($write);
                        } else {
                            DB::table('tds_transactions')->where('id', $postId)->update($write);
                            $savedId = $postId;
                        }
                        $saved = true;
                    } catch (\Illuminate\Database\QueryException $e) {
                        session()->now('error', 'The TDS Transaction could not be saved. Duplicate deduction for this payment/section/deductee is not allowed.');
                    }
                    if ($saved) {
                        $tds->writeAuditLog('tds_transactions', $savedId, empty($postId) ? 'create' : 'update', null, $saveData);
                        return redirect()->route('society.tdsTransactions')
                            ->with('success', 'TDS Transaction saved successfully. TDS Amount: Rs. ' . number_format($calc['tds_amount'], 2));
                    }
                }
            }
        }

        return view('society.tds.add-transaction', [
            'd' => $d,
            'sectionsList' => $tds->activeSectionsList(),
            'vendorList' => $tds->deducteeVendorList(),
        ]);
    }

    public function calculateTdsAjax(Request $request)
    {
        $vendorDetailId = $request->query('vendor_detail_id');
        $tdsSectionId = $request->query('tds_section_id');
        $grossAmount = $request->query('gross_amount');
        $deductionDate = $request->query('deduction_date');
        $excludeTxnId = $request->query('exclude_txn_id');

        if (empty($vendorDetailId) || empty($tdsSectionId) || !is_numeric($grossAmount) || empty($deductionDate)) {
            return response()->json(['ok' => false, 'message' => 'Missing required fields.']);
        }

        return response()->json($this->tds()->calculateTds($vendorDetailId, $tdsSectionId, floatval($grossAmount), $deductionDate, $excludeTxnId));
    }

    public function reverseTransaction(Request $request, $id = null)
    {
        $txn = DB::table('tds_transactions')->where('id', $id)->where('society_id', $this->societyId())->first();
        if (empty($txn)) {
            return redirect()->route('society.tdsTransactions')->with('error', 'TDS Transaction not found.');
        }
        if (!in_array($txn->challan_status, ['Pending'])) {
            return redirect()->route('society.tdsTransactions')->with('error', 'Cannot reverse a transaction that is already part of a challan.');
        }
        if ($request->isMethod('post')) {
            $reason = $request->input('reversal_reason');
            DB::table('tds_transactions')->where('id', $id)->update([
                'is_reversed' => 1,
                'reversed_by' => Auth::id(),
                'reversed_at' => $this->now(),
                'reversal_reason' => $reason,
                'udate' => $this->now(),
            ]);
            $this->tds()->writeAuditLog('tds_transactions', $id, 'reverse', TdsService::cakeRow($txn), ['is_reversed' => 1, 'reversal_reason' => $reason]);

            return redirect()->route('society.tdsTransactions')->with('success', 'TDS Transaction reversed successfully.');
        }

        return view('society.tds.reverse-transaction', ['txn' => (array) $txn]);
    }

    /* ------------------------------------------------------------------ TDS report */

    private function reportQuery(Request $request, Builder $q): Builder
    {
        $qs = $request->query();
        $q->where('t.society_id', $this->societyId())->where('t.is_reversed', 0);
        if (!empty($qs['financial_year_id'])) {
            $q->where('t.financial_year_id', $qs['financial_year_id']);
        }
        if (!empty($qs['date_from']) && !empty($qs['date_to'])) {
            $q->whereRaw('t.deduction_date BETWEEN ? AND ?', [$qs['date_from'], $qs['date_to']]);
        }
        if (!empty($qs['tds_section_id'])) {
            $q->where('t.tds_section_id', $qs['tds_section_id']);
        }
        if (!empty($qs['vendor_detail_id'])) {
            $q->where('t.vendor_detail_id', $qs['vendor_detail_id']);
        }
        if (!empty($qs['pan_no'])) {
            $q->where('t.pan_no', $qs['pan_no']);
        }
        if (!empty($qs['challan_status'])) {
            $q->where('t.challan_status', $qs['challan_status']);
        }

        return $q;
    }

    private function reportTotals(Request $request)
    {
        return $this->reportQuery($request, DB::table('tds_transactions as t'))
            ->selectRaw('SUM(t.gross_amount) as total_gross, SUM(t.tds_amount) as total_tds, SUM(t.net_amount) as total_net')
            ->first();
    }

    public function report(Request $request)
    {
        $reportData = $this->reportQuery($request, $this->transactionQuery(true))->orderBy('t.deduction_date')->get();
        $totals = $this->reportTotals($request);
        $financialYearsList = $this->financialYearsList();
        $sectionsList = $this->tds()->activeSectionsList();
        $vendorList = $this->tds()->deducteeVendorList();
        $qs = $request->query();

        return view('society.tds.report', compact('reportData', 'totals', 'financialYearsList', 'sectionsList', 'vendorList', 'qs'));
    }

    public function reportPdf(Request $request)
    {
        $reportData = $this->reportQuery($request, $this->transactionQuery())->orderBy('t.deduction_date')->get();
        $totals = $this->reportTotals($request);
        $societyDetails = $this->society();

        return $this->pdf(view('society.tds.report-pdf', compact('reportData', 'totals', 'societyDetails'))->render(), 'TDS_Report_' . date('Ymd_His') . '.pdf');
    }

    public function reportExcel(Request $request)
    {
        $reportData = $this->reportQuery($request, $this->transactionQuery())->orderBy('t.deduction_date')->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('TDS Report');

        $headers = ['Sr No', 'Deduction Date', 'Payment Date', 'Deductee Name', 'PAN', 'Invoice No', 'Section', 'Gross Amount', 'TDS Rate', 'TDS Amount', 'Net Amount', 'Challan Status'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        $rowNo = 2;
        $sr = 1;
        $totalGross = 0;
        $totalTds = 0;
        $totalNet = 0;
        foreach ($reportData as $t) {
            $sheet->fromArray([
                $sr++, $t->deduction_date, $t->payment_date,
                $t->contact_person_name, $t->pan_no, $t->invoice_no,
                $t->section_code, $t->gross_amount, $t->tds_rate,
                $t->tds_amount, $t->net_amount, $t->challan_status,
            ], null, 'A' . $rowNo++);
            $totalGross += $t->gross_amount;
            $totalTds += $t->tds_amount;
            $totalNet += $t->net_amount;
        }
        $sheet->fromArray(['', '', '', '', '', '', 'TOTAL', $totalGross, '', $totalTds, $totalNet, ''], null, 'A' . $rowNo);

        $filename = 'TDS_Report_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /* ------------------------------------------------------------------ TDS ledger */

    public function ledger(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $request->query('financial_year_id') ?: $this->fyId();

        $base = fn () => DB::table('tds_transactions as t')
            ->where('t.society_id', $societyId)
            ->where('t.financial_year_id', $financialYearId)
            ->where('t.is_reversed', 0);
        $sums = 'SUM(t.tds_amount) as deducted, SUM(CASE WHEN t.challan_status IN ("Paid","Challan Verified","Filed") THEN t.tds_amount ELSE 0 END) as paid';

        $deducteeWise = $base()
            ->join('vendor_details as v', 't.vendor_detail_id', '=', 'v.id')
            ->selectRaw('v.id, v.contact_person_name, v.pan_no, ' . $sums)
            ->groupBy('t.vendor_detail_id', 'v.id', 'v.contact_person_name', 'v.pan_no')
            ->get();

        $sectionWise = $base()
            ->join('tds_sections as s', 't.tds_section_id', '=', 's.id')
            ->selectRaw('s.id, s.section_code, s.nature_of_payment, ' . $sums)
            ->groupBy('t.tds_section_id', 's.id', 's.section_code', 's.nature_of_payment')
            ->get();

        $financialYearsList = $this->financialYearsList();

        return view('society.tds.ledger', compact('deducteeWise', 'sectionWise', 'financialYearId', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ Challan management */

    public function challans()
    {
        $challansList = DB::table('tds_challans')
            ->where('society_id', $this->societyId())
            ->orderByDesc('financial_year_id')->orderByDesc('month')
            ->get();

        return view('society.tds.challans', compact('challansList'));
    }

    public function generateChallan(Request $request)
    {
        $societyId = $this->societyId();
        $society = $this->society();
        $financialYearId = $this->fyId();
        $tds = $this->tds();

        if ($request->isMethod('post')) {
            $month = intval($request->input('month'));
            $selectedTxnIds = !empty($request->input('txn_ids')) ? $request->input('txn_ids') : [];
            $selectedTxnIds = array_filter((array) $selectedTxnIds);

            if (empty($selectedTxnIds)) {
                return redirect()->route('society.tdsGenerateChallan')->with('error', 'Please select at least one TDS transaction to include in the challan.');
            }

            $tanNo = $society->tan_no ?? null;
            if (empty($tanNo)) {
                return redirect()->route('society.tdsGenerateChallan')->with('error', 'TAN is not set for this Society. Please update Society Identity first.');
            }

            $txns = DB::table('tds_transactions')
                ->whereIn('id', $selectedTxnIds)
                ->where('society_id', $societyId)
                ->where('is_reversed', 0)
                ->whereNull('tds_challan_id')
                ->get();
            if ($txns->isEmpty()) {
                return redirect()->route('society.tdsGenerateChallan')->with('error', 'Selected transactions are invalid or already grouped into a challan.');
            }

            $totalTds = 0;
            foreach ($txns as $t) {
                $totalTds += $t->tds_amount;
            }
            $deductionDateSample = $txns[0]->deduction_date;
            $assessmentYear = TdsService::assessmentYearFromDate($deductionDateSample);
            $quarterMonth = intval(date('n', strtotime($deductionDateSample)));
            $quarter = $quarterMonth >= 4 && $quarterMonth <= 6 ? 'Q1' : ($quarterMonth >= 7 && $quarterMonth <= 9 ? 'Q2' : ($quarterMonth >= 10 && $quarterMonth <= 12 ? 'Q3' : 'Q4'));

            // Reuse an existing draft challan for the same TAN+FY+Month if one is still open (duplicate challan prevention)
            $existingChallan = DB::table('tds_challans')
                ->where('society_id', $societyId)->where('tan_no', $tanNo)
                ->where('financial_year_id', $financialYearId)->where('month', $month)
                ->whereIn('payment_status', ['Pending', 'Challan Prepared', 'Payment Pending'])
                ->first();

            if (!empty($existingChallan)) {
                $challanId = $existingChallan->id;
                $newTotal = $existingChallan->total_tds_amount + $totalTds;
                $newCount = $existingChallan->deductee_count + count($txns);
                DB::table('tds_challans')->where('id', $challanId)->update(['total_tds_amount' => $newTotal, 'deductee_count' => $newCount, 'udate' => $this->now()]);
                $tds->writeAuditLog('tds_challans', $challanId, 'update', TdsService::cakeRow($existingChallan), ['total_tds_amount' => $newTotal, 'deductee_count' => $newCount]);
            } else {
                $challanData = [
                    'society_id' => $societyId,
                    'tan_no' => $tanNo,
                    'financial_year_id' => $financialYearId,
                    'assessment_year' => $assessmentYear,
                    'quarter' => $quarter,
                    'month' => $month,
                    'major_head' => !empty($request->input('major_head')) ? $request->input('major_head') : null,
                    'minor_head' => !empty($request->input('minor_head')) ? $request->input('minor_head') : null,
                    'total_tds_amount' => $totalTds,
                    'deductee_count' => count($txns),
                    'payment_status' => 'Challan Prepared',
                    'prepared_by' => (string) Auth::id(),
                    'prepared_at' => $this->now(),
                    'cdate' => $this->now(),
                    'udate' => $this->now(),
                ];
                // TdsChallan::$validate (TAN format, month range) - CakePHP's save() silently returned false
                if (TdsService::validateChallan($challanData)) {
                    return redirect()->route('society.tdsGenerateChallan')->with('error', 'Could not create the challan: ' . TdsService::lastValidationMessage(TdsService::validateChallan($challanData)));
                }
                $challanId = DB::table('tds_challans')->insertGetId($challanData);
                $tds->writeAuditLog('tds_challans', $challanId, 'create', null, ['total_tds_amount' => $totalTds, 'deductee_count' => count($txns)]);
            }

            foreach ($txns as $t) {
                TdsChallanTransaction::create(['tds_challan_id' => $challanId, 'tds_transaction_id' => $t->id, 'cdate' => $this->now()]);
                DB::table('tds_transactions')->where('id', $t->id)->update(['tds_challan_id' => $challanId, 'challan_status' => 'Challan Prepared', 'udate' => $this->now()]);
            }

            return redirect()->route('society.tdsChallans')->with('success', 'Challan draft prepared successfully with ' . count($txns) . ' transaction(s).');
        }

        $pendingTxns = $this->transactionQuery()
            ->where('t.society_id', $societyId)->where('t.is_reversed', 0)->whereNull('t.tds_challan_id')->where('t.financial_year_id', $financialYearId)
            ->orderBy('t.deduction_date')->get();

        return view('society.tds.generate-challan', compact('pendingTxns', 'society'));
    }

    public function updateChallan(Request $request, $id = null)
    {
        $challan = DB::table('tds_challans')->where('id', $id)->where('society_id', $this->societyId())->first();
        if (empty($challan)) {
            return redirect()->route('society.tdsChallans')->with('error', 'Challan not found.');
        }

        $c = (array) $challan;
        if ($request->isMethod('post')) {
            $old = TdsService::cakeRow($challan);
            $newData = [
                'id' => $id,
                'challan_number' => (string) $request->input('challan_number'),
                'bsr_code' => (string) $request->input('bsr_code'),
                'challan_date' => (string) $request->input('challan_date'),
                'cin' => (string) $request->input('cin'),
                'payment_status' => (string) $request->input('payment_status'),
                'remarks' => (string) $request->input('remarks'),
                'udate' => $this->now(),
            ];
            if ($newData['payment_status'] == 'Challan Verified' && $old['payment_status'] != 'Challan Verified') {
                $newData['verified_by'] = (string) Auth::id();
                $newData['verified_at'] = $this->now();
            }

            $saved = false;
            if (in_array($newData['payment_status'], self::CHALLAN_STATUSES, true)) {
                $write = TdsService::cakeWrite($newData, self::CHALLAN_STRING_COLS);
                unset($write['id']);
                DB::table('tds_challans')->where('id', $id)->update($write);
                $saved = true;
            }

            if ($saved) {
                $this->tds()->writeAuditLog('tds_challans', $id, 'update', $old, $newData);
                // propagate status to member transactions
                DB::table('tds_transactions')->where('tds_challan_id', $id)->update(['challan_status' => $newData['payment_status'], 'udate' => $this->now()]);

                return redirect()->route('society.tdsChallans')->with('success', 'Challan updated successfully.');
            }
            session()->now('error', 'Could not update challan.');
        }

        $challanTxns = $this->transactionQuery()->where('t.tds_challan_id', $id)->get();
        return view('society.tds.update-challan', ['c' => $c, 'challanTxns' => $challanTxns]);
    }

    /* ------------------------------------------------------------------ Certificates */

    public function certificates()
    {
        $certList = DB::table('tds_certificates as c')
            ->leftJoin('vendor_details as v', 'v.id', '=', 'c.vendor_detail_id')
            ->leftJoin('financial_year_master as f', 'f.id', '=', 'c.financial_year_id')
            ->where('c.society_id', $this->societyId())
            ->orderByDesc('c.generated_at')
            ->select('c.*', 'v.contact_person_name', 'f.year as financial_year')
            ->get();
        $vendorList = $this->tds()->deducteeVendorList();
        $financialYearsList = $this->financialYearsList();

        return view('society.tds.certificates', compact('certList', 'vendorList', 'financialYearsList'));
    }

    public function generateCertificate(Request $request)
    {
        $vendorDetailId = $request->input('vendor_detail_id');
        $financialYearId = $request->input('financial_year_id');

        $totalTds = DB::table('tds_transactions')
            ->where('society_id', $this->societyId())->where('vendor_detail_id', $vendorDetailId)
            ->where('financial_year_id', $financialYearId)->where('is_reversed', 0)
            ->selectRaw('SUM(tds_amount) as total_tds')->value('total_tds');
        $totalTds = isset($totalTds) ? $totalTds : 0;

        TdsCertificate::create([
            'society_id' => $this->societyId(),
            'vendor_detail_id' => $vendorDetailId,
            'financial_year_id' => $financialYearId,
            'quarter' => 'Full Year',
            'total_tds_amount' => $totalTds,
            'generated_by' => Auth::id(),
            'generated_at' => $this->now(),
            'cdate' => $this->now(),
        ]);

        return redirect()->route('society.tdsCertificatePdf', [$vendorDetailId, $financialYearId]);
    }

    public function certificatePdf($vendorDetailId = null, $financialYearId = null)
    {
        $society = $this->society();
        $vendor = DB::table('vendor_details')->where('id', $vendorDetailId)->where('society_id', $this->societyId())->first();
        $financialYear = DB::table('financial_year_master')->where('id', $financialYearId)->first();
        $txns = $this->transactionQuery()
            ->where('t.society_id', $this->societyId())->where('t.vendor_detail_id', $vendorDetailId)
            ->where('t.financial_year_id', $financialYearId)->where('t.is_reversed', 0)
            ->orderBy('t.deduction_date')->get();
        $totalTds = 0;
        foreach ($txns as $t) {
            $totalTds += $t->tds_amount;
        }

        return $this->pdf(
            view('society.tds.certificate-pdf', compact('society', 'vendor', 'financialYear', 'txns', 'totalTds'))->render(),
            'TDS_Certificate_' . date('Ymd_His') . '.pdf'
        );
    }

    protected function pdf(string $html, string $filename)
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
