<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\FinancialYearMaster;
use App\Models\Society;
use App\Services\Gst\GstService;
use App\Services\Tds\TdsService;
use Dompdf\Dompdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Port of CakePHP GstController (GST Management & Return Reporting module, Phase 1 + Phase 2): dashboard, GST master,
 * HSN/SAC master, outward register (+PDF/Excel, classify), input register (+PDF/Excel, ITC classify), ITC summary,
 * liability, ledger, advance receipts (+adjust), credit/debit notes, payments/challan, reconciliation, return status,
 * return reports, year-end report.
 * Society scoping: society id == Auth::id() (Cake: Session Auth.User.id); financial year: session fy.year_id.
 * Like the CakePHP module this posts no accounting entries - it reads member_bill_summaries / vendor_bill_details
 * and keeps its own extension tables.
 */
class GstController extends Controller
{
    /** varchar / text / enum columns - CakePHP keeps '' there, every other column type gets NULL for '' */
    private const MASTER_TEXT = ['gstin', 'legal_name', 'trade_name', 'registered_address', 'state', 'state_code', 'registration_type', 'gst_return_frequency', 'default_tax_type', 'default_place_of_supply'];
    private const HSN_TEXT = ['code', 'code_type', 'description', 'taxability_type'];
    private const OUTWARD_TEXT = ['place_of_supply', 'supply_type', 'remarks'];
    private const ITC_TEXT = ['itc_eligibility', 'ineligible_reason', 'itc_claim_status', 'claim_period', 'remarks'];
    private const ADVANCE_TEXT = ['party_type', 'party_name', 'gstin', 'pan_no', 'remarks'];
    private const NOTE_TEXT = ['note_type', 'original_invoice_type', 'original_invoice_no', 'party_type', 'party_name', 'gstin', 'reason', 'remarks'];
    private const PAYMENT_TEXT = ['challan_number', 'cin_cpin', 'bank_portal_reference', 'payment_status', 'remarks'];
    private const ADJUSTMENT_TEXT = ['period_type', 'period_value', 'remarks'];
    private const RECON_TEXT = ['period_type', 'period_value', 'status', 'remarks'];

    private function societyId()
    {
        return (string) Auth::id();
    }

    private function fyId()
    {
        $id = session('fy.year_id');
        return $id === null ? null : (string) $id;
    }

    private function gst(): GstService
    {
        return new GstService($this->societyId(), $this->fyId(), (string) Auth::id());
    }

