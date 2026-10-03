<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\SocietyLedgerHead;
use App\Models\VendorBill;
use App\Models\VendorBillDetail;
use App\Models\VendorDetail;
use App\Models\VendorFacility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Vendor Detail and Vendor Billing (CakePHP SocietysController::vendor_details / vendor_billings and the
 * "Vendor Detail / Vendor Billing" block of SocietysAjaxController). The list pages open the same modals as
 * CakePHP: vendor detail form, billing form and the quick "Add New Ledger Head" form, all served by the
 * JSON / HTML fragments below.
 */
class VendorController extends Controller
{
    private function societyId(): int
    {
        return (int) auth()->id();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function fail(string $message = 'Opps! Something went wrong.'): array
    {
        return ['error' => 1, 'error_message' => $message];
    }

    // ------------------------------------------------------------------ list pages

    public function details()
    {
        $vendorDetailsList = DB::table('vendor_details as v')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'v.ledger_head_id')
            ->where('v.society_id', $this->societyId())
            ->orderBy('h.title')
            ->get(['v.*', 'h.id as head_id', 'h.title as head_title']);

        return view('society.vendor.details', compact('vendorDetailsList'));
    }

    public function billings()
    {
        $vendorBillsList = DB::table('vendor_bills as b')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'b.vendor_ledger_head_id')
            ->where('b.society_id', $this->societyId())
            ->orderByDesc('b.bill_date')->orderByDesc('b.id')
            ->get(['b.*', 'h.title as vendor_title']);