    private function now(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    private function society()
    {
        return Society::where('user_id', $this->societyId())->first();
    }

    private function financialYearsList()
    {
        return FinancialYearMaster::orderBy('id')->pluck('year', 'id');
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

    /** "queryFy": the financial_year_id query parameter, else the session's current financial year */
    private function queryFy(Request $request)
    {
        return !empty($request->query('financial_year_id')) ? $request->query('financial_year_id') : $this->fyId();
    }

    private function hsnActiveList()
    {
        return DB::table('gst_hsn_sac_master')->where('society_id', $this->societyId())->where('status', 1)->pluck('code', 'id');
    }

    /* ------------------------------------------------------------------ Dashboard */

    public function dashboard()
    {
        $societyId = $this->societyId();
        $fy = $this->fyId();
        $gst = $this->gst();

        $outward = DB::table('member_bill_summaries as m')
            ->where('m.society_id', $societyId)->where('m.financial_year_id', $fy)
            ->selectRaw('ROUND(SUM(m.monthly_bill_amount), 2) as taxable_turnover, ROUND(SUM(m.cgst_total), 2) as output_cgst, ROUND(SUM(m.sgst_total), 2) as output_sgst, ROUND(SUM(m.igst_total), 2) as output_igst, ROUND(SUM(m.tax_total), 2) as output_gst')
            ->first();
        $outputGst = isset($outward->output_gst) ? floatval($outward->output_gst) : 0;
        $taxableTurnover = isset($outward->taxable_turnover) ? floatval($outward->taxable_turnover) : 0;

        $inward = DB::table('vendor_bill_details as d')
            ->join('vendor_bills as b', 'd.vendor_bill_id', '=', 'b.id')
            ->leftJoin('gst_itc_classification as c', 'd.id', '=', 'c.vendor_bill_detail_id')
            ->where('b.society_id', $societyId)->where('b.financial_year_id', $fy)
            ->selectRaw('SUM(d.sgst_amount + d.cgst_amount + d.igst_amount) as total_input_gst, '
                . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE (d.sgst_amount + d.cgst_amount + d.igst_amount) END) as eligible_itc, '
                . 'SUM(CASE WHEN c.itc_claim_status = "Claimed" THEN (d.sgst_amount + d.cgst_amount + d.igst_amount) ELSE 0 END) as itc_claimed')
            ->first();
        $eligibleItc = isset($inward->eligible_itc) ? floatval($inward->eligible_itc) : 0;
        $itcClaimed = isset($inward->itc_claimed) ? floatval($inward->itc_claimed) : 0;
        $itcBalance = $eligibleItc - $itcClaimed;

        $netLiability = $outputGst - $eligibleItc;

        $advance = DB::table('gst_advance_receipts')
            ->where('society_id', $societyId)->where('financial_year_id', $fy)->where('adjustment_status', '!=', 'Fully Adjusted')
            ->selectRaw('SUM(total_gst) as advance_gst')->first();
        $advanceGst = isset($advance->advance_gst) ? floatval($advance->advance_gst) : 0;

        $paid = DB::table('gst_payments')
            ->where('society_id', $societyId)->where('financial_year_id', $fy)->where('payment_status', 'Paid')
            ->selectRaw('SUM(cgst + sgst + igst) as gst_paid')->first();
        $gstPaid = isset($paid->gst_paid) ? floatval($paid->gst_paid) : 0;
        $gstOutstanding = $netLiability - $gstPaid;

        $currentPeriodValue = date('F');
        $returnStatusRow = DB::table('gst_return_status')->where([
            'society_id' => $societyId, 'financial_year_id' => $fy, 'period_type' => 'Month', 'period_value' => $currentPeriodValue,
        ])->first();
        $returnStatus = !empty($returnStatusRow->status) ? $returnStatusRow->status : 'Draft';

        $gstMaster = $gst->getGstMaster();
        $currentPeriod = date('F Y');

        return view('society.gst.dashboard', compact('currentPeriod', 'currentPeriodValue', 'taxableTurnover', 'outputGst', 'eligibleItc', 'itcClaimed', 'itcBalance', 'netLiability', 'advanceGst', 'gstPaid', 'gstOutstanding', 'returnStatus', 'gstMaster'));
    }

    /* ------------------------------------------------------------------ GST Master */

    public function gstMasterSetup(Request $request)
    {
        $societyId = $this->societyId();
        $existing = $this->gst()->getGstMaster();
        $d = !empty($existing) ? (array) $existing : [];

        if ($request->isMethod('post')) {
            $data = $this->postedFields($request, ['gstin', 'legal_name', 'trade_name', 'registered_address', 'state', 'state_code', 'registration_type', 'registration_date', 'gst_return_frequency', 'default_tax_type', 'default_place_of_supply']);
            $data['society_id'] = $societyId;
            $data['udate'] = $this->now();
            if (!empty($existing)) {
                $data['id'] = (string) $existing->id;
            } else {
                $data['cdate'] = $this->now();
            }

            $errors = GstService::validateGstMaster($data);
            $saved = false;
            $savedId = null;
            if (!$errors) {
                $write = TdsService::cakeWrite($data, self::MASTER_TEXT);
                try {
                    if (!empty($existing)) {
                        unset($write['id']);
                        DB::table('gst_master')->where('id', $existing->id)->update($write);
                        $savedId = $existing->id;
                    } else {
                        $savedId = DB::table('gst_master')->insertGetId($write);
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    $saved = false;
                }
            }

            if ($saved) {
                $this->gst()->writeAuditLog('gst_master', $savedId, empty($existing) ? 'create' : 'update', !empty($existing) ? TdsService::cakeRow($existing) : null, $data);
                return redirect()->route('society.gstMasterSetup')->with('success', 'GST Master saved successfully.');
            }
            $err = TdsService::lastValidationMessage($errors);
            session()->now('error', 'Could not save GST Master' . (!empty($err) ? ': ' . $err : '.'));
            $d = $data;
        }

        return view('society.gst.gst-master-setup', compact('d'));
    }

    /* ------------------------------------------------------------------ HSN/SAC master */

    public function hsnMaster()
    {
        $hsnList = DB::table('gst_hsn_sac_master')->where('society_id', $this->societyId())->orderBy('code')->get();

        return view('society.gst.hsn-master', compact('hsnList'));
    }

    public function addHsnMaster(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $d = [];

        if ($request->isMethod('get') && $id) {
            $row = DB::table('gst_hsn_sac_master')->where('id', $id)->where('society_id', $societyId)->first();
            if (empty($row)) {
                return redirect()->route('society.gstHsnMaster')->with('error', 'HSN/SAC record not found.');
            }
            $d = (array) $row;
        }

        if ($request->isMethod('post')) {
            $posted = $this->postedFields($request, ['id', 'code', 'code_type', 'description', 'taxability_type', 'gst_rate', 'cgst_rate', 'sgst_rate', 'igst_rate', 'cess_rate', 'reverse_charge_applicable', 'effective_from', 'effective_to', 'status']);
            $data = $posted;
            $data['society_id'] = $societyId;
            $data['udate'] = $this->now();
            $isNew = empty($data['id']);
            $old = null;
            if ($isNew) {
                $data['cdate'] = $this->now();
            } else {
                $old = DB::table('gst_hsn_sac_master')->where('id', $data['id'])->where('society_id', $societyId)->first();
                if (empty($old)) {
                    return redirect()->route('society.gstHsnMaster')->with('error', 'HSN/SAC record not found.');
                }
            }

            $errors = GstService::validateHsn($data);
            $saved = false;
            $savedId = null;
            if (!$errors) {
                $write = TdsService::cakeWrite($data, self::HSN_TEXT, ['cgst_rate', 'sgst_rate', 'igst_rate', 'cess_rate']);
                try {
                    if ($isNew) {
                        unset($write['id']);
                        $savedId = DB::table('gst_hsn_sac_master')->insertGetId($write);
                    } else {
                        $savedId = $write['id'];
                        unset($write['id']);
                        DB::table('gst_hsn_sac_master')->where('id', $savedId)->update($write);
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    $saved = false;
                }
            }

            if ($saved) {
                $this->gst()->writeAuditLog('gst_hsn_sac_master', $savedId, $isNew ? 'create' : 'update', $old ? TdsService::cakeRow($old) : null, $data);
                return redirect()->route('society.gstHsnMaster')->with('success', 'HSN/SAC master saved successfully.');
            }
            session()->now('error', 'Could not save: ' . TdsService::lastValidationMessage($errors));
            $d = $posted;
        }

        return view('society.gst.add-hsn-master', compact('d'));
    }

    public function toggleHsnStatus($id)
    {
        $row = DB::table('gst_hsn_sac_master')->where('id', $id)->where('society_id', $this->societyId())->first();
        if (empty($row)) {
            return response()->json(['error' => 1, 'error_message' => 'Record not found.']);
        }
        $newStatus = $row->status == 1 ? 0 : 1;
        DB::table('gst_hsn_sac_master')->where('id', $id)->update(['status' => $newStatus]);
        $this->gst()->writeAuditLog('gst_hsn_sac_master', $id, 'status_change', ['status' => (bool) $row->status], ['status' => $newStatus]);

        return response()->json(['error' => 0, 'status' => $newStatus]);
    }

    /* ------------------------------------------------------------------ Outward register */

    private function outwardRows(Request $request, Builder $q, bool $withSelect = true): Builder
    {
        $qs = $request->query();
        $q->where('m.society_id', $this->societyId());
        $q->where('m.financial_year_id', !empty($qs['financial_year_id']) ? $qs['financial_year_id'] : $this->fyId());
        if (!empty($qs['month'])) {
            $q->whereIn('m.month', GstService::monthValues($qs['month']));
        }
        if (!empty($qs['date_from']) && !empty($qs['date_to'])) {
            $q->whereRaw('m.bill_generated_date BETWEEN ? AND ?', [$qs['date_from'], $qs['date_to']]);
        }
        if (!empty($qs['member_id'])) {
            $q->where('m.member_id', $qs['member_id']);
        }
        if (!empty($qs['gstin'])) {
            $q->where('mi.GSTIN', 'LIKE', '%' . $qs['gstin'] . '%');
        }
        if (!empty($qs['hsn_sac_id'])) {
            $q->where('o.hsn_sac_id', $qs['hsn_sac_id']);
        }
        if (!empty($qs['supply_type'])) {
            $q->where('o.supply_type', $qs['supply_type']);
        }

        return $q;
    }

    private function outwardList(Request $request)
    {
        $gst = $this->gst();
        $rows = $this->outwardRows($request, $gst->outwardQuery())
            ->select($gst->outwardSelect())->orderBy('m.bill_generated_date')->get();

        return $gst->enrichOutwardRows($rows, $gst->fallbackCombinedRate());
    }

    public function outwardRegister(Request $request)
    {
        $gst = $this->gst();
        $rows = $this->outwardList($request);
        $totals = $this->outwardRows($request, $gst->outwardQuery())
            ->selectRaw('ROUND(SUM(m.cgst_total), 2) as total_cgst, ROUND(SUM(m.sgst_total), 2) as total_sgst, ROUND(SUM(m.igst_total), 2) as total_igst, ROUND(SUM(m.tax_total), 2) as total_gst, ROUND(SUM(m.monthly_bill_amount), 2) as total_invoice')
            ->first();
        $financialYearsList = $this->financialYearsList();
        $hsnList = DB::table('gst_hsn_sac_master')->where('society_id', $this->societyId())->pluck('code', 'id');
        $qs = $request->query();

        return view('society.gst.outward-register', compact('rows', 'totals', 'financialYearsList', 'hsnList', 'qs'));
    }

    public function outwardRegisterPdf(Request $request)
    {
        $rows = $this->outwardList($request);
        $totals = $this->outwardRows($request, $this->gst()->outwardQuery())
            ->selectRaw('ROUND(SUM(m.cgst_total), 2) as total_cgst, ROUND(SUM(m.sgst_total), 2) as total_sgst, ROUND(SUM(m.igst_total), 2) as total_igst, ROUND(SUM(m.tax_total), 2) as total_gst')
            ->first();
        $societyDetails = $this->society();

        return $this->pdf(view('society.gst.outward-register-pdf', compact('rows', 'totals', 'societyDetails'))->render(), 'GST_Outward_Register_' . date('Ymd_His') . '.pdf');
    }

    public function outwardRegisterExcel(Request $request)
    {
        $rows = $this->outwardList($request);

        $headers = ['Sr No', 'Invoice No', 'Invoice Date', 'Member Name', 'GSTIN', 'Place of Supply', 'HSN/SAC', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total GST', 'Invoice Total', 'B2B/B2C'];
        $data = [];
        $sr = 1;
        foreach ($rows as $m) {
            $data[] = [
                $sr++, $m->bill_no, $m->bill_generated_date,
                $m->member_name, isset($m->GSTIN) ? $m->GSTIN : '',
                isset($m->place_of_supply) ? $m->place_of_supply : '',
                isset($m->hsn_code) ? $m->hsn_code : '',
                $m->computed['taxable_value'], $m->cgst_total, $m->sgst_total, $m->igst_total, $m->tax_total,
                $m->monthly_bill_amount, $m->computed['supply_type'],
            ];
        }

        return $this->excel('Outward Register', $headers, $data, 'GST_Outward_Register_' . date('Ymd_His') . '.xlsx');
    }

    public function classifyOutward(Request $request, $memberBillSummaryId = null)
    {
        $societyId = $this->societyId();
        $bill = DB::table('member_bill_summaries')->where('id', $memberBillSummaryId)->where('society_id', $societyId)
            ->select('id', 'bill_no', 'bill_generated_date', DB::raw('CAST(tax_total AS CHAR) AS tax_total'))->first();
        if (empty($bill)) {
            return redirect()->route('society.gstOutwardRegister')->with('error', 'Bill not found.');
        }
        $existing = DB::table('gst_outward_supply_meta')->where('member_bill_summary_id', $memberBillSummaryId)->first();
        $d = !empty($existing) ? (array) $existing : ['member_bill_summary_id' => $memberBillSummaryId];

        if ($request->isMethod('post')) {
            $posted = $this->postedFields($request, ['hsn_sac_id', 'place_of_supply', 'supply_type', 'reverse_charge', 'remarks']);
            $data = $posted;
            $data['society_id'] = $societyId;
            $data['member_bill_summary_id'] = (string) $memberBillSummaryId;
            $data['udate'] = $this->now();
            if (!empty($existing)) {
                $data['id'] = (string) $existing->id;
            } else {
                $data['cdate'] = $this->now();
            }

            $saved = false;
            $savedId = null;
            $write = TdsService::cakeWrite($data, self::OUTWARD_TEXT);
            try {
                if (!empty($existing)) {
                    unset($write['id']);
                    DB::table('gst_outward_supply_meta')->where('id', $existing->id)->update($write);
                    $savedId = $existing->id;
                } else {
                    $savedId = DB::table('gst_outward_supply_meta')->insertGetId($write);
                }
                $saved = true;
            } catch (\Illuminate\Database\QueryException $e) {
                $saved = false;
            }

            if ($saved) {
                $this->gst()->writeAuditLog('gst_outward_supply_meta', $savedId, empty($existing) ? 'create' : 'update', !empty($existing) ? TdsService::cakeRow($existing) : null, $data);
                return redirect()->route('society.gstOutwardRegister')->with('success', 'Classification saved successfully.');
            }
            session()->now('error', 'Could not save classification.');
            $d = $posted;
        }

        $hsnList = $this->hsnActiveList();

        return view('society.gst.classify-outward', compact('bill', 'hsnList', 'd'));
    }

    /* ------------------------------------------------------------------ Input / purchase register */

    private function inputRows(Request $request, Builder $q): Builder
    {
        $qs = $request->query();
        $q->where('b.society_id', $this->societyId());
        $q->where('b.financial_year_id', !empty($qs['financial_year_id']) ? $qs['financial_year_id'] : $this->fyId());
        if (!empty($qs['date_from']) && !empty($qs['date_to'])) {
            $q->whereRaw('b.bill_date BETWEEN ? AND ?', [$qs['date_from'], $qs['date_to']]);
        }
        if (!empty($qs['itc_eligibility'])) {
            $q->where('c.itc_eligibility', $qs['itc_eligibility']);
        }

        return $q;
    }

    private function inputList(Request $request)
    {
        $gst = $this->gst();

        return $this->inputRows($request, $gst->inputQuery())->select($gst->inputSelect())->orderBy('b.bill_date')->get();
    }

    public function inputRegister(Request $request)
    {
        $rows = $this->inputList($request);
        $totals = $this->inputRows($request, $this->gst()->inputQuery())->selectRaw(
            'SUM(d.amount) as total_taxable, SUM(d.cgst_amount) as total_cgst, SUM(d.sgst_amount) as total_sgst, SUM(d.igst_amount) as total_igst, '
            . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN (d.sgst_amount+d.cgst_amount+d.igst_amount) ELSE 0 END) as total_ineligible, '
            . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE (d.sgst_amount+d.cgst_amount+d.igst_amount) END) as total_eligible'
        )->first();
        $financialYearsList = $this->financialYearsList();
        $qs = $request->query();

        return view('society.gst.input-register', compact('rows', 'totals', 'financialYearsList', 'qs'));
    }

    public function inputRegisterPdf(Request $request)
    {
        $rows = $this->inputList($request);
        $totals = $this->inputRows($request, $this->gst()->inputQuery())
            ->selectRaw('SUM(d.amount) as total_taxable, SUM(d.cgst_amount) as total_cgst, SUM(d.sgst_amount) as total_sgst, SUM(d.igst_amount) as total_igst')->first();
        $societyDetails = $this->society();

        return $this->pdf(view('society.gst.input-register-pdf', compact('rows', 'totals', 'societyDetails'))->render(), 'GST_Input_Register_' . date('Ymd_His') . '.pdf');
    }

    public function inputRegisterExcel(Request $request)
    {
        $rows = $this->inputList($request);

        $headers = ['Supplier', 'Supplier GSTIN', 'Invoice No', 'Invoice Date', 'HSN/SAC', 'Taxable Amount', 'CGST', 'SGST', 'IGST', 'Total Invoice', 'ITC Eligibility'];
        $data = [];
        foreach ($rows as $v) {
            $totalInvoice = $v->amount + $v->sgst_amount + $v->cgst_amount + $v->igst_amount;
            $data[] = [
                isset($v->title) ? $v->title : '',
                isset($v->gst_no) ? $v->gst_no : '',
                $v->bill_no, $v->bill_date, $v->hsn_sac,
                $v->amount, $v->cgst_amount, $v->sgst_amount, $v->igst_amount, $totalInvoice,
                isset($v->itc_eligibility) ? $v->itc_eligibility : 'Eligible',
            ];
        }

        return $this->excel('Input Register', $headers, $data, 'GST_Input_Register_' . date('Ymd_His') . '.xlsx');
    }

    public function classifyItc(Request $request, $vendorBillDetailId = null)
    {
        $societyId = $this->societyId();
        $line = DB::table('vendor_bill_details as d')
            ->join('vendor_bills as b', 'd.vendor_bill_id', '=', 'b.id')
            ->where('d.id', $vendorBillDetailId)->where('b.society_id', $societyId)
            ->select('d.id', 'd.amount', 'd.hsn_sac', 'd.cgst_amount', 'd.sgst_amount', 'd.igst_amount', 'b.id as bill_id', 'b.bill_no')->first();
        if (empty($line)) {
            return redirect()->route('society.gstInputRegister')->with('error', 'Purchase line not found.');
        }
        $existing = DB::table('gst_itc_classification')->where('vendor_bill_detail_id', $vendorBillDetailId)->first();
        $d = !empty($existing) ? (array) $existing : ['vendor_bill_detail_id' => $vendorBillDetailId];

        if ($request->isMethod('post')) {
            $posted = $this->postedFields($request, ['itc_eligibility', 'ineligible_reason', 'itc_claim_status', 'claim_period', 'remarks']);
            $data = $posted;
            $data['society_id'] = $societyId;
            $data['vendor_bill_detail_id'] = (string) $vendorBillDetailId;
            $data['udate'] = $this->now();
            if (!empty($existing)) {
                $data['id'] = (string) $existing->id;
            } else {
                $data['cdate'] = $this->now();
            }

            $saved = false;
            $savedId = null;
            $write = TdsService::cakeWrite($data, self::ITC_TEXT);
            try {
                if (!empty($existing)) {
                    unset($write['id']);
                    DB::table('gst_itc_classification')->where('id', $existing->id)->update($write);
                    $savedId = $existing->id;
                } else {
                    $savedId = DB::table('gst_itc_classification')->insertGetId($write);
                }
                $saved = true;
            } catch (\Illuminate\Database\QueryException $e) {
                $saved = false;
            }

            if ($saved) {
                $this->gst()->writeAuditLog('gst_itc_classification', $savedId, empty($existing) ? 'create' : 'update', !empty($existing) ? TdsService::cakeRow($existing) : null, $data);
                return redirect()->route('society.gstInputRegister')->with('success', 'ITC classification saved successfully.');
            }
            session()->now('error', 'Could not save classification.');
            $d = $posted;
        }

        return view('society.gst.classify-itc', compact('line', 'd'));
    }

    /* ------------------------------------------------------------------ ITC summary */

    public function itcSummary(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);
        $gst = $this->gst();
        $base = fn () => $gst->inputQuery()->where('b.society_id', $societyId)->where('b.financial_year_id', $financialYearId);

        $summary = $base()->selectRaw(
            'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.cgst_amount END) as cgst_itc, '
            . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.sgst_amount END) as sgst_itc, '
            . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.igst_amount END) as igst_itc, '
            . 'SUM(CASE WHEN c.itc_claim_status = "Claimed" THEN (d.cgst_amount+d.sgst_amount+d.igst_amount) ELSE 0 END) as itc_claimed, '
            . 'SUM(CASE WHEN c.itc_claim_status = "Reversed" THEN (d.cgst_amount+d.sgst_amount+d.igst_amount) ELSE 0 END) as itc_reversed'
        )->first();

        $vendorWise = $base()->selectRaw(
            'l.title, v.gst_no, SUM(d.cgst_amount) as cgst, SUM(d.sgst_amount) as sgst, SUM(d.igst_amount) as igst, '
            . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN (d.cgst_amount+d.sgst_amount+d.igst_amount) ELSE 0 END) as ineligible'
        )->groupBy('b.vendor_ledger_head_id')->get();

        $financialYearsList = $this->financialYearsList();

        return view('society.gst.itc-summary', compact('summary', 'vendorWise', 'financialYearId', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ Liability */

    public function liability(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);
        $periodType = !empty($request->query('period_type')) ? $request->query('period_type') : 'Month';
        $periodValue = !empty($request->query('period_value')) ? $request->query('period_value') : date('F');

        if ($request->isMethod('post')) {
            $in = $request->post();
            $existingAdj = DB::table('gst_period_adjustments')->where([
                'society_id' => $societyId, 'financial_year_id' => $financialYearId,
                'period_type' => $in['period_type'] ?? null, 'period_value' => $in['period_value'] ?? null,
            ])->first();
            $saveData = [
                'society_id' => $societyId,
                'financial_year_id' => $financialYearId,
                'period_type' => $in['period_type'] ?? null,
                'period_value' => $in['period_value'] ?? null,
                'interest' => floatval($in['interest'] ?? 0),
                'late_fee' => floatval($in['late_fee'] ?? 0),
                'other_adjustment' => floatval($in['other_adjustment'] ?? 0),
                'previous_period_adjustment' => floatval($in['previous_period_adjustment'] ?? 0),
                'remarks' => $in['remarks'] ?? null,
                'created_by' => (string) Auth::id(),
                'udate' => $this->now(),
            ];
            if (!empty($existingAdj)) {
                DB::table('gst_period_adjustments')->where('id', $existingAdj->id)->update(TdsService::cakeWrite($saveData, self::ADJUSTMENT_TEXT));
            } else {
                $saveData['cdate'] = $this->now();
                DB::table('gst_period_adjustments')->insert(TdsService::cakeWrite($saveData, self::ADJUSTMENT_TEXT));
            }

            return redirect()->route('society.gstLiability', ['financial_year_id' => $financialYearId, 'period_type' => $saveData['period_type'], 'period_value' => $saveData['period_value']])
                ->with('success', 'Period adjustments saved.');
        }

        $breakdown = $this->gst()->computeLiabilityBreakdown($societyId, $financialYearId, $periodType, $periodValue);
        $financialYearsList = $this->financialYearsList();

        return view('society.gst.liability', $breakdown + compact('financialYearId', 'periodType', 'periodValue', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ GST ledger */

    public function ledger(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);

        $output = DB::table('member_bill_summaries')
            ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)
            ->selectRaw('ROUND(SUM(cgst_total), 2) as cgst, ROUND(SUM(sgst_total), 2) as sgst, ROUND(SUM(igst_total), 2) as igst')->first();

        $input = $this->gst()->inputQuery()
            ->where('b.society_id', $societyId)->where('b.financial_year_id', $financialYearId)
            ->selectRaw(
                'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.cgst_amount END) as cgst_available, '
                . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.sgst_amount END) as sgst_available, '
                . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.igst_amount END) as igst_available, '
                . 'SUM(CASE WHEN c.itc_claim_status = "Claimed" THEN d.cgst_amount ELSE 0 END) as cgst_claimed, '
                . 'SUM(CASE WHEN c.itc_claim_status = "Claimed" THEN d.sgst_amount ELSE 0 END) as sgst_claimed, '
                . 'SUM(CASE WHEN c.itc_claim_status = "Claimed" THEN d.igst_amount ELSE 0 END) as igst_claimed'
            )->first();

        $paid = DB::table('gst_payments')
            ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->where('payment_status', 'Paid')
            ->selectRaw('SUM(cgst) as cgst_paid, SUM(sgst) as sgst_paid, SUM(igst) as igst_paid')->first();
        $cgstPaid = isset($paid->cgst_paid) ? floatval($paid->cgst_paid) : 0;
        $sgstPaid = isset($paid->sgst_paid) ? floatval($paid->sgst_paid) : 0;
        $igstPaid = isset($paid->igst_paid) ? floatval($paid->igst_paid) : 0;

        $ledgerRows = [
            ['name' => 'Output CGST', 'opening' => 0, 'accrued' => floatval($output->cgst), 'utilized' => $cgstPaid, 'note' => 'Utilized = paid via GST Payment/Challan'],
            ['name' => 'Output SGST', 'opening' => 0, 'accrued' => floatval($output->sgst), 'utilized' => $sgstPaid, 'note' => 'Utilized = paid via GST Payment/Challan'],
            ['name' => 'Output IGST', 'opening' => 0, 'accrued' => floatval($output->igst), 'utilized' => $igstPaid, 'note' => 'Utilized = paid via GST Payment/Challan'],
            ['name' => 'Input CGST (ITC)', 'opening' => 0, 'accrued' => floatval($input->cgst_available), 'utilized' => floatval($input->cgst_claimed), 'note' => 'Claimed via Input Register'],
            ['name' => 'Input SGST (ITC)', 'opening' => 0, 'accrued' => floatval($input->sgst_available), 'utilized' => floatval($input->sgst_claimed), 'note' => 'Claimed via Input Register'],
            ['name' => 'Input IGST (ITC)', 'opening' => 0, 'accrued' => floatval($input->igst_available), 'utilized' => floatval($input->igst_claimed), 'note' => 'Claimed via Input Register'],
        ];
        foreach ($ledgerRows as &$r) {
            $r['closing'] = $r['opening'] + $r['accrued'] - $r['utilized'];
        }
        unset($r);

        $financialYearsList = $this->financialYearsList();

        return view('society.gst.ledger', compact('ledgerRows', 'financialYearId', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ Advance receipts */

    public function advanceReceipts(Request $request)
    {
        $financialYearId = $this->queryFy($request);
        $rows = DB::table('gst_advance_receipts')
            ->where('society_id', $this->societyId())->where('financial_year_id', $financialYearId)
            ->orderByDesc('receipt_date')->get();
        $financialYearsList = $this->financialYearsList();

        return view('society.gst.advance-receipts', compact('rows', 'financialYearId', 'financialYearsList'));
    }

    public function addAdvanceReceipt(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->fyId();
        $gst = $this->gst();
        $d = [];

        if ($request->isMethod('get') && $id) {
            $row = DB::table('gst_advance_receipts')->where('id', $id)->where('society_id', $societyId)->first();
            if (empty($row)) {
                return redirect()->route('society.gstAdvanceReceipts')->with('error', 'Advance receipt not found.');
            }
            $d = (array) $row;
        }

        if ($request->isMethod('post')) {
            $in = $request->post();
            $posted = $this->postedFields($request, ['id', 'party_type', 'member_id', 'vendor_detail_id', 'party_name', 'gstin', 'pan_no', 'receipt_date', 'amount_received', 'gst_rate', 'supply_type', 'remarks']);
            $dd = $posted;
            $amountReceived = floatval($dd['amount_received'] ?? 0);
            $rate = floatval($dd['gst_rate'] ?? 0);
            $taxableValue = $rate > 0 ? round($amountReceived / (1 + $rate / 100), 2) : $amountReceived;
            $totalGst = round($amountReceived - $taxableValue, 2);
            $cgstAmount = $sgstAmount = $igstAmount = 0;
            if (($dd['supply_type'] ?? null) == 'Inter-state') {
                $igstAmount = $totalGst;
            } else {
                $cgstAmount = round($totalGst / 2, 2);
                $sgstAmount = $totalGst - $cgstAmount;
            }

            $isNew = empty($dd['id']);
            $saveData = [
                'society_id' => $societyId,
                'financial_year_id' => $financialYearId,
                'receipt_date' => $dd['receipt_date'] ?? null,
                'party_type' => $dd['party_type'] ?? null,
                'member_id' => ($dd['party_type'] ?? null) == 'Member' ? ($dd['member_id'] ?? null) : null,
                'vendor_detail_id' => ($dd['party_type'] ?? null) == 'Vendor' ? ($dd['vendor_detail_id'] ?? null) : null,
                'party_name' => $dd['party_name'] ?? null,
                'gstin' => $dd['gstin'] ?? null,
                'pan_no' => $dd['pan_no'] ?? null,
                'amount_received' => $amountReceived,
                'taxable_value' => $taxableValue,
                'gst_rate' => $rate,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'total_gst' => $totalGst,
                'remarks' => $dd['remarks'] ?? null,
                'created_by' => (string) Auth::id(),
                'udate' => $this->now(),
            ];
            if ($isNew) {
                $saveData['receipt_no'] = $gst->nextSequenceNumber('gst_advance_receipts', 'receipt_no', $financialYearId);
                $saveData['adjustment_status'] = 'Unadjusted';
                $saveData['cdate'] = $this->now();
            } else {
                $saveData['id'] = $dd['id'];
                if (!DB::table('gst_advance_receipts')->where('id', $dd['id'])->where('society_id', $societyId)->exists()) {
                    return redirect()->route('society.gstAdvanceReceipts')->with('error', 'Advance receipt not found.');
                }
            }

            $saved = false;
            $savedId = null;
            $write = TdsService::cakeWrite($saveData, self::ADVANCE_TEXT);
            try {
                if ($isNew) {
                    $savedId = DB::table('gst_advance_receipts')->insertGetId($write);
                } else {
                    unset($write['id']);
                    DB::table('gst_advance_receipts')->where('id', $saveData['id'])->update($write);
                    $savedId = $saveData['id'];
                }
                $saved = true;
            } catch (\Illuminate\Database\QueryException $e) {
                $saved = false;
            }

            if ($saved) {
                $gst->writeAuditLog('gst_advance_receipts', $savedId, $isNew ? 'create' : 'update', null, $saveData);
                return redirect()->route('society.gstAdvanceReceipts')->with('success', 'Advance receipt saved successfully. GST Amount: Rs. ' . number_format($totalGst, 2));
            }
            session()->now('error', 'Could not save advance receipt.');
            $d = $posted;
        }

        $memberList = DB::table('members')->where('society_id', $societyId)->pluck('member_name', 'id');
        $vendorList = DB::table('vendor_details')->where('society_id', $societyId)->pluck('contact_person_name', 'id');

        return view('society.gst.add-advance-receipt', compact('d', 'memberList', 'vendorList'));
    }

    public function adjustAdvance(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $receipt = DB::table('gst_advance_receipts')->where('id', $id)->where('society_id', $societyId)->first();
        if (empty($receipt)) {
            return redirect()->route('society.gstAdvanceReceipts')->with('error', 'Advance receipt not found.');
        }

        $adjustments = DB::table('gst_advance_adjustments')->where('gst_advance_receipt_id', $id)->orderBy('adjustment_date')->get();
        $totalAdjusted = 0;
        foreach ($adjustments as $adj) {
            $totalAdjusted += $adj->adjusted_amount;
        }
        $balance = $receipt->amount_received - $totalAdjusted;

        if ($request->isMethod('post')) {
            $in = $request->post();
            $adjustedAmount = floatval($in['adjusted_amount'] ?? 0);
            if ($adjustedAmount <= 0 || $adjustedAmount > $balance) {
                session()->now('error', 'Adjustment amount must be between 0 and the remaining balance (Rs. ' . number_format($balance, 2) . ').');
            } else {
                $rate = floatval($receipt->gst_rate);
                $adjustedTaxableValue = $rate > 0 ? round($adjustedAmount / (1 + $rate / 100), 2) : $adjustedAmount;
                $adjustedGstAmount = round($adjustedAmount - $adjustedTaxableValue, 2);

                DB::table('gst_advance_adjustments')->insert(TdsService::cakeWrite([
                    'society_id' => $societyId,
                    'gst_advance_receipt_id' => $id,
                    'adjusted_against_type' => $in['adjusted_against_type'] ?? null,
                    'adjusted_against_id' => $in['adjusted_against_id'] ?? null,
                    'adjusted_against_invoice_no' => $in['adjusted_against_invoice_no'] ?? null,
                    'adjustment_date' => $in['adjustment_date'] ?? null,
                    'adjusted_amount' => $adjustedAmount,
                    'adjusted_taxable_value' => $adjustedTaxableValue,
                    'adjusted_gst_amount' => $adjustedGstAmount,
                    'remarks' => $in['remarks'] ?? null,
                    'created_by' => (string) Auth::id(),
                    'cdate' => $this->now(),
                ], ['adjusted_against_type', 'adjusted_against_invoice_no', 'remarks'], ['adjusted_against_id', 'adjustment_date']));

                $newTotalAdjusted = $totalAdjusted + $adjustedAmount;
                $newStatus = $newTotalAdjusted >= $receipt->amount_received ? 'Fully Adjusted' : 'Partially Adjusted';
                DB::table('gst_advance_receipts')->where('id', $id)->update(['adjustment_status' => $newStatus]);

                $this->gst()->writeAuditLog('gst_advance_receipts', $id, 'adjust', null, ['adjusted_amount' => $adjustedAmount]);

                return redirect()->route('society.gstAdjustAdvance', $id)->with('success', 'Advance adjusted successfully.');
            }
        }

        return view('society.gst.adjust-advance', ['r' => (array) $receipt, 'adjustments' => $adjustments, 'balance' => $balance]);
    }

    /* ------------------------------------------------------------------ Credit / debit notes */

    public function creditDebitNotes(Request $request)
    {
        $financialYearId = $this->queryFy($request);
        $rows = DB::table('gst_credit_debit_notes')
            ->where('society_id', $this->societyId())->where('financial_year_id', $financialYearId)
            ->orderByDesc('note_date')->get();
        $financialYearsList = $this->financialYearsList();

        return view('society.gst.credit-debit-notes', compact('rows', 'financialYearId', 'financialYearsList'));
    }

    public function addCreditDebitNote(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->fyId();
        $gst = $this->gst();
        $d = [];

        if ($request->isMethod('get') && $id) {
            $row = DB::table('gst_credit_debit_notes')->where('id', $id)->where('society_id', $societyId)->first();
            if (empty($row)) {
                return redirect()->route('society.gstCreditDebitNotes')->with('error', 'Note not found.');
            }
            $d = (array) $row;
        }

        // Prefill from an original member bill referenced via ?member_bill_summary_id=
        if ($request->isMethod('get') && empty($id) && $request->query('member_bill_summary_id')) {
            $bill = DB::table('member_bill_summaries as m')
                ->join('members as mem', 'm.member_id', '=', 'mem.id')
                ->where('m.id', $request->query('member_bill_summary_id'))->where('m.society_id', $societyId)
                ->select('m.id', 'm.bill_no', 'm.bill_generated_date', 'm.member_id', 'mem.member_name')->first();
            if (!empty($bill)) {
                $d['original_invoice_type'] = 'MemberBill';
                $d['original_invoice_id'] = $bill->id;
                $d['original_invoice_no'] = $bill->bill_no;
                $d['original_invoice_date'] = $bill->bill_generated_date;
                $d['party_type'] = 'Member';
                $d['member_id'] = $bill->member_id;
                $d['party_name'] = $bill->member_name;
            }
        }

        if ($request->isMethod('post')) {
            $posted = $this->postedFields($request, ['id', 'note_type', 'note_date', 'original_invoice_type', 'original_invoice_no', 'original_invoice_id', 'original_invoice_date', 'party_type', 'member_id', 'vendor_detail_id', 'party_name', 'gstin', 'hsn_sac_id', 'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'reason', 'remarks']);
            $dd = $posted;
            $taxableAmount = floatval($dd['taxable_amount'] ?? 0);
            $cgstAmount = floatval($dd['cgst_amount'] ?? 0);
            $sgstAmount = floatval($dd['sgst_amount'] ?? 0);
            $igstAmount = floatval($dd['igst_amount'] ?? 0);
            $totalGst = $cgstAmount + $sgstAmount + $igstAmount;
            $totalAmount = $taxableAmount + $totalGst;

            $isNew = empty($dd['id']);
            $saveData = [
                'society_id' => $societyId,
                'financial_year_id' => $financialYearId,
                'note_type' => $dd['note_type'] ?? null,
                'note_date' => $dd['note_date'] ?? null,
                'original_invoice_type' => $dd['original_invoice_type'] ?? null,
                'original_invoice_id' => $dd['original_invoice_id'] ?? null,
                'original_invoice_no' => $dd['original_invoice_no'] ?? null,
                'original_invoice_date' => !empty($dd['original_invoice_date']) ? $dd['original_invoice_date'] : null,
                'party_type' => $dd['party_type'] ?? null,
                'member_id' => ($dd['party_type'] ?? null) == 'Member' ? ($dd['member_id'] ?? null) : null,
                'vendor_detail_id' => ($dd['party_type'] ?? null) == 'Vendor' ? ($dd['vendor_detail_id'] ?? null) : null,
                'party_name' => $dd['party_name'] ?? null,
                'gstin' => $dd['gstin'] ?? null,
                'hsn_sac_id' => !empty($dd['hsn_sac_id']) ? $dd['hsn_sac_id'] : null,
                'taxable_amount' => $taxableAmount,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'igst_amount' => $igstAmount,
                'total_gst' => $totalGst,
                'total_amount' => $totalAmount,
                'reason' => $dd['reason'] ?? null,
                'remarks' => $dd['remarks'] ?? null,
                'created_by' => (string) Auth::id(),
                'udate' => $this->now(),
            ];
            if ($isNew) {
                $saveData['note_no'] = $gst->nextSequenceNumber('gst_credit_debit_notes', 'note_no', $financialYearId, ['note_type' => $dd['note_type'] ?? null]);
                $saveData['cdate'] = $this->now();
            } else {
                $saveData['id'] = $dd['id'];
                if (!DB::table('gst_credit_debit_notes')->where('id', $dd['id'])->where('society_id', $societyId)->exists()) {
                    return redirect()->route('society.gstCreditDebitNotes')->with('error', 'Note not found.');
                }
            }

            $errors = GstService::validateNote($saveData);
            $saved = false;
            $savedId = null;
            if (!$errors) {
                $write = TdsService::cakeWrite($saveData, self::NOTE_TEXT, ['original_invoice_id']);
                try {
                    if ($isNew) {
                        $savedId = DB::table('gst_credit_debit_notes')->insertGetId($write);
                    } else {
                        unset($write['id']);
                        DB::table('gst_credit_debit_notes')->where('id', $saveData['id'])->update($write);
                        $savedId = $saveData['id'];
                    }
                    $saved = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    $saved = false;
                }
            }

            if ($saved) {
                $gst->writeAuditLog('gst_credit_debit_notes', $savedId, $isNew ? 'create' : 'update', null, $saveData);
                return redirect()->route('society.gstCreditDebitNotes')->with('success', ($dd['note_type'] ?? '') . ' saved successfully.');
            }
            session()->now('error', 'Could not save.');
            $d = $posted;
        }

        $memberList = DB::table('members')->where('society_id', $societyId)->pluck('member_name', 'id');
        $vendorList = DB::table('vendor_details')->where('society_id', $societyId)->pluck('contact_person_name', 'id');
        $hsnRows = DB::table('gst_hsn_sac_master')->where('society_id', $societyId)->get(['id', 'code', 'cgst_rate', 'sgst_rate', 'igst_rate']);
        $hsnList = [];
        $hsnRateMap = [];
        foreach ($hsnRows as $hsnRow) {
            $hsnList[$hsnRow->id] = $hsnRow->code;
            $hsnRateMap[$hsnRow->id] = ['cgst' => $hsnRow->cgst_rate, 'sgst' => $hsnRow->sgst_rate, 'igst' => $hsnRow->igst_rate];
        }

        return view('society.gst.add-credit-debit-note', compact('d', 'memberList', 'vendorList', 'hsnList', 'hsnRateMap'));
    }

    /* ------------------------------------------------------------------ Payment / challan */

    public function payments(Request $request)
    {
        $financialYearId = $this->queryFy($request);
        $rows = DB::table('gst_payments')
            ->where('society_id', $this->societyId())->where('financial_year_id', $financialYearId)
            ->orderByDesc('prepared_at')->get();
        $financialYearsList = $this->financialYearsList();

        return view('society.gst.payments', compact('rows', 'financialYearId', 'financialYearsList'));
    }

    public function preparePayment(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->fyId();
        $gst = $this->gst();
        $gstMaster = $gst->getGstMaster();

        // CakePHP prepare_payment has no view of its own: only the POST from the payments screen does anything
        if (!$request->isMethod('post')) {
            return redirect()->route('society.gstPayments');
        }

        $periodType = $request->input('period_type');
        $periodValue = $request->input('period_value');

        $existing = DB::table('gst_payments')->where([
            'society_id' => $societyId, 'financial_year_id' => $financialYearId,
            'period_type' => $periodType, 'period_value' => $periodValue,
        ])->where('payment_status', '!=', 'Paid')->first();
        if (!empty($existing)) {
            return redirect()->route('society.gstPayments')->with('error', 'A draft/prepared GST payment for this period already exists. Update that one instead of creating a duplicate.');
        }

        $breakdown = $gst->computeLiabilityBreakdown($societyId, $financialYearId, $periodType, $periodValue);

        $paymentId = DB::table('gst_payments')->insertGetId([
            'society_id' => $societyId,
            'financial_year_id' => $financialYearId,
            'gstin' => !empty($gstMaster->gstin) ? $gstMaster->gstin : null,
            'period_type' => $periodType,
            'period_value' => $periodValue,
            'cgst' => max(0, $breakdown['netCgst']),
            'sgst' => max(0, $breakdown['netSgst']),
            'igst' => max(0, $breakdown['netIgst']),
            'interest' => $breakdown['interest'],
            'late_fee' => $breakdown['lateFee'],
            'other_amount' => $breakdown['otherAdjustment'],
            'total_payable' => $breakdown['netGstPayable'],
            'payment_status' => 'Prepared',
            'prepared_by' => (string) Auth::id(),
            'prepared_at' => $this->now(),
            'cdate' => $this->now(),
            'udate' => $this->now(),
        ]);
        $gst->writeAuditLog('gst_payments', $paymentId, 'create', null, ['total_payable' => $breakdown['netGstPayable']]);

        return redirect()->route('society.gstPayments')->with('success', 'GST payment draft prepared successfully.');
    }

    public function updatePayment(Request $request, $id = null)
    {
        $payment = DB::table('gst_payments')->where('id', $id)->where('society_id', $this->societyId())->first();
        if (empty($payment)) {
            return redirect()->route('society.gstPayments')->with('error', 'GST payment not found.');
        }

        if ($request->isMethod('post')) {
            $old = TdsService::cakeRow($payment);
            $newData = [
                'id' => $id,
                'challan_number' => (string) $request->input('challan_number'),
                'cin_cpin' => (string) $request->input('cin_cpin'),
                'bank_portal_reference' => (string) $request->input('bank_portal_reference'),
                'payment_date' => (string) $request->input('payment_date'),
                'amount_paid' => (string) $request->input('amount_paid'),
                'payment_status' => (string) $request->input('payment_status'),
                'remarks' => (string) $request->input('remarks'),
                'udate' => $this->now(),
            ];
            $saved = false;
            if (in_array($newData['payment_status'], ['Draft', 'Prepared', 'Paid'], true)) {
                $write = TdsService::cakeWrite($newData, self::PAYMENT_TEXT);
                unset($write['id']);
                DB::table('gst_payments')->where('id', $id)->update($write);
                $saved = true;
            }
            if ($saved) {
                $this->gst()->writeAuditLog('gst_payments', $id, 'update', $old, $newData);
                return redirect()->route('society.gstPayments')->with('success', 'GST payment updated successfully.');
            }
            session()->now('error', 'Could not update.');
        }

        return view('society.gst.update-payment', ['p' => (array) $payment]);
    }

    /* ------------------------------------------------------------------ Reconciliation */

    public function reconciliation(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);
        $periodType = !empty($request->query('period_type')) ? $request->query('period_type') : 'Month';
        $periodValue = !empty($request->query('period_value')) ? $request->query('period_value') : date('F');
        $gst = $this->gst();

        if ($request->isMethod('post')) {
            $d = $request->post();
            $breakdown = $gst->computeLiabilityBreakdown($societyId, $financialYearId, $d['period_type'] ?? null, $d['period_value'] ?? null);
            $booksTaxable = DB::table('member_bill_summaries')
                ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->whereIn('month', GstService::monthValues($d['period_value'] ?? null))
                ->selectRaw('ROUND(SUM(monthly_bill_amount - tax_total), 2) as taxable, COUNT(*) as cnt')->first();

            $returnCgst = floatval($d['return_cgst'] ?? 0);
            $returnSgst = floatval($d['return_sgst'] ?? 0);
            $returnIgst = floatval($d['return_igst'] ?? 0);
            $booksCgst = $breakdown['outputCgst'];
            $booksSgst = $breakdown['outputSgst'];
            $booksIgst = $breakdown['outputIgst'];
            $tolerance = 1.00;
            $matched = (abs($returnCgst - $booksCgst) <= $tolerance) && (abs($returnSgst - $booksSgst) <= $tolerance) && (abs($returnIgst - $booksIgst) <= $tolerance);

            $existing = DB::table('gst_reconciliation_entries')->where([
                'society_id' => $societyId, 'financial_year_id' => $financialYearId,
                'period_type' => $d['period_type'] ?? null, 'period_value' => $d['period_value'] ?? null,
            ])->first();
            $saveData = [
                'society_id' => $societyId,
                'financial_year_id' => $financialYearId,
                'period_type' => $d['period_type'] ?? null,
                'period_value' => $d['period_value'] ?? null,
                'return_taxable_value' => floatval($d['return_taxable_value'] ?? 0),
                'return_cgst' => $returnCgst,
                'return_sgst' => $returnSgst,
                'return_igst' => $returnIgst,
                'return_invoice_count' => intval($d['return_invoice_count'] ?? 0),
                'books_taxable_value' => isset($booksTaxable->taxable) ? floatval($booksTaxable->taxable) : 0,
                'books_cgst' => $booksCgst,
                'books_sgst' => $booksSgst,
                'books_igst' => $booksIgst,
                'books_invoice_count' => isset($booksTaxable->cnt) ? intval($booksTaxable->cnt) : 0,
                'status' => $matched ? 'Matched' : 'Mismatch',
                'remarks' => $d['remarks'] ?? null,
                'created_by' => (string) Auth::id(),
                'udate' => $this->now(),
            ];
            if (!empty($existing)) {
                DB::table('gst_reconciliation_entries')->where('id', $existing->id)->update(TdsService::cakeWrite($saveData, self::RECON_TEXT));
            } else {
                $saveData['cdate'] = $this->now();
                DB::table('gst_reconciliation_entries')->insert(TdsService::cakeWrite($saveData, self::RECON_TEXT));
            }
            session()->now($matched ? 'success' : 'error', 'Reconciliation saved - Status: ' . $saveData['status']);
            $periodType = $d['period_type'] ?? null;
            $periodValue = $d['period_value'] ?? null;
        }

        $entry = DB::table('gst_reconciliation_entries')->where([
            'society_id' => $societyId, 'financial_year_id' => $financialYearId,
            'period_type' => $periodType, 'period_value' => $periodValue,
        ])->first();
        $history = DB::table('gst_reconciliation_entries')
            ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)
            ->orderByDesc('udate')->get();
        $financialYearsList = $this->financialYearsList();

        return view('society.gst.reconciliation', ['e' => !empty($entry) ? (array) $entry : [], 'history' => $history, 'financialYearId' => $financialYearId, 'periodType' => $periodType, 'periodValue' => $periodValue, 'financialYearsList' => $financialYearsList]);
    }

    /* ------------------------------------------------------------------ Return status (dashboard workflow) */

    public function updateReturnStatus(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->fyId();
        $periodType = $request->input('period_type');
        $periodValue = $request->input('period_value');
        $status = $request->input('status');

        $existing = DB::table('gst_return_status')->where([
            'society_id' => $societyId, 'financial_year_id' => $financialYearId,
            'period_type' => $periodType, 'period_value' => $periodValue,
        ])->first();
        $saveData = [
            'society_id' => $societyId, 'financial_year_id' => $financialYearId,
            'period_type' => $periodType, 'period_value' => $periodValue,
            'status' => $status, 'updated_by' => (string) Auth::id(), 'updated_at' => $this->now(),
        ];
        if (!empty($existing)) {
            $saveData['id'] = $existing->id;
            $write = $saveData;
            unset($write['id']);
            DB::table('gst_return_status')->where('id', $existing->id)->update($write);
            $savedId = $existing->id;
        } else {
            $saveData['cdate'] = $this->now();
            $savedId = DB::table('gst_return_status')->insertGetId($saveData);
        }
        $this->gst()->writeAuditLog('gst_return_status', $savedId, 'status_change', null, $saveData);

        return response()->json(['error' => 0, 'status' => $status]);
    }

    /* ------------------------------------------------------------------ Return-ready reports */

    public function returnReports(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);
        $periodValue = !empty($request->query('period_value')) ? $request->query('period_value') : '';
        $gst = $this->gst();

        $outward = function () use ($gst, $societyId, $financialYearId, $periodValue) {
            $q = $gst->outwardQuery()->where('m.society_id', $societyId)->where('m.financial_year_id', $financialYearId);
            if (!empty($periodValue)) {
                $q->whereIn('m.month', GstService::monthValues($periodValue));
            }
            return $q;
        };
        $fallbackRate = $gst->fallbackCombinedRate();

        $b2bRows = $gst->enrichOutwardRows($outward()->where('o.supply_type', 'B2B')->select($gst->outwardSelect())->get(), $fallbackRate);
        // CakePHP: ('GstOutwardSupplyMeta.supply_type' = 'B2C') OR (supply_type IS NULL)
        $b2cRows = $gst->enrichOutwardRows($outward()->where(function ($q) {
            $q->where('o.supply_type', 'B2C')->orWhereNull('o.supply_type');
        })->select($gst->outwardSelect())->get(), $fallbackRate);

        $notes = fn ($type) => DB::table('gst_credit_debit_notes')->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->where('note_type', $type)->get();
        $creditNotes = $notes('Credit Note');
        $debitNotes = $notes('Debit Note');

        $advancesReceived = DB::table('gst_advance_receipts')->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->where('adjustment_status', '!=', 'Fully Adjusted')->get();
        $advanceAdjustments = DB::table('gst_advance_adjustments')->where('society_id', $societyId)->orderByDesc('adjustment_date')->get();

        $exemptHsn = DB::table('gst_hsn_sac_master')->where('society_id', $societyId)->where('taxability_type', '!=', 'Taxable')->get();

        $hsnSummary = $outward()->selectRaw('h.code, h.description, COUNT(*) as invoice_count, ROUND(SUM(m.cgst_total), 2) as cgst, ROUND(SUM(m.sgst_total), 2) as sgst, ROUND(SUM(m.igst_total), 2) as igst, ROUND(SUM(m.tax_total), 2) as total_gst')
            ->groupBy('o.hsn_sac_id')->get();

        $liability = $gst->computeLiabilityBreakdown($societyId, $financialYearId, 'Month', !empty($periodValue) ? $periodValue : date('F'));

        $payments = DB::table('gst_payments')->where('society_id', $societyId)->where('financial_year_id', $financialYearId);
        if (!empty($periodValue)) {
            $payments->where('period_value', $periodValue);
        }
        $payments = $payments->orderByDesc('prepared_at')->get();

        $financialYearsList = $this->financialYearsList();

        return view('society.gst.return-reports', compact('b2bRows', 'b2cRows', 'creditNotes', 'debitNotes', 'advancesReceived', 'advanceAdjustments', 'exemptHsn', 'hsnSummary', 'liability', 'payments', 'financialYearId', 'periodValue', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ Year-end report */

    public function yearEndReport(Request $request)
    {
        $societyId = $this->societyId();
        $financialYearId = $this->queryFy($request);
        $gst = $this->gst();
        $months = ['April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];

        $monthlyData = [];
        $grandTotals = ['turnover' => 0, 'output_cgst' => 0, 'output_sgst' => 0, 'output_igst' => 0, 'input_cgst' => 0, 'input_sgst' => 0, 'input_igst' => 0, 'net_liability' => 0, 'paid' => 0];

        foreach ($months as $month) {
            $output = DB::table('member_bill_summaries')
                ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->whereIn('month', GstService::monthValues($month))
                ->selectRaw('ROUND(SUM(monthly_bill_amount), 2) as turnover, ROUND(SUM(cgst_total), 2) as cgst, ROUND(SUM(sgst_total), 2) as sgst, ROUND(SUM(igst_total), 2) as igst')->first();
            $turnover = isset($output->turnover) ? floatval($output->turnover) : 0;
            $outCgst = isset($output->cgst) ? floatval($output->cgst) : 0;
            $outSgst = isset($output->sgst) ? floatval($output->sgst) : 0;
            $outIgst = isset($output->igst) ? floatval($output->igst) : 0;

            $input = $gst->inputQuery()
                ->where('b.society_id', $societyId)->where('b.financial_year_id', $financialYearId)->whereRaw('MONTHNAME(b.bill_date) = ?', [$month])
                ->selectRaw('SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.cgst_amount END) as cgst, '
                    . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.sgst_amount END) as sgst, '
                    . 'SUM(CASE WHEN c.itc_eligibility = "Ineligible" THEN 0 ELSE d.igst_amount END) as igst')->first();
            $inCgst = isset($input->cgst) ? floatval($input->cgst) : 0;
            $inSgst = isset($input->sgst) ? floatval($input->sgst) : 0;
            $inIgst = isset($input->igst) ? floatval($input->igst) : 0;

            $netLiability = ($outCgst + $outSgst + $outIgst) - ($inCgst + $inSgst + $inIgst);

            $paidRow = DB::table('gst_payments')
                ->where('society_id', $societyId)->where('financial_year_id', $financialYearId)->where('period_value', $month)->where('payment_status', 'Paid')
                ->selectRaw('SUM(cgst + sgst + igst) as paid')->first();
            $paid = isset($paidRow->paid) ? floatval($paidRow->paid) : 0;

            $monthlyData[] = ['month' => $month, 'turnover' => $turnover, 'output_cgst' => $outCgst, 'output_sgst' => $outSgst, 'output_igst' => $outIgst, 'input_cgst' => $inCgst, 'input_sgst' => $inSgst, 'input_igst' => $inIgst, 'net_liability' => $netLiability, 'paid' => $paid, 'closing' => $netLiability - $paid];

            $grandTotals['turnover'] += $turnover;
            $grandTotals['output_cgst'] += $outCgst;
            $grandTotals['output_sgst'] += $outSgst;
            $grandTotals['output_igst'] += $outIgst;
            $grandTotals['input_cgst'] += $inCgst;
            $grandTotals['input_sgst'] += $inSgst;
            $grandTotals['input_igst'] += $inIgst;
            $grandTotals['net_liability'] += $netLiability;
            $grandTotals['paid'] += $paid;
        }

        $financialYearsList = $this->financialYearsList();

        return view('society.gst.year-end-report', compact('monthlyData', 'grandTotals', 'financialYearId', 'financialYearsList'));
    }

    /* ------------------------------------------------------------------ export helpers */

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

    private function excel(string $sheetName, array $headers, array $rows, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle($sheetName);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        $rowNo = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNo++);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