        return view('society.vendor.billings', compact('vendorBillsList'));
    }

    // ------------------------------------------------------------------ vendor detail

    /** HTML of the vendor detail form (existing vendor, or a picker of ledger heads that are not a vendor yet) */
    public function detailForm(Request $request)
    {
        $societyId = $this->societyId();
        $ledgerHeadId = (int) $request->input('ledgerHeadId', 0);

        $vendorDetail = VendorDetail::where('ledger_head_id', $ledgerHeadId)->where('society_id', $societyId)->first();
        $vendorFacilityLists = VendorFacility::where('society_id', $societyId)->where('status', 1)->orderBy('title')->pluck('title', 'id');

        // Opened with no ledger head yet (Billing's "+ Add New Vendor") - offer a picker of ledger heads that
        // aren't already a vendor, alongside the "+" to create a brand new one via quick-add.
        $ledgerHeadTitle = '';
        $availableLedgerHeadLists = collect();
        if (empty($ledgerHeadId)) {
            $existing = VendorDetail::where('society_id', $societyId)->pluck('ledger_head_id')->all();
            $availableLedgerHeadLists = SocietyLedgerHead::where('society_id', $societyId)->where('status', 1)
                ->when(!empty($existing), fn ($q) => $q->whereNotIn('id', $existing))
                ->orderBy('title')->pluck('title', 'id');
        } else {
            $ledgerHeadTitle = (string) SocietyLedgerHead::where('id', $ledgerHeadId)->where('society_id', $societyId)->value('title');
        }

        return view('society.vendor._detail_form', [
            'v' => $vendorDetail ? $vendorDetail->toArray() : [],
            'vendorFacilityLists' => $vendorFacilityLists,
            'ledgerHeadId' => $ledgerHeadId,
            'ledgerHeadTitle' => $ledgerHeadTitle,
            'availableLedgerHeadLists' => $availableLedgerHeadLists,
        ]);
    }

    public function saveDetail(Request $request)
    {
        $societyId = $this->societyId();
        $data = (array) $request->input('VendorDetail', []);
        $ledgerHeadId = (int) ($data['ledger_head_id'] ?? 0);

        if (!SocietyLedgerHead::where('id', $ledgerHeadId)->where('society_id', $societyId)->exists()) {
            return response()->json($this->fail('Invalid ledger head selected.'));
        }

        $saveData = [
            'society_id' => $societyId,
            'ledger_head_id' => $ledgerHeadId,
            'contact_person_name' => $data['contact_person_name'] ?? '',
            'phone_number' => $data['phone_number'] ?? '',
            'pan_no' => $data['pan_no'] ?? '',
            'gst_no' => $data['gst_no'] ?? '',
            'company_email' => $data['company_email'] ?? '',
            'comments' => $data['comments'] ?? '',
            'amc_start_date' => !empty($data['amc_start_date']) ? $data['amc_start_date'] : null,
            'amc_end_date' => !empty($data['amc_end_date']) ? $data['amc_end_date'] : null,
            'facility_id' => !empty($data['facility_id']) ? $data['facility_id'] : null,
            'sub_committee_list' => $data['sub_committee_list'] ?? '',
            'rating' => $data['rating'] ?? '',
            'cr_dr' => (($data['cr_dr'] ?? '') === 'Dr') ? 'Dr' : 'Cr',
            'status' => 1,
            'udate' => $this->now(),
        ];

        try {
            $existing = VendorDetail::where('ledger_head_id', $ledgerHeadId)->where('society_id', $societyId)->first();
            if ($existing) {
                $existing->update($saveData);
            } else {
                VendorDetail::create($saveData + ['cdate' => $this->now()]);
            }
        } catch (\Throwable $e) {
            report($e);

            return response()->json($this->fail());
        }

        return response()->json(['error' => 0, 'error_message' => 'Vendor detail saved successfully.', 'ledger_head_id' => $ledgerHeadId]);
    }

    /** Find-or-create for the "Select Facility" inline add; returns a real vendor_facilities row id */
    public function saveFacility(Request $request)
    {
        $societyId = $this->societyId();
        $title = trim((string) $request->input('title', ''));

        if ($title === '') {
            return response()->json($this->fail('Facility name is required.'));
        }

        $existing = VendorFacility::where('society_id', $societyId)->where('title', $title)->first();
        if ($existing) {
            return response()->json(['error' => 0, 'id' => $existing->id, 'title' => $existing->title]);
        }

        try {
            $facility = VendorFacility::create(['society_id' => $societyId, 'title' => $title, 'status' => 1, 'cdate' => $this->now(), 'udate' => $this->now()]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json($this->fail());
        }

        return response()->json(['error' => 0, 'id' => $facility->id, 'title' => $title]);
    }

    // ------------------------------------------------------------------ dropdown fragments

    /** <option>s of the vendors (ledger heads with a vendor detail), for the billing form's vendor select */
    public function vendorList()
    {
        $vendorLists = $this->vendorLists();

        $html = '';
        if (count($vendorLists) > 0) {
            foreach ($vendorLists as $id => $title) {
                $html .= '<option value="' . (int) $id . '">' . e($title) . '</option>' . "\n";
            }
        } else {
            $html .= '<option value="">No vendors added yet</option>' . "\n";
        }
        $html .= '<option value="new">+ Add New Vendor</option>';

        return response($html);
    }

    /** ledger head id => title of the society's ACTIVE vendors, sorted by title */
    private function vendorLists(): array
    {
        $rows = DB::table('vendor_details as v')
            ->join('society_ledger_heads as h', 'h.id', '=', 'v.ledger_head_id')
            ->where('v.society_id', $this->societyId())->where('v.status', 1)->where('h.status', 1)
            ->get(['h.id', 'h.title']);

        $lists = [];
        foreach ($rows as $r) {
            $lists[$r->id] = $r->title;
        }
        asort($lists);

        return $lists;
    }

    /**
     * ledger head id => title for the "Bill Particulars" select: the society's active heads except income
     * heads (account_category_id 3) - CakePHP SocietyBill::societyAllLedgerHeadsLists(), which returns nothing
     * when there are none.
     */
    private function billParticularHeadLists(): array
    {
        return SocietyLedgerHead::where('status', 1)->where('society_id', $this->societyId())
            ->where('account_category_id', '!=', 3)->orderBy('title')->pluck('title', 'id')->all();
    }

    public function particularHeads()
    {
        $lists = $this->billParticularHeadLists();

        $html = '';
        if (count($lists) > 0) {
            foreach ($lists as $id => $title) {
                $html .= '<option value="' . (int) $id . '">' . e($title) . '</option>' . "\n";
            }
        } else {
            $html .= '<option value="">Ledger heads not available</option>' . "\n";
        }
        $html .= '<option value="new">+ Add New Ledger Head</option>';

        return response($html);
    }

    /** <option>s of the society's (and the common) head sub categories, for the quick ledger head form */
    public function subCategories()
    {
        $rows = DB::table('society_head_sub_categories')->whereIn('society_id', [0, $this->societyId()])->orderBy('title')->get(['id', 'title']);

        $html = '';
        foreach ($rows as $r) {
            $html .= '<option value="' . (int) $r->id . '">' . e($r->title) . '</option>' . "\n";
        }

        return response($rows->isEmpty() ? '<option value="">Not available</option>' : $html);
    }

    /** the sub category's account category, as an <option> */
    public function accountCategories(Request $request)
    {
        $row = DB::table('society_head_sub_categories as s')
            ->join('account_categories as c', 'c.id', '=', 's.account_category_id')
            ->where('s.id', (int) $request->input('headSubCategoryId', 0))->where('s.status', 1)
            ->first(['c.id', 'c.title']);

        return response($row
            ? '<option value="' . (int) $row->id . '">' . e($row->title) . '</option>'
            : '<option value="">Account category not available</option>');
    }

    /** the sub category's account head (group), as an <option> */
    public function accountHeads(Request $request)
    {
        $row = DB::table('society_head_sub_categories as s')
            ->join('account_heads as a', 'a.id', '=', 's.account_head_id')
            ->where('s.id', (int) $request->input('headSubCategoryId', 0))->where('s.status', 1)
            ->first(['a.id', 'a.title']);

        return response($row
            ? '<option value="' . (int) $row->id . '">' . e($row->title) . '</option>'
            : '<option value="">Account head not available</option>');
    }

    /** Creates a brand new ledger head from the "+" next to a vendor / bill particular dropdown */
    public function saveLedgerHeadInline(Request $request)
    {
        $data = (array) $request->input('SocietyLedgerHeads', []);
        $title = trim((string) ($data['title'] ?? ''));
        $subCategoryId = $data['society_head_sub_category_id'] ?? '';

        if ($title === '' || empty($subCategoryId)) {
            return response()->json($this->fail('Title and Subgroup are required.'));
        }

        try {
            $head = SocietyLedgerHead::create([
                'title' => $title,
                'short_code' => $data['short_code'] ?? '',
                'opening_amount' => isset($data['opening_amount']) && $data['opening_amount'] !== '' ? $data['opening_amount'] : 0,
                'society_head_sub_category_id' => $subCategoryId,
                'account_head_id' => $data['account_head_id'] ?? '',
                'account_category_id' => $data['account_category_id'] ?? '',
                'society_id' => $this->societyId(),
                'status' => 1,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json($this->fail());
        }

        return response()->json(['error' => 0, 'error_message' => 'Ledger head created successfully.', 'id' => $head->id, 'title' => $title]);
    }

    // ------------------------------------------------------------------ vendor billing

    public function billingForm(Request $request)
    {
        $societyId = $this->societyId();
        $vendorBillId = (int) $request->input('vendorBillId', 0);

        $header = [];
        $lines = [];
        if (!empty($vendorBillId)) {
            $bill = VendorBill::where('id', $vendorBillId)->where('society_id', $societyId)->first();
            if ($bill) {
                $header = $bill->toArray();
                $lines = VendorBillDetail::where('vendor_bill_id', $bill->id)->orderBy('id')->get()->toArray();
            }
        }

        return view('society.vendor._billing_form', [
            'header' => $header,
            'lines' => $lines,
            'vendorLists' => $this->vendorLists(),
            'billParticularHeadLists' => $this->billParticularHeadLists(),
        ]);
    }

    /** Recomputes every total server-side from the posted line data rather than trusting the client's numbers */
    public function saveBill(Request $request)
    {
        $societyId = $this->societyId();
        $header = (array) $request->input('VendorBill', []);
        $lines = $request->input('VendorBillDetail', []);

        $vendorLedgerHeadId = (int) ($header['vendor_ledger_head_id'] ?? 0);
        if (empty($vendorLedgerHeadId) || !SocietyLedgerHead::where('id', $vendorLedgerHeadId)->where('society_id', $societyId)->exists()) {
            return response()->json($this->fail('Please select a valid vendor.'));
        }

        $totalAmount = $totalSgst = $totalCgst = $totalIgst = 0;
        $cleanLines = [];
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $amount = isset($line['amount']) ? (float) $line['amount'] : 0;
                if (empty($line['ledger_head_id']) || $amount <= 0) {
                    continue;
                }
                $sgstRate = isset($line['sgst_rate']) ? (float) $line['sgst_rate'] : 0;
                $cgstRate = isset($line['cgst_rate']) ? (float) $line['cgst_rate'] : 0;
                $igstRate = isset($line['igst_rate']) ? (float) $line['igst_rate'] : 0;
                $sgstAmount = round($amount * $sgstRate / 100, 2);
                $cgstAmount = round($amount * $cgstRate / 100, 2);
                $igstAmount = round($amount * $igstRate / 100, 2);
                $totalAmount += $amount;
                $totalSgst += $sgstAmount;
                $totalCgst += $cgstAmount;
                $totalIgst += $igstAmount;
                $cleanLines[] = [
                    'ledger_head_id' => $line['ledger_head_id'],
                    'amount' => $amount,
                    'sgst_rate' => $sgstRate, 'sgst_amount' => $sgstAmount,
                    'cgst_rate' => $cgstRate, 'cgst_amount' => $cgstAmount,
                    'igst_rate' => $igstRate, 'igst_amount' => $igstAmount,
                    'hsn_sac' => $line['hsn_sac'] ?? '',
                ];
            }
        }

        if (empty($cleanLines)) {
            return response()->json($this->fail('Please add at least one billing line.'));
        }

        $tdsPercent = isset($header['tds_percent']) ? (float) $header['tds_percent'] : 0;
        $deductAmount = isset($header['deduct_amount']) ? (float) $header['deduct_amount'] : 0;
        $subTotal = $totalAmount + $totalSgst + $totalCgst + $totalIgst;
        $tdsAmount = round($subTotal * $tdsPercent / 100, 2);
        $totalBillBeforeRound = $subTotal - $tdsAmount - $deductAmount;
        $totalBillRounded = round($totalBillBeforeRound);
        $roundOffAmount = round($totalBillRounded - $totalBillBeforeRound, 2);

        $billData = [
            'society_id' => $societyId,
            'vendor_ledger_head_id' => $vendorLedgerHeadId,
            'bill_type' => $header['bill_type'] ?? 'Sales',
            'bill_no' => $header['bill_no'] ?? '',
            'bill_date' => !empty($header['bill_date']) ? $header['bill_date'] : null,
            'due_date' => !empty($header['due_date']) ? $header['due_date'] : null,
            'po_no' => $header['po_no'] ?? '',
            'title' => $header['title'] ?? '',
            'remarks' => $header['remarks'] ?? '',
            'total_amount' => $totalAmount,
            'total_sgst_amount' => $totalSgst,
            'total_cgst_amount' => $totalCgst,
            'total_igst_amount' => $totalIgst,
            'tds_percent' => $tdsPercent,
            'tds_amount' => $tdsAmount,
            'deduct_amount' => $deductAmount,
            'total_bill_amount' => $totalBillRounded,
            'round_off_amount' => $roundOffAmount,
            'status' => 1,
            'udate' => $this->now(),
        ];

        try {
            $vendorBillId = DB::transaction(function () use ($header, $societyId, $billData, $cleanLines) {
                $vendorBillId = (int) ($header['id'] ?? 0);

                if (!empty($vendorBillId)) {
                    $bill = VendorBill::where('id', $vendorBillId)->where('society_id', $societyId)->first();
                    if (!$bill) {
                        throw new \RuntimeException('Invalid vendor bill.');
                    }
                    $bill->update($billData);
                } else {
                    $bill = VendorBill::create($billData + ['cdate' => $this->now()]);
                }

                // Replace the line set wholesale on edit - simpler and safer than diffing rows.
                VendorBillDetail::where('vendor_bill_id', $bill->id)->delete();
                foreach ($cleanLines as $line) {
                    VendorBillDetail::create($line + ['vendor_bill_id' => $bill->id, 'cdate' => $this->now(), 'udate' => $this->now()]);
                }

                return $bill->id;
            });
        } catch (\Throwable $e) {
            return response()->json($this->fail($e instanceof \RuntimeException ? $e->getMessage() : 'Unable to save vendor bill.'));
        }

        return response()->json([
            'error' => 0, 'error_message' => 'Vendor bill saved successfully.',
            'id' => $vendorBillId, 'total_bill_amount' => $totalBillRounded,
        ]);
    }

    public function printBill($vendorBillId)
    {
        $bill = VendorBill::where('id', (int) $vendorBillId)->where('society_id', $this->societyId())->first();
        abort_if(!$bill, 404, 'Vendor bill not found.');

        $lines = DB::table('vendor_bill_details as d')
            ->leftJoin('society_ledger_heads as h', 'h.id', '=', 'd.ledger_head_id')
            ->where('d.vendor_bill_id', $bill->id)->orderBy('d.id')
            ->get(['d.*', 'h.title as particular_title']);

        return view('society.vendor.print', [
            'header' => $bill->toArray(),
            'lines' => $lines,
            'vendorName' => (string) SocietyLedgerHead::where('id', $bill->vendor_ledger_head_id)->value('title'),
            'societyName' => auth()->user()->username,
        ]);
    }
}
