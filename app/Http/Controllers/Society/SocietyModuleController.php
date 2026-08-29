<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\BillingFrequency;
use App\Models\Bank;
use App\Models\Building;
use App\Models\CashWithdraw;
use App\Models\Employee;
use App\Models\EmployeeCategory;
use App\Models\EmployeeSubCategory;
use App\Models\InterestMethod;
use App\Models\InterestType;
use App\Models\JournalVoucher;
use App\Models\Member;
use App\Models\MemberBillGenerate;
use App\Models\MemberBillSettlement;
use App\Models\MemberBillSummary;
use App\Models\MemberIdentification;
use App\Models\MemberPayment;
use App\Models\MemberTariff;
use App\Models\Society;
use App\Models\SocietyHeadSubCategory;
use App\Models\SocietyLedgerHead;
use App\Models\SocietyParameter;
use App\Models\SocietyPayment;
use App\Models\SocietyTariffOrder;
use App\Models\AccountHead;
use App\Models\SocietyBank;
use App\Models\Tenant;
use App\Models\TariffType;
use App\Models\MemberTariffDetail;
use App\Models\Wing;
use App\Services\BillSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SocietyModuleController extends Controller
{
    private function getSociety()
    {
        return Society::where('user_id', Auth::id())->first();
    }

    private function societyId()
    {
        return Auth::id();
    }

    private function fyId()
    {
        return session('fy.year_id');
    }

    // ─── Society Section ───────────────────────────────────────────

    public function identity(Request $request)
    {
        $society = $this->getSociety();

        if ($request->isMethod('post')) {
            $society->update($request->only([
                'society_name', 'society_code', 'registration_no', 'registration_date',
                'address', 'telephone_no', 'fax_no', 'email_id', 'url',
                'tan_no', 'pan_no', 'circle', 'service_tax_no',
                'gstin_no', 'cgst_no', 'igst_no',
                'is_conveyance', 'conveynace_date', 'authorised_person',
            ]));
            return redirect()->route('society.identity')->with('success', 'Society identity updated.');
        }

        return view('society.modules.identity', compact('society'));
    }

    public function parameters(Request $request)
    {
        $societyId = $this->societyId();
        $society = $this->getSociety();
        $params = SocietyParameter::where('society_id', $societyId)->first();

        if ($request->isMethod('post')) {
            $data = $request->only([
                'billing_frequency_id', 'interest_type_id', 'interest_rate',
                'method_id', 'tariff_id', 'cgst_tax_per', 'igst_tax_per', 'sgst_tax_per',
                'is_tariff_mothly', 'show_all_tariff_name', 'bill_note', 'special_field',
                'show_bills_in_receipt', 'gst_interest', 'gst_interest_arreas',
                'settlement', 'gst_limit',
            ]);
            if ($params) {
                $params->update($data);
            } else {
                $data['society_id'] = $societyId;
                SocietyParameter::create($data);
            }
            return redirect()->route('society.parameters')->with('success', 'Parameters updated.');
        }

        $billingFrequencies = BillingFrequency::all();
        $interestTypes = InterestType::all();
        $interestMethods = InterestMethod::all();
        $tariffTypes = TariffType::all();

        return view('society.modules.parameters', compact(
            'society', 'params', 'billingFrequencies', 'interestTypes', 'interestMethods', 'tariffTypes'
        ));
    }

    public function tariffDefinition()
    {
        return view('society.modules.placeholder', [
            'title' => 'Tariff Definition',
        ]);
    }

    public function headSubCategories(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $items = SocietyHeadSubCategory::whereIn('society_id', [0, $societyId])
            ->with(['accountCategory', 'accountHead'])
            ->orderBy('title')
            ->get();
        $accountCategories = AccountCategory::where('status', 1)->orderBy('title')->pluck('title', 'id');
        $editItem = $id ? SocietyHeadSubCategory::with('accountHead')->find($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only(['title', 'account_category_id', 'account_head_id', 'account_status']);
            $data['society_id'] = $societyId;
            $editId = $request->input('id');

            if ($editId) {
                SocietyHeadSubCategory::where('id', $editId)->update($data);
                return redirect()->route('society.headSubCategories')->with('success', 'Society head category data saved/Updated successfully.');
            } else {
                $titles = explode("\n", $data['title']);
                foreach ($titles as $title) {
                    $title = trim($title);
                    if (empty($title)) continue;
                    $data['title'] = $title;
                    SocietyHeadSubCategory::create($data);
                }
                return redirect()->route('society.headSubCategories')->with('success', 'Society head category data saved/Updated successfully.');
            }
        }

        return view('society.modules.head-sub-categories', compact('items', 'accountCategories', 'editItem'));
    }

    public function getAccountHeads(Request $request)
    {
        $categoryId = $request->input('category_id');
        $heads = AccountHead::where('account_category_id', $categoryId)
            ->where('status', 1)
            ->orderBy('title')
            ->get(['id', 'title']);
        return response()->json($heads);
    }

    public function getSubGroupDetails(Request $request)
    {
        $subGroupId = $request->input('sub_group_id');
        $subGroup = SocietyHeadSubCategory::with(['accountCategory', 'accountHead'])->find($subGroupId);
        if (!$subGroup) {
            return response()->json(['account_category' => null, 'account_head' => null]);
        }
        return response()->json([
            'account_category' => $subGroup->accountCategory ? ['id' => $subGroup->accountCategory->id, 'title' => $subGroup->accountCategory->title] : null,
            'account_head' => $subGroup->accountHead ? ['id' => $subGroup->accountHead->id, 'title' => $subGroup->accountHead->title] : null,
        ]);
    }

    public function ledgerHeads()
    {
        $societyId = $this->societyId();
        $items = SocietyLedgerHead::where('society_id', $societyId)
            ->where('status', 1)
            ->with(['accountCategory', 'accountHead', 'headSubCategory'])
            ->orderBy('title')
            ->get();

        return view('society.modules.ledger-heads', compact('items'));
    }

    public function addLedgerHead(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $headSubCategories = SocietyHeadSubCategory::whereIn('society_id', [0, $societyId])
            ->where('status', 1)->orderBy('title')->pluck('title', 'id');
        $editItem = $id ? SocietyLedgerHead::with(['accountHead', 'accountCategory'])->find($id) : null;
        $bankData = null;
        if ($editItem) {
            $bankData = SocietyBank::where('bank_ledger_head_id', $id)->first();
        }

        if ($request->isMethod('post')) {
            if ($id && $request->has('title')) {
                $data = $request->only([
                    'title', 'short_code', 'account_category_id', 'account_head_id',
                    'society_head_sub_category_id', 'opening_amount', 'is_in_bill_charges',
                    'is_tax_applicable', 'is_rebate_applicable', 'is_interest_free',
                    'is_tds', 'tds_value', 'tds_type', 'is_supplementary_bill',
                ]);
                $data['society_id'] = $societyId;
                $data['financial_year_id'] = $fyId;
                $data['is_in_bill_charges'] = $data['is_in_bill_charges'] ?? 0;
                $data['is_tax_applicable'] = $data['is_tax_applicable'] ?? 0;
                $data['is_rebate_applicable'] = $data['is_rebate_applicable'] ?? 0;
                $data['is_interest_free'] = $data['is_interest_free'] ?? 0;
                $data['is_tds'] = $data['is_tds'] ?? 0;
                $data['is_supplementary_bill'] = $data['is_supplementary_bill'] ?? 0;

                SocietyLedgerHead::where('id', $id)->update($data);

                $bankBranch = $request->input('bank_branch');
                $accountNo = $request->input('account_no');
                if ($bankBranch || $accountNo) {
                    SocietyBank::updateOrCreate(
                        ['bank_ledger_head_id' => $id, 'society_id' => $societyId],
                        ['branch' => $bankBranch ?? '', 'account_no' => $accountNo ?? '']
                    );
                }

                return redirect()->route('society.ledgerHeads')->with('success', 'Ledger head updated successfully.');
            }

            $rows = $request->input('rows', []);
            $subCategoryId = $request->input('society_head_sub_category_id', '');
            $accountCategoryId = $request->input('account_category_id', '');
            $accountHeadId = $request->input('account_head_id', '');
            $savedCount = 0;

            foreach ($rows as $row) {
                if (empty($row['title']) && empty($row['short_code'])) continue;

                $ledgerData = [
                    'society_id' => $societyId,
                    'financial_year_id' => $fyId,
                    'society_head_sub_category_id' => $subCategoryId,
                    'account_category_id' => $accountCategoryId,
                    'account_head_id' => $accountHeadId,
                    'title' => $row['title'] ?? '',
                    'short_code' => $row['short_code'] ?? '',
                    'opening_amount' => $row['op_amount'] ?? 0,
                    'is_in_bill_charges' => $row['is_in_bill_charges'] ?? 0,
                    'is_tax_applicable' => $row['is_tax_applicable'] ?? 0,
                    'is_rebate_applicable' => $row['is_rebate_applicable'] ?? 0,
                    'is_interest_free' => $row['is_interest_free'] ?? 0,
                    'is_tds' => $row['is_tds'] ?? 0,
                    'tds_value' => $row['tds_value'] ?? 0,
                    'tds_type' => $row['tds_type'] ?? 0,
                    'is_supplementary_bill' => $row['is_supplementary_bill'] ?? 0,
                    'cdate' => now(),
                ];

                $saved = SocietyLedgerHead::create($ledgerData);

                if ($saved && (!empty($row['bank_branch']) || !empty($row['account_no']))) {
                    SocietyBank::create([
                        'society_id' => $societyId,
                        'bank_ledger_head_id' => $saved->id,
                        'branch' => $row['bank_branch'] ?? '',
                        'account_no' => $row['account_no'] ?? '',
                    ]);
                }
                $savedCount++;
            }

            return redirect()->route('society.ledgerHeads')->with('success', "{$savedCount} ledger head(s) added successfully.");
        }

        return view('society.modules.add-ledger-head', compact('headSubCategories', 'editItem', 'bankData'));
    }

    public function deleteLedgerHead($id)
    {
        $societyId = $this->societyId();
        $ledger = SocietyLedgerHead::where('id', $id)->where('society_id', $societyId)->firstOrFail();
        $ledger->update(['status' => 0]);
        return redirect()->route('society.ledgerHeads')->with('success', 'The ledger head has been deleted.');
    }

    public function tariffs()
    {
        return view('society.modules.placeholder', [
            'title' => 'Society Tariffs',
        ]);
    }

    public function tariffOrders()
    {
        $societyId = $this->societyId();
        $items = SocietyLedgerHead::where('society_id', $societyId)
            ->where('status', 1)
            ->where('is_in_bill_charges', 1)
            ->with('tariffOrder')
            ->get()
            ->sortBy(function ($item) {
                return $item->tariffOrder->tariff_serial ?? 9999;
            });

        return view('society.modules.tariff-orders', compact('items'));
    }

    public function saveTariffOrder(Request $request)
    {
        $societyId = $this->societyId();
        $order = $request->input('order', []);
        foreach ($order as $serial => $ledgerHeadId) {
            SocietyTariffOrder::updateOrCreate(
                ['society_id' => $societyId, 'ledger_head_id' => $ledgerHeadId],
                ['tariff_serial' => $serial + 1]
            );
        }
        return response()->json(['success' => true, 'message' => 'Tariff order updated.']);
    }

    public function payments(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $query = SocietyPayment::where('society_id', $societyId)
            ->where('status', 1)
            ->where('financial_year_id', $fyId)
            ->with('ledgerHead');

        if ($request->isMethod('post')) {
            if ($request->input('from_date')) {
                $query->where('payment_date', '>=', $request->input('from_date'));
            }
            if ($request->input('to_date')) {
                $query->where('payment_date', '<=', $request->input('to_date'));
            }
        }

        $items = $query->orderByDesc('payment_date')->get();
        $accountCategories = AccountCategory::pluck('title', 'id');
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        return view('society.modules.payments', compact('items', 'fyId', 'accountCategories', 'fromDate', 'toDate'));
    }

    public function addPayment(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $ledgerHeads = SocietyLedgerHead::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('title')
            ->pluck('title', 'id');
        $editItem = $id ? SocietyPayment::findOrFail($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'ledger_head_id', 'particulars', 'amount', 'tax_amount', 'total_amount',
                'bill_voucher_number', 'cheque_reference_number', 'payment_date',
                'payment_type', 'debited_date', 'cheque_date', 'notes',
            ]);
            $data['society_id'] = $societyId;
            $data['financial_year_id'] = $fyId;
            $data['status'] = 1;
            $data['amount'] = $data['amount'] ?: 0;
            $data['tax_amount'] = $data['tax_amount'] ?: 0;
            $data['total_amount'] = ($data['amount'] - $data['tax_amount']);

            if ($id) {
                SocietyPayment::where('id', $id)->update($data);
            } else {
                SocietyPayment::create($data);
            }
            return redirect()->route('society.payments')->with('success', $id ? 'Payment updated.' : 'Payment added.');
        }

        return view('society.modules.add-payment', compact('ledgerHeads', 'editItem'));
    }

    public function deletePayment($id)
    {
        $societyId = $this->societyId();
        SocietyPayment::where('id', $id)->where('society_id', $societyId)->update(['status' => 0]);
        return redirect()->route('society.payments')->with('success', 'Payment deleted.');
    }

    public function bankReconciliation()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bank Reconciliation',
        ]);
    }

    public function cashContra()
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $items = CashWithdraw::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->with('bankLedgerHead')
            ->orderByDesc('payment_date')
            ->get();

        return view('society.modules.cash-contra', compact('items', 'fyId'));
    }

    public function generalReceipt()
    {
        return view('society.modules.placeholder', [
            'title' => 'General Receipt',
        ]);
    }

    // ─── Member Section ────────────────────────────────────────────

    public function buildingIdentity(Request $request)
    {
        $societyId = $this->societyId();

        if ($request->isMethod('post')) {
            Building::create([
                'society_id'    => $societyId,
                'building_name' => $request->input('building_name'),
                'num_flats'     => $request->input('num_flats', 0),
                'status'        => 1,
            ]);
            return redirect()->route('society.buildingIdentity')->with('success', 'Building added.');
        }

        $buildings = Building::where('society_id', $societyId)->get();

        return view('society.modules.building-identity', compact('buildings'));
    }

    public function wingIdentity(Request $request)
    {
        $societyId = $this->societyId();

        if ($request->isMethod('post')) {
            Wing::create([
                'society_id'  => $societyId,
                'building_id' => $request->input('building_id'),
                'wing_name'   => $request->input('wing_name'),
                'status'      => 1,
            ]);
            return redirect()->route('society.wingIdentity')->with('success', 'Wing added.');
        }

        $wings = Wing::where('society_id', $societyId)->with('building')->get();
        $buildings = Building::where('society_id', $societyId)->active()->get();

        return view('society.modules.wing-identity', compact('wings', 'buildings'));
    }

    public function memberIdentity()
    {
        $societyId = $this->societyId();
        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->with(['building', 'wing'])
            ->orderBy('id', 'asc')
            ->get();
        $buildings = Building::where('society_id', $societyId)->where('status', 1)->orderBy('building_name')->pluck('building_name', 'id');
        $wings = Wing::where('society_id', $societyId)->where('status', 1)->get();

        return view('society.modules.member-identity', compact('members', 'buildings', 'wings'));
    }

    public function addMember(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $buildings = Building::where('society_id', $societyId)->where('status', 1)->orderBy('building_name')->pluck('building_name', 'id');
        $wings = Wing::where('society_id', $societyId)->where('status', 1)->get();

        $member = $id ? Member::findOrFail($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'member_prefix', 'member_name', 'flat_no', 'building_id', 'wing_id',
                'floor_no', 'unit_type', 'area', 'carpet', 'commercial', 'residential', 'terrace',
                'member_email', 'member_phone', 'member_parking_no', 'gstin_no',
                'op_principal', 'op_interest', 'op_tax', 'penality',
                'supplementary_principal', 'supplementary_interest', 'supplementary_tax', 'supplementary_penality',
                'op_bill_due_date', 'op_bill_date', 'joint_member_name',
            ]);

            $data['society_id'] = $societyId;
            $data['floor_no'] = $data['floor_no'] ?: 0;
            $data['area'] = $data['area'] ?: 0;
            $data['carpet'] = $data['carpet'] ?: 0;
            $data['commercial'] = $data['commercial'] ?: 0;
            $data['residential'] = $data['residential'] ?: 0;
            $data['terrace'] = $data['terrace'] ?: 0;
            $data['op_principal'] = $data['op_principal'] ?: 0;
            $data['op_interest'] = $data['op_interest'] ?: 0;
            $data['op_tax'] = $data['op_tax'] ?: 0;
            $data['penality'] = $data['penality'] ?: 0;
            $data['supplementary_principal'] = $data['supplementary_principal'] ?: 0;
            $data['supplementary_interest'] = $data['supplementary_interest'] ?: 0;
            $data['supplementary_tax'] = $data['supplementary_tax'] ?: 0;
            $data['supplementary_penality'] = $data['supplementary_penality'] ?: 0;
            $data['joint_member_name'] = $data['joint_member_name'] ?: '';
            $data['member_prefix'] = $data['member_prefix'] ?: '';
            $data['member_email'] = $data['member_email'] ?: '';
            $data['member_phone'] = $data['member_phone'] ?: '';
            $data['member_parking_no'] = $data['member_parking_no'] ?: '';
            $data['gstin_no'] = $data['gstin_no'] ?: '';
            $data['op_bill_due_date'] = $data['op_bill_due_date'] ?: null;
            $data['op_bill_date'] = $data['op_bill_date'] ?: null;

            if ($member) {
                $member->update($data);
                $msg = 'Member has been updated successfully.';
            } else {
                $data['status'] = 1;
                Member::create($data);
                $msg = 'Member has been added successfully.';
            }

            return redirect()->route('society.memberIdentity')->with('success', $msg);
        }

        return view('society.modules.add-member', compact('member', 'buildings', 'wings'));
    }

    public function deleteMember($id)
    {
        $member = Member::where('society_id', $this->societyId())->findOrFail($id);
        $member->update(['status' => 0]);

        return redirect()->route('society.memberIdentity')->with('success', 'The Member has been deleted.');
    }

    public function deleteAllMembers()
    {
        Member::where('society_id', $this->societyId())->update(['status' => 0]);

        return redirect()->route('society.memberIdentity')->with('success', 'All members have been deleted.');
    }

    public function downloadMemberTemplate()
    {
        $filePath = public_path('files/SampleMembersIdentities.csv');
        return response()->download($filePath, 'SampleMembersIdentities.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadMemberOpeningBalance()
    {
        $societyId = $this->societyId();
        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->with(['building', 'wing'])
            ->orderBy('flat_no')
            ->get();

        $filename = 'Member_Opening_Balance_Template_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($members) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member ID', 'Member Name', 'Flat/Shop No', 'Building Name', 'Wing Name', 'Mobile No', 'Email Address', 'Area', 'Opening Principal', 'Opening Interest', 'Opening Tax']);
            foreach ($members as $m) {
                fputcsv($out, [
                    $m->id,
                    trim($m->member_prefix . ' ' . $m->member_name),
                    $m->flat_no ?? '',
                    $m->building->building_name ?? '',
                    $m->wing->wing_name ?? '',
                    $m->member_phone ?? '',
                    $m->member_email ?? '',
                    $m->area ?? '',
                    $m->op_principal ?? '0.00',
                    $m->op_interest ?? '0.00',
                    $m->op_tax ?? '0.00',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function uploadMemberOpeningBalance(Request $request)
    {
        $request->validate(['member_csv' => 'required|file|mimes:csv,txt']);

        $societyId = $this->societyId();
        $file = $request->file('member_csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 11) continue;
            $memberId = $row[0];
            $member = Member::where('id', $memberId)->where('society_id', $societyId)->first();
            if ($member) {
                $member->update([
                    'op_principal' => $row[8] ?: 0,
                    'op_interest' => $row[9] ?: 0,
                    'op_tax' => $row[10] ?: 0,
                ]);
                $updated++;
            }
        }
        fclose($handle);

        return redirect()->route('society.memberIdentity')->with('success', "{$updated} members opening balance updated.");
    }

    public function uploadMemberCsv(Request $request)
    {
        $request->validate([
            'member_csv' => 'required|file|mimes:csv,txt',
            'building_id' => 'nullable|integer',
            'wing_id' => 'nullable|integer',
        ]);

        $societyId = $this->societyId();
        $buildingId = $request->input('building_id', '');
        $wingId = $request->input('wing_id', '');
        $file = $request->file('member_csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $added = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (!array_filter($row)) continue;

            $dataArr = [
                'building_id' => $buildingId,
                'wing_id' => $wingId,
                'society_id' => $societyId,
                'member_prefix' => $row[0] ?? '',
                'member_name' => $row[1] ?? '',
                'member_email' => $row[2] ?? '',
                'member_phone' => $row[3] ?? '',
                'floor_no' => !empty($row[4]) ? $row[4] : 0,
                'unit_type' => $row[5] ?? 'R',
                'flat_no' => $row[6] ?? '',
                'area' => !empty($row[7]) ? $row[7] : 0,
                'carpet' => !empty($row[8]) ? $row[8] : 0,
                'commercial' => !empty($row[9]) ? $row[9] : 0,
                'residential' => !empty($row[10]) ? $row[10] : 0,
                'terrace' => !empty($row[11]) ? $row[11] : 0,
                'op_principal' => !empty($row[12]) ? $row[12] : 0,
                'op_interest' => !empty($row[13]) ? $row[13] : 0,
                'op_tax' => !empty($row[14]) ? $row[14] : 0,
                'op_bill_date' => !empty($row[15]) ? date('Y-m-d', strtotime(trim($row[15]))) : null,
                'op_bill_due_date' => !empty($row[16]) ? date('Y-m-d', strtotime(trim($row[16]))) : null,
                'gstin_no' => $row[17] ?? '',
                'member_parking_no' => $row[18] ?? '',
                'status' => 1,
                'penality' => 0,
                'supplementary_principal' => 0,
                'supplementary_interest' => 0,
                'supplementary_tax' => 0,
                'supplementary_penality' => 0,
                'joint_member_name' => '',
            ];

            $existing = Member::where('society_id', $societyId)
                ->where('building_id', $buildingId)
                ->where('flat_no', $dataArr['flat_no'])
                ->first();

            if ($existing) {
                $dataArr['udate'] = now();
                $existing->update($dataArr);
                $updated++;
            } else {
                $dataArr['cdate'] = now();
                Member::create($dataArr);
                $added++;
            }
        }
        fclose($handle);

        $msg = '';
        if ($added > 0) $msg .= "{$added} members added. ";
        if ($updated > 0) $msg .= "{$updated} members updated. ";
        if ($added === 0 && $updated === 0) $msg = 'No members were imported. Please check the CSV file.';

        return redirect()->route('society.memberIdentity')->with('success', trim($msg));
    }

    public function memberTariff(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('member_name')
            ->get();

        $societyMemberList = [];
        foreach ($members as $m) {
            $societyMemberList[$m->id] = trim($m->member_prefix . ' ' . $m->member_name) . ' (' . $m->flat_no . ')';
        }

        $ledgerHeads = SocietyLedgerHead::where('society_id', $societyId)
            ->where('status', 1)
            ->where('is_in_bill_charges', 1)
            ->orderBy('title')
            ->get();

        $tariffOrder = SocietyTariffOrder::where('society_id', $societyId)->get()->keyBy('ledger_head_id');
        if ($tariffOrder->count() > 0) {
            $ledgerHeads = $ledgerHeads->sortBy(function ($lh) use ($tariffOrder) {
                return $tariffOrder[$lh->id]->tariff_serial ?? 9999;
            });
        }

        $buildings = Building::where('society_id', $societyId)->where('status', 1)->orderBy('building_name')->pluck('building_name', 'id');

        if ($request->isMethod('post') && $request->has('member_id')) {
            $memberId = $request->input('member_id');
            $effectiveDate = $request->input('tariff_effective_since', date('Y-m-d'));
            $remark = $request->input('remark', '');
            $rates = $request->input('rates', []);

            foreach ($rates as $ledgerHeadId => $amount) {
                if ($amount === null || $amount === '') continue;
                MemberTariff::updateOrCreate(
                    ['society_id' => $societyId, 'member_id' => $memberId, 'ledger_head_id' => $ledgerHeadId],
                    ['amount' => $amount, 'financial_year_id' => $fyId, 'updated_date' => now()]
                );
            }

            MemberTariffDetail::updateOrCreate(
                ['member_id' => $memberId],
                ['tariff_effective_since' => $effectiveDate, 'remark' => $remark, 'updated_date' => now()]
            );

            return redirect()->route('society.memberTariff')->with('success', 'Member tariff saved successfully.');
        }

        return view('society.modules.member-tariff', compact('societyMemberList', 'ledgerHeads', 'buildings'));
    }

    public function getMemberTariffData(Request $request)
    {
        $societyId = $this->societyId();
        $memberId = $request->input('member_id');
        $member = Member::find($memberId);
        $tariffs = MemberTariff::where('society_id', $societyId)
            ->where('member_id', $memberId)
            ->get()
            ->keyBy('ledger_head_id');
        $tariffDetail = MemberTariffDetail::where('member_id', $memberId)->first();

        return response()->json([
            'flat_no' => $member->flat_no ?? '',
            'tariffs' => $tariffs,
            'effective_date' => $tariffDetail->tariff_effective_since ?? '',
            'remark' => $tariffDetail->remark ?? '',
        ]);
    }

    public function memberTariffUpload(Request $request)
    {
        $societyId = $this->societyId();
        $buildings = Building::where('society_id', $societyId)->where('status', 1)->orderBy('building_name')->pluck('building_name', 'id');

        if ($request->isMethod('post') && $request->hasFile('member_tariff_csv')) {
            $file = $request->file('member_tariff_csv');
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext !== 'csv') {
                return redirect()->route('society.memberTariffUpload')->with('error', 'Please upload a CSV file.');
            }

            $handle = fopen($file->getRealPath(), 'r');
            $headerRow = fgetcsv($handle);

            $memberTariffPost = [];
            while (($row = fgetcsv($handle)) !== false) {
                $count = count($row);
                $memberId = $row[1] ?? '';
                if (empty($memberId)) continue;

                for ($i = 3; $i < $count; $i++) {
                    $headerVal = $headerRow[$i] ?? '';
                    $tariffNameArray = explode('|*|', $headerVal);
                    $tariffID = $tariffNameArray[1] ?? '';
                    $tariffName = $tariffNameArray[0] ?? '';

                    if (!empty($tariffID)) {
                        $memberTariffPost[$memberId][$tariffID] = $row[$i] ?? 0;
                    }
                    if (!empty($tariffName) && empty($tariffID)) {
                        $memberTariffPost[$memberId]['Remark'] = $row[$i] ?? '';
                    }
                }
            }
            fclose($handle);

            $failCnt = 0;
            foreach ($memberTariffPost as $memberId => $membTariffDetails) {
                $member = Member::where('id', $memberId)->where('society_id', $societyId)->first();
                if (!$member) continue;

                $tariffSerialNo = 1;
                foreach ($membTariffDetails as $ledgerHeadId => $tariffAmount) {
                    if ($tariffAmount !== '' && $ledgerHeadId !== 'Remark') {
                        try {
                            MemberTariff::updateOrCreate(
                                ['society_id' => $societyId, 'member_id' => $memberId, 'ledger_head_id' => $ledgerHeadId],
                                ['amount' => $tariffAmount, 'tariff_serial' => $tariffSerialNo, 'updated_date' => now()]
                            );
                        } catch (\Exception $e) {
                            $failCnt++;
                        }
                        $tariffSerialNo++;
                    }

                    if ($ledgerHeadId === 'Remark') {
                        try {
                            MemberTariffDetail::updateOrCreate(
                                ['member_id' => $memberId],
                                ['remark' => $tariffAmount, 'tariff_effective_since' => now()->format('Y-m-d'), 'updated_date' => now()]
                            );
                        } catch (\Exception $e) {
                            $failCnt++;
                        }
                    }
                }
            }

            $message = $failCnt > 0 ? 'Member Tariff Uploaded With Some Errors' : 'Member Tariff Uploaded Successfully';
            return redirect()->route('society.memberTariff')->with('success', $message);
        }

        return view('society.modules.member-tariff-upload', compact('buildings'));
    }

    public function downloadMemberTariffData(Request $request)
    {
        $societyId = $this->societyId();
        $buildingId = $request->input('building_id');
        $wingId = $request->input('wing_id');

        $query = Member::where('society_id', $societyId)->where('status', 1);
        if ($buildingId) $query->where('building_id', $buildingId);
        if ($wingId) $query->where('wing_id', $wingId);
        $members = $query->orderBy('id', 'asc')->get();

        if (SocietyTariffOrder::where('society_id', $societyId)->exists()) {
            $ledgerHeads = SocietyTariffOrder::where('society_id', $societyId)
                ->join('society_ledger_heads', 'society_tariff_orders.ledger_head_id', '=', 'society_ledger_heads.id')
                ->where('society_ledger_heads.status', 1)
                ->where('society_ledger_heads.society_id', $societyId)
                ->orderBy('society_tariff_orders.id', 'asc')
                ->select('society_ledger_heads.id', 'society_ledger_heads.title')
                ->get();
        } else {
            $ledgerHeads = SocietyLedgerHead::where('society_id', $societyId)
                ->where('status', 1)->where('is_in_bill_charges', 1)->orderBy('title')->get();
        }

        $filename = 'Member_Tariff.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($members, $ledgerHeads, $societyId) {
            $out = fopen('php://output', 'w');
            $headerRow = ['Member Name', 'Member ID', 'Flat No'];
            foreach ($ledgerHeads as $lh) {
                $headerRow[] = $lh->title . '|*|' . $lh->id;
            }
            $headerRow[] = 'Remark';
            fputcsv($out, $headerRow);

            foreach ($members as $m) {
                $tariffs = MemberTariff::where('society_id', $societyId)
                    ->where('member_id', $m->id)->get()->keyBy('ledger_head_id');
                $tariffDetail = MemberTariffDetail::where('member_id', $m->id)->first();
                $row = [$m->member_name, $m->id, $m->flat_no];
                foreach ($ledgerHeads as $lh) {
                    $row[] = isset($tariffs[$lh->id]) ? $tariffs[$lh->id]->amount : '';
                }
                $row[] = $tariffDetail->remark ?? '';
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getWingsByBuilding(Request $request)
    {
        $wings = Wing::where('building_id', $request->input('building_id'))
            ->where('status', 1)->orderBy('wing_name')->pluck('wing_name', 'id');
        return response()->json($wings);
    }

    public function getAllMemberTariffDetails(Request $request)
    {
        $societyId = $this->societyId();
        $buildingId = $request->input('building_id');
        $wingId = $request->input('wing_id');

        if (SocietyTariffOrder::where('society_id', $societyId)->exists()) {
            $ledgerHeads = SocietyTariffOrder::where('society_id', $societyId)
                ->join('society_ledger_heads', 'society_tariff_orders.ledger_head_id', '=', 'society_ledger_heads.id')
                ->where('society_ledger_heads.status', 1)
                ->where('society_ledger_heads.society_id', $societyId)
                ->orderBy('society_tariff_orders.id', 'asc')
                ->select('society_ledger_heads.id', 'society_ledger_heads.title')
                ->get();
        } else {
            $ledgerHeads = SocietyLedgerHead::where('society_id', $societyId)
                ->where('status', 1)->where('is_in_bill_charges', 1)->orderBy('title')->get();
        }

        $memberQuery = Member::where('society_id', $societyId)->where('status', 1);
        if ($buildingId) $memberQuery->where('building_id', $buildingId);
        if ($wingId) $memberQuery->where('wing_id', $wingId);
        $members = $memberQuery->orderBy('id', 'asc')->get();

        $memberTariffData = [];
        $ledgerTotals = [];
        foreach ($ledgerHeads as $lh) {
            $ledgerTotals[$lh->id] = 0;
        }

        foreach ($members as $member) {
            $tariffs = MemberTariff::where('society_id', $societyId)
                ->where('member_id', $member->id)->get()->keyBy('ledger_head_id');

            $rowTotal = 0;
            $ledgerData = [];
            foreach ($ledgerHeads as $lh) {
                $amount = isset($tariffs[$lh->id]) ? $tariffs[$lh->id]->amount : '0.00';
                $ledgerData[$lh->id] = $amount;
                $rowTotal += floatval($amount);
                $ledgerTotals[$lh->id] += floatval($amount);
            }

            $memberTariffData[] = [
                'member_id' => $member->id,
                'member_name' => trim($member->member_prefix . ' ' . $member->member_name),
                'flat_no' => $member->flat_no,
                'ledger_data' => $ledgerData,
                'total' => number_format($rowTotal, 2, '.', ''),
            ];
        }

        return response()->json([
            'ledger_heads' => $ledgerHeads->map(fn($lh) => ['id' => $lh->id, 'title' => $lh->title]),
            'members' => $memberTariffData,
            'ledger_totals' => $ledgerTotals,
            'grand_total' => number_format(array_sum($ledgerTotals), 2, '.', ''),
        ]);
    }

    public function updateAllMemberTariffDetails(Request $request)
    {
        $societyId = $this->societyId();
        $tariffData = $request->input('MemberTariff', []);
        $failCnt = 0;

        foreach ($tariffData as $memberId => $ledgerAmounts) {
            foreach ($ledgerAmounts as $ledgerHeadId => $amount) {
                $serialOrder = SocietyTariffOrder::where('society_id', $societyId)
                    ->where('ledger_head_id', $ledgerHeadId)->first();

                try {
                    MemberTariff::updateOrCreate(
                        ['society_id' => $societyId, 'member_id' => $memberId, 'ledger_head_id' => $ledgerHeadId],
                        [
                            'amount' => $amount ?? 0,
                            'tariff_serial' => $serialOrder->tariff_serial ?? null,
                            'updated_date' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    $failCnt++;
                }
            }
        }

        if ($failCnt > 0) {
            return response()->json(['error' => 1, 'error_message' => 'The member tariff could not be saved. Please, try again.']);
        }
        return response()->json(['error' => 0, 'error_message' => 'The member tariff has been Updated.']);
    }

    public function tenantMemberIdentity()
    {
        $societyId = $this->societyId();
        $items = Tenant::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('tenant_name')
            ->get();

        return view('society.modules.tenant-member-identity', compact('items'));
    }

    public function addTenant(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $editItem = $id ? Tenant::findOrFail($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'tenant_name', 'lease_type', 'agreement_on', 'rent_per_month',
                'nationality', 'address', 'city', 'state_id', 'country_id',
                'phone', 'email', 'building_id', 'wing_id', 'flat_no',
            ]);
            $data['society_id'] = $societyId;

            if ($editItem) {
                $data['udate'] = now();
                $editItem->update($data);
            } else {
                $data['status'] = 1;
                $data['cdate'] = now();
                $data['udate'] = now();
                Tenant::create($data);
            }

            return redirect()->route('society.tenantMemberIdentity')->with('success', 'Tenant data saved successfully.');
        }

        $buildings = Building::where('society_id', $societyId)->where('status', 1)->pluck('building_name', 'id');
        $wings = Wing::where('society_id', $societyId)->where('status', 1)->get();

        return view('society.modules.add-tenant', compact('editItem', 'buildings', 'wings'));
    }

    public function deleteTenant($id)
    {
        $societyId = $this->societyId();
        Tenant::where('id', $id)->where('society_id', $societyId)->update(['status' => 0]);
        return redirect()->route('society.tenantMemberIdentity')->with('success', 'The tenant has been deleted.');
    }

    public function memberPayments()
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $paymentModes = [1 => 'Cash', 3 => 'Cheque', 2 => 'NEFT', 4 => 'Other'];
        $items = MemberPayment::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->with('member')
            ->orderByDesc('id')
            ->get();

        return view('society.modules.member-payments', compact('items', 'fyId', 'paymentModes'));
    }

    public function addMemberPayment(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $members = Member::where('society_id', $societyId)->where('status', 1)->orderBy('member_name')->get();
        $societyBanks = SocietyBank::where('society_id', $societyId)->get();
        $paymentModes = [1 => 'Cash', 3 => 'Cheque', 2 => 'NEFT', 4 => 'Other'];
        $editItem = $id ? MemberPayment::findOrFail($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'member_id', 'payment_date', 'amount_paid', 'payment_mode',
                'society_bank_id', 'cheque_reference_number', 'credited_date', 'narration',
            ]);
            $data['society_id'] = $societyId;
            $data['financial_year_id'] = $fyId;
            $data['bill_type'] = $request->input('bill_type', 'reg');

            $settlementService = app(BillSettlementService::class);
            $memberTransfer = $settlementService->getLatestTransferNo($data['member_id'], $societyId);
            $data['member_transfer'] = $memberTransfer;

            if ($editItem) {
                $oldMemberId = $editItem->member_id;
                $oldBillType = $editItem->bill_type ?: 'reg';
                $oldTransfer = $editItem->member_transfer ?? 0;
                $editItem->update($data);

                if ($oldMemberId != $data['member_id']) {
                    $settlementService->recalculateMemberBills(
                        $oldMemberId, $societyId, $oldBillType, $fyId, $oldTransfer
                    );
                }
            } else {
                $data['receipt_id'] = (MemberPayment::where('society_id', $societyId)->max('receipt_id') ?? 0) + 1;
                MemberPayment::create($data);
            }

            $settlementService->recalculateMemberBills(
                $data['member_id'], $societyId, $data['bill_type'], $fyId, $memberTransfer
            );

            return redirect()->route('society.memberPayments')->with('success', $editItem ? 'Payment updated.' : 'Payment added.');
        }

        return view('society.modules.add-member-payment', compact('members', 'societyBanks', 'paymentModes', 'editItem'));
    }

    public function deleteMemberPayment($id)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $payment = MemberPayment::where('id', $id)->where('society_id', $societyId)->first();

        if ($payment) {
            $memberId = $payment->member_id;
            $billType = $payment->bill_type ?: 'reg';
            $memberTransfer = $payment->member_transfer ?? 0;

            MemberBillSettlement::where('payment_id', $id)->delete();
            $payment->delete();

            app(BillSettlementService::class)->recalculateMemberBills(
                $memberId, $societyId, $billType, $fyId, $memberTransfer
            );
        }

        return redirect()->route('society.memberPayments')->with('success', 'Payment deleted.');
    }

    public function memberReceipt()
    {
        return view('society.modules.member-receipt');
    }

    public function loadMemberPaymentReceipts(Request $request)
    {
        $societyId = $this->societyId();

        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();

        $banks = Bank::orderBy('bank_name', 'asc')->get();

        $bankBalanceSubCatId = SocietyHeadSubCategory::where('title', 'Bank Balances')->value('id');
        $bankLists = [];
        if ($bankBalanceSubCatId) {
            $bankLists = SocietyLedgerHead::where('society_id', $societyId)
                ->where('status', 1)
                ->where('society_head_sub_category_id', $bankBalanceSubCatId)
                ->orderBy('title', 'asc')
                ->pluck('title', 'id')
                ->toArray();
        }

        $memberBalanceAmount = [];
        $balanceData = \DB::select("SELECT member_id, balance_amount FROM member_bill_summaries WHERE id IN (SELECT MAX(id) FROM member_bill_summaries WHERE society_id = ? GROUP BY member_id)", [$societyId]);
        foreach ($balanceData as $row) {
            $memberBalanceAmount[$row->member_id] = $row->balance_amount;
        }

        $memberPaymentRemarks = [];
        $remarks = MemberPayment::where('society_id', $societyId)
            ->where('narration', '!=', '')
            ->whereNotNull('narration')
            ->select('narration', 'member_id')
            ->orderBy('id', 'asc')
            ->get();
        foreach ($remarks as $r) {
            $memberPaymentRemarks[$r->member_id][] = ['narration' => $r->narration];
        }

        return view('society.modules.load-member-payment-receipts', compact(
            'members', 'banks', 'bankLists', 'memberBalanceAmount', 'memberPaymentRemarks'
        ));
    }

    public function saveMemberPaymentReceipts(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $insertFlag = false;

        $memberPayments = $request->input('data.MemberPayments', []);

        if (!empty($memberPayments)) {
            foreach ($memberPayments as $paymentData) {
                $amountPaid = floatval($paymentData['amount_paid'] ?? 0);
                if ($amountPaid <= 0) continue;

                $receiptId = MemberPayment::where('society_id', $societyId)
                    ->where('financial_year_id', $fyId)
                    ->max('receipt_id');
                $receiptId = $receiptId ? $receiptId + 1 : 1;

                $memberId = $paymentData['member_id'] ?? '';
                $paymentDate = !empty($paymentData['payment_date']) ? date('Y-m-d', strtotime($paymentData['payment_date'])) : date('Y-m-d');

                $billType = 'reg';
                $billCount = MemberBillSummary::where('member_id', $memberId)
                    ->where('society_id', $societyId)
                    ->where('bill_type', $billType)
                    ->where('bill_generated_date', '<=', $paymentDate)
                    ->count();

                $aData = [
                    'society_id' => $societyId,
                    'member_id' => $memberId,
                    'society_bank_id' => $paymentData['society_bank_id'] ?? null,
                    'amount_paid' => $amountPaid,
                    'receipt_id' => $receiptId,
                    'payment_mode' => $paymentData['reciept_payment_mode'] ?? 3,
                    'cheque_reference_number' => $paymentData['cheque_reference_number'] ?? '',
                    'payment_date' => $paymentDate,
                    'credited_date' => null,
                    'member_bank_id' => $paymentData['member_bank_id'] ?? null,
                    'member_bank_ifsc' => $paymentData['member_bank_ifsc'] ?? '',
                    'member_bank_branch' => $paymentData['member_bank_branch'] ?? '',
                    'entry_date' => !empty($paymentData['cheque_date']) ? date('Y-m-d', strtotime($paymentData['cheque_date'])) : null,
                    'financial_year_id' => $fyId,
                    'narration' => $paymentData['narration'] ?? '',
                    'bill_type' => $billType,
                ];

                $settlementService = app(BillSettlementService::class);
                $memberTransfer = $settlementService->getLatestTransferNo($memberId, $societyId);
                $aData['member_transfer'] = $memberTransfer;

                MemberPayment::create($aData);
                $insertFlag = true;

                $settlementService->recalculateMemberBills(
                    $memberId, $societyId, $billType, $fyId, $memberTransfer
                );
            }
        }

        if ($insertFlag) {
            return response()->json(['error' => 0, 'error_message' => 'Member payment has been made successfully.']);
        }

        return response()->json(['error' => 1, 'error_message' => 'Member payment data could not saved.']);
    }

    public function importMemberPayments()
    {
        $societyId = $this->societyId();
        $societyBanks = SocietyBank::where('society_id', $societyId)
            ->with('bankLedgerHead')
            ->get();
        $bankList = [];
        foreach ($societyBanks as $sb) {
            $bankList[$sb->id] = $sb->bankLedgerHead->title ?? ('Bank #' . $sb->id);
        }

        return view('society.modules.import-member-payments', compact('bankList'));
    }

    public function importSocietyPayments()
    {
        $societyId = $this->societyId();
        $societyBanks = SocietyBank::where('society_id', $societyId)
            ->with('bankLedgerHead')
            ->get();
        $bankList = [];
        foreach ($societyBanks as $sb) {
            $bankList[$sb->id] = $sb->bankLedgerHead->title ?? ('Bank #' . $sb->id);
        }

        return view('society.modules.import-society-payments', compact('bankList'));
    }

    public function allGeneratedBills(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $query = MemberBillSummary::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->with('member');

        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        if ($request->isMethod('post')) {
            if ($fromDate) $query->where('bill_generated_date', '>=', $fromDate);
            if ($toDate) $query->where('bill_generated_date', '<=', $toDate);
        }

        $items = $query->orderByDesc('bill_generated_date')->get();

        return view('society.modules.generated-bills', compact('items', 'fyId', 'fromDate', 'toDate'));
    }

    public function createMemberLogins(Request $request)
    {
        $societyId = $this->societyId();

        if ($request->isMethod('post') && $request->input('createlogin')) {
            $members = Member::where('society_id', $societyId)->where('status', 1)->get();
            $created = 0;
            foreach ($members as $member) {
                if (!$member->member_email) continue;
                $existingUser = \App\Models\User::where('email', $member->member_email)->first();
                if ($existingUser) continue;

                $password = $this->randomPassword();
                \App\Models\User::create([
                    'name' => trim($member->member_prefix . ' ' . $member->member_name),
                    'email' => $member->member_email,
                    'password' => bcrypt($password),
                    'role' => 'Member',
                    'status' => 1,
                ]);
                $created++;
            }
            return redirect()->route('society.createMemberLogins')->with('success', "{$created} member logins created.");
        }

        return view('society.modules.create-member-logins');
    }

    private function randomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function updateOpeningBalance(Request $request)
    {
        $societyId = $this->societyId();
        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->with(['building', 'wing'])
            ->orderBy('id')
            ->get();

        if ($request->isMethod('post') && $request->hasFile('member_csv')) {
            $file = $request->file('member_csv');
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            $updated = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 6) continue;
                $memberId = $row[0];
                $member = Member::where('id', $memberId)->where('society_id', $societyId)->first();
                if ($member) {
                    $member->update([
                        'op_principal' => $row[3] ?: 0,
                        'op_interest' => $row[4] ?: 0,
                        'op_tax' => $row[5] ?: 0,
                    ]);
                    $updated++;
                }
            }
            fclose($handle);
            return redirect()->route('society.updateOpeningBalance')->with('success', "{$updated} members opening balance updated.");
        }

        return view('society.modules.update-opening-balance', compact('members'));
    }

    public function downloadMemberDetails()
    {
        $societyId = $this->societyId();
        $members = Member::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        $filename = 'MemberDetails_' . date('Y-m-d') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($members) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member ID', 'Member Name', 'Flat No', 'Principal Balance', 'Interest Balance', 'Tax Balance']);
            foreach ($members as $m) {
                fputcsv($out, [
                    $m->id,
                    trim($m->member_prefix . ' ' . $m->member_name),
                    $m->flat_no,
                    $m->op_principal ?? 0,
                    $m->op_interest ?? 0,
                    $m->op_tax ?? 0,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Employee Section ──────────────────────────────────────────

    public function employeeCategory(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $items = EmployeeCategory::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('emp_category_name')
            ->get();
        $editItem = $id ? EmployeeCategory::find($id) : null;

        if ($request->isMethod('post')) {
            $data = [
                'society_id' => $societyId,
                'emp_category_name' => $request->input('emp_category_name'),
                'udate' => now(),
            ];
            $editId = $request->input('id');
            if ($editId) {
                EmployeeCategory::where('id', $editId)->where('society_id', $societyId)->update($data);
            } else {
                $data['status'] = 1;
                $data['cdate'] = now();
                EmployeeCategory::create($data);
            }
            return redirect()->route('society.employeeCategory')->with('success', 'Employee category saved successfully.');
        }

        return view('society.modules.employee-category', compact('items', 'editItem'));
    }

    public function deleteEmployeeCategory($id)
    {
        EmployeeCategory::where('id', $id)->where('society_id', $this->societyId())->update(['status' => 0]);
        return redirect()->route('society.employeeCategory')->with('success', 'Employee category deleted.');
    }

    public function employeeSubCategory(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $items = EmployeeSubCategory::where('society_id', $societyId)
            ->where('status', 1)
            ->with('category')
            ->orderBy('emp_sub_category_name')
            ->get();
        $categories = EmployeeCategory::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('emp_category_name')
            ->pluck('emp_category_name', 'id');
        $editItem = $id ? EmployeeSubCategory::find($id) : null;

        if ($request->isMethod('post')) {
            $data = [
                'society_id' => $societyId,
                'emp_category_id' => $request->input('emp_category_id'),
                'emp_sub_category_name' => $request->input('emp_sub_category_name'),
                'udate' => now(),
            ];
            $editId = $request->input('id');
            if ($editId) {
                EmployeeSubCategory::where('id', $editId)->where('society_id', $societyId)->update($data);
            } else {
                $data['status'] = 1;
                $data['cdate'] = now();
                EmployeeSubCategory::create($data);
            }
            return redirect()->route('society.employeeSubCategory')->with('success', 'Employee sub category saved successfully.');
        }

        return view('society.modules.employee-sub-category', compact('items', 'categories', 'editItem'));
    }

    public function deleteEmployeeSubCategory($id)
    {
        EmployeeSubCategory::where('id', $id)->where('society_id', $this->societyId())->update(['status' => 0]);
        return redirect()->route('society.employeeSubCategory')->with('success', 'Employee sub category deleted.');
    }

    public function employeeDetails()
    {
        $societyId = $this->societyId();
        $items = Employee::where('society_id', $societyId)
            ->where('status', 1)
            ->with('category')
            ->orderBy('emp_name')
            ->get();

        return view('society.modules.employee-details', compact('items'));
    }

    public function addEmployee(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $categories = EmployeeCategory::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('emp_category_name')
            ->pluck('emp_category_name', 'id');
        $subCategories = EmployeeSubCategory::where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('emp_sub_category_name')
            ->pluck('emp_sub_category_name', 'id');
        $editItem = $id ? Employee::findOrFail($id) : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'emp_name', 'emp_code', 'emp_category_id', 'emp_sub_category_id',
                'joining_date', 'gender', 'date_of_birth', 'marrital_status',
                'date_of_leaving', 'religion', 'qualification', 'pan_no', 'gstin_no',
            ]);
            $data['society_id'] = $societyId;
            $data['udate'] = now();

            if ($editItem) {
                $editItem->update($data);
            } else {
                $data['status'] = 1;
                $data['cdate'] = now();
                Employee::create($data);
            }

            return redirect()->route('society.employeeDetails')->with('success', $editItem ? 'Employee updated.' : 'Employee added.');
        }

        return view('society.modules.add-employee', compact('categories', 'subCategories', 'editItem'));
    }

    public function deleteEmployee($id)
    {
        Employee::where('id', $id)->where('society_id', $this->societyId())->update(['status' => 0]);
        return redirect()->route('society.employeeDetails')->with('success', 'Employee deleted.');
    }

    public function getEmployeeSubCategories(Request $request)
    {
        $categoryId = $request->input('category_id');
        $societyId = $this->societyId();
        $subCategories = EmployeeSubCategory::where('emp_category_id', $categoryId)
            ->where('society_id', $societyId)
            ->where('status', 1)
            ->orderBy('emp_sub_category_name')
            ->pluck('emp_sub_category_name', 'id');
        return response()->json($subCategories);
    }

    // ─── Reports Section ───────────────────────────────────────────

    public function reportAccounts()
    {
        return view('society.modules.placeholder', [
            'title' => 'Report - Accounts',
        ]);
    }

    public function reportSociety()
    {
        return view('society.modules.placeholder', [
            'title' => 'Report - Society',
        ]);
    }

    public function journalVoucher()
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $items = JournalVoucher::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->orderByDesc('voucher_date')
            ->get();

        return view('society.modules.journal-voucher', compact('items', 'fyId'));
    }

    public function closingBalances()
    {
        return view('society.modules.placeholder', [
            'title' => 'Closing Balances',
        ]);
    }

    public function ledgerClosingTrialBalance()
    {
        return view('society.modules.placeholder', [
            'title' => 'Ledger - Closing vs Trial Balance',
        ]);
    }

    // ─── Utilities ─────────────────────────────────────────────────

    public function updateBillDates()
    {
        return view('society.modules.placeholder', [
            'title' => 'Update Bill Dates',
        ]);
    }

    // ─── Bill Print ────────────────────────────────────────────────

    public function billWithReceiptTabular()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill With Receipt Tabular',
        ]);
    }

    public function billTaxInvoiceGst()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill Tax Invoice GST',
        ]);
    }

    public function billFullPage()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill Full Page',
        ]);
    }

    public function billHalfPage()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill Half Page',
        ]);
    }

    public function billWithInterestGst()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill With Interest GST',
        ]);
    }

    public function billSummaryWithPrevData()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill Summary With Prev Data',
        ]);
    }

    public function billReceipt()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bill Receipt',
        ]);
    }

    // ─── Modal AJAX Methods ──────────────────────────────────────────

    public function getLedgerHeadDetails(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();
        $ledgerHeadId = $request->input('ledger_head_id', 0);

        $head = SocietyLedgerHead::with('accountHead')
            ->where('id', $ledgerHeadId)
            ->where('society_id', $sid)
            ->where('status', 1)
            ->first();

        if (!$head) {
            return response()->json(['status' => 0]);
        }

        $categoryId = $head->account_category_id;
        $openingAmount = ($categoryId == 3 || $categoryId == 4) ? 0 : ($head->opening_amount ?? 0);

        $fyStart = '';
        $fyMapping = \App\Models\SocietyYearMapping::where('society_id', $sid)
            ->where('financial_year_id', $fyId)->first();
        if ($fyMapping) {
            $fy = \App\Models\FinancialYearMaster::find($fyMapping->financial_year_id);
            if ($fy) $fyStart = $fy->start_date ?? '';
        }

        $ledgerEntries = [];
        $isInBill = $head->is_in_bill_charges;

        if ($isInBill == 0) {
            $payments = SocietyPayment::where('ledger_head_id', $ledgerHeadId)
                ->where('financial_year_id', $fyId)
                ->where('society_id', $sid)
                ->orderBy('payment_date')
                ->get();

            foreach ($payments as $p) {
                $txnType = $head->accountHead ? strtolower($head->accountHead->transaction_type ?? '') : '';
                $isDebit = stripos($txnType, 'debit') !== false;
                $ledgerEntries[] = [
                    'date' => date('d/m/Y', strtotime($p->payment_date)),
                    'particular' => $p->particulars ?? '',
                    'dr_amount' => $isDebit ? number_format($p->total_amount, 2, '.', '') : '0.00',
                    'cr_amount' => !$isDebit ? number_format($p->total_amount, 2, '.', '') : '0.00',
                ];
            }
        } else {
            $bills = MemberBillGenerate::where('ledger_head_id', $ledgerHeadId)
                ->where('society_id', $sid)
                ->orderBy('bill_generated_date')
                ->get();

            foreach ($bills as $b) {
                $member = Member::find($b->member_id);
                $monthName = date('F', strtotime($b->bill_generated_date));
                $ledgerEntries[] = [
                    'date' => date('d/m/Y', strtotime($b->bill_generated_date)),
                    'particular' => ($member ? $member->flat_no . ' - ' : '') . $monthName,
                    'dr_amount' => number_format($b->amount, 2, '.', ''),
                    'cr_amount' => '0.00',
                ];
            }
        }

        $jvEntries = JournalVoucher::where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->where(function ($q) use ($ledgerHeadId) {
                $q->where('jv_debit_ledger_head_id', $ledgerHeadId)
                  ->orWhere('jv_credit_ledger_head_id', $ledgerHeadId);
            })
            ->orderBy('voucher_date')
            ->get();

        foreach ($jvEntries as $jv) {
            $isDebit = $jv->jv_debit_ledger_head_id == $ledgerHeadId;
            $amt = $isDebit ? $jv->jv_amount_debited : $jv->jv_amount_credited;
            $ledgerEntries[] = [
                'date' => date('d/m/Y', strtotime($jv->voucher_date)),
                'particular' => 'JV ' . ($isDebit ? 'Debited' : 'Credited') . '. V.No ' . $jv->voucher_no,
                'dr_amount' => $isDebit ? number_format($amt, 2, '.', '') : '0.00',
                'cr_amount' => !$isDebit ? number_format($amt, 2, '.', '') : '0.00',
            ];
        }

        usort($ledgerEntries, function ($a, $b) {
            return strtotime(str_replace('/', '-', $a['date'])) - strtotime(str_replace('/', '-', $b['date']));
        });

        return response()->json([
            'id' => $head->id,
            'title' => $head->title,
            'short_code' => $head->short_code,
            'opening_amount' => $openingAmount,
            'account_category_id' => $head->account_category_id,
            'account_head_id' => $head->account_head_id,
            'society_head_sub_category_id' => $head->society_head_sub_category_id,
            'is_in_bill_charges' => $head->is_in_bill_charges,
            'is_tax_applicable' => $head->is_tax_applicable,
            'is_rebate_applicable' => $head->is_rebate_applicable,
            'is_interest_free' => $head->is_interest_free,
            'fy_start' => $fyStart,
            'ledger_entries' => $ledgerEntries,
            'status' => 1,
        ]);
    }

    public function updateLedgerHead(Request $request)
    {
        $sid = $this->societyId();
        $id = $request->input('id');

        $head = SocietyLedgerHead::where('id', $id)->where('society_id', $sid)->first();
        if (!$head) {
            return response()->json(['message' => 'Ledger head not found', 'error' => 1]);
        }

        $head->update([
            'short_code' => $request->input('short_code'),
            'opening_amount' => $request->input('opening_amount', 0),
            'society_head_sub_category_id' => $request->input('society_head_sub_category_id'),
            'account_head_id' => $request->input('account_head_id'),
            'account_category_id' => $request->input('account_category_id'),
            'is_in_bill_charges' => $request->input('is_in_bill_charges', 0),
            'is_tax_applicable' => $request->input('is_tax_applicable', 0),
            'is_rebate_applicable' => $request->input('is_rebate_applicable', 0),
            'is_interest_free' => $request->input('is_interest_free', 0),
        ]);

        return response()->json(['message' => 'Ledger head updated successfully', 'error' => 0]);
    }

    public function getMemberDetails(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();
        $memberId = $request->input('member_id', 0);
        $oldMember = $request->input('oldMember', 0);

        $member = Member::where('id', $memberId)
            ->where('society_id', $sid)
            ->where('status', 1)
            ->first();

        if (!$member) {
            return response()->json(['Member' => ['status' => 0]]);
        }

        $memberTransfer = $member->member_transfer ?? 0;

        if ($oldMember == 1 && $memberTransfer >= 1) {
            $memberTransfer = $memberTransfer - 1;
            $oldIdent = MemberIdentification::where('member_id', $memberId)
                ->where('society_id', $sid)
                ->orderBy('flat_no')
                ->first();
            if ($oldIdent && !empty($oldIdent->third_member)) {
                $member->member_name = $oldIdent->third_member;
            }
        }

        $prevMember = Member::where('id', '<', $memberId)
            ->where('society_id', $sid)->where('status', 1)
            ->orderBy('id', 'desc')->first();
        $nextMember = Member::where('id', '>', $memberId)
            ->where('society_id', $sid)->where('status', 1)
            ->orderBy('id', 'asc')->first();

        $building = Building::find($member->building_id);
        $wing = Wing::find($member->wing_id);

        $prevYearId = $fyId - 1;
        $closingData = DB::table('member_year_wise_closing_balance')
            ->where('member_id', $memberId)
            ->where('year_id', $prevYearId)
            ->get();

        $opPrincipal = $member->op_principal ?? 0;
        $opInterest = $member->op_interest ?? 0;
        $opTax = $member->op_tax ?? 0;
        $supPrincipal = $member->supplementary_principal ?? 0;
        $supInterest = $member->supplementary_interest ?? 0;
        $supTax = $member->supplementary_tax ?? 0;

        foreach ($closingData as $cd) {
            if ($cd->bill_type == 'reg') {
                $opPrincipal = $cd->principal_balance ?: 0;
                $opInterest = $cd->interest_balance ?: 0;
                $opTax = $cd->tax_balance ?: 0;
            } elseif ($cd->bill_type == 'sup') {
                $supPrincipal = $cd->principal_balance ?: 0;
                $supInterest = $cd->interest_balance ?: 0;
                $supTax = $cd->tax_balance ?: 0;
            }
        }

        $bills = MemberBillSummary::where('society_id', $sid)
            ->where('member_id', $memberId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $fyId)
            ->orderBy('id')
            ->get();

        $payments = DB::table('member_payments')
            ->where('society_id', $sid)
            ->where('member_id', $memberId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $fyId)
            ->orderBy('id')
            ->get();

        $chequeReturns = DB::table('cheque_return_details')
            ->where('member_id', $memberId)
            ->where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->get();

        $monthNames = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

        $billSummaryIds = $bills->pluck('id')->toArray();
        $settlements = [];
        $paymentIdsArr = [];
        if (!empty($billSummaryIds)) {
            $allSettlements = MemberBillSettlement::whereIn('bill_summary_id', $billSummaryIds)
                ->where('member_id', $memberId)->get();
            foreach ($allSettlements as $s) {
                $paymentIdsArr[] = $s->payment_id;
            }
        }

        $ledgerEntries = [];
        $noPaymentIdsArr = [];

        foreach ($bills as $bill) {
            $monthName = $monthNames[$bill->month] ?? $bill->month;
            $suplementary = ($bill->bill_type == 'sup') ? ' Suplementary' : '';
            $ledgerEntries[] = [
                'date' => $bill->bill_generated_date,
                'formattedDate' => date('d/m/Y', strtotime($bill->bill_generated_date)),
                'particular' => 'To Bill No ' . $bill->bill_no . ' For ' . $monthName . $suplementary,
                'debit' => number_format($bill->monthly_bill_amount, 2, '.', ''),
                'credit' => '0.00',
                'flag' => 'bill',
                'bill_id' => $bill->id,
                'month' => $bill->month,
            ];

            foreach ($payments as $payment) {
                if (in_array($payment->id, $noPaymentIdsArr)) continue;
                $billType = ($payment->bill_type == 'sup') ? ' - supplementary' : '';
                $chq = '';
                if (!empty($payment->cheque_reference_number)) {
                    $chq = ' and Cheque Reference Number ' . $payment->cheque_reference_number;
                }
                $noPaymentIdsArr[] = $payment->id;
                $ledgerEntries[] = [
                    'date' => $payment->payment_date,
                    'formattedDate' => date('d/m/Y', strtotime($payment->payment_date)),
                    'particular' => 'By Receipt V.No.' . $payment->receipt_id . $chq . $billType,
                    'debit' => '0.00',
                    'credit' => number_format($payment->amount_paid, 2, '.', ''),
                    'flag' => 'receipt',
                    'payment_id' => $payment->id,
                ];

                if (!empty($chequeReturns) && !empty($payment->cheque_reference_number)) {
                    foreach ($chequeReturns as $chData) {
                        if ($chData->cheque_no == $payment->cheque_reference_number && $chData->payment_id == $payment->id) {
                            $ledgerEntries[] = [
                                'date' => $chData->cheque_return_date,
                                'formattedDate' => date('d/m/Y', strtotime($chData->cheque_return_date)),
                                'particular' => 'Ret By Receipt V.No.' . $payment->receipt_id . ' Cheque Reference Number ' . $chData->cheque_no,
                                'debit' => number_format($chData->cheque_amount, 2, '.', ''),
                                'credit' => '0.00',
                                'flag' => '',
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($paymentIdsArr)) {
            $paymentIdsArr = array_unique($paymentIdsArr);
            $filteredIds = array_diff($paymentIdsArr, $noPaymentIdsArr);
            if (!empty($filteredIds)) {
                $extraPayments = DB::table('member_payments')
                    ->where('society_id', $sid)
                    ->where('member_id', $memberId)
                    ->whereIn('id', $filteredIds)
                    ->where('member_transfer', $memberTransfer)
                    ->where('financial_year_id', $fyId)
                    ->get();
                foreach ($extraPayments as $payment) {
                    $billType = ($payment->bill_type == 'sup') ? ' supplementary' : '';
                    $chq = '';
                    if (!empty($payment->cheque_reference_number)) {
                        $chq = ' and Cheque Reference Number ' . $payment->cheque_reference_number;
                    }
                    $ledgerEntries[] = [
                        'date' => $payment->payment_date,
                        'formattedDate' => date('d/m/Y', strtotime($payment->payment_date)),
                        'particular' => 'By Receipt V.No.' . $payment->receipt_id . $chq . $billType,
                        'debit' => '0.00',
                        'credit' => number_format($payment->amount_paid, 2, '.', ''),
                        'flag' => 'receipt',
                        'payment_id' => $payment->id,
                    ];

                    if (!empty($chequeReturns) && !empty($payment->cheque_reference_number)) {
                        foreach ($chequeReturns as $chData) {
                            if ($chData->cheque_no == $payment->cheque_reference_number && $chData->payment_id == $payment->id) {
                                $ledgerEntries[] = [
                                    'date' => $chData->cheque_return_date,
                                    'formattedDate' => date('d/m/Y', strtotime($chData->cheque_return_date)),
                                    'particular' => 'Ret By Receipt V.No.' . $payment->receipt_id . ' Cheque Reference Number ' . $chData->cheque_no,
                                    'debit' => number_format($chData->cheque_amount, 2, '.', ''),
                                    'credit' => '0.00',
                                    'flag' => '',
                                ];
                            }
                        }
                    }
                }
            }
        }

        $jvData = JournalVoucher::where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->where(function ($q) use ($memberId) {
                $q->where('jv_credit_member_head_id', $memberId)
                  ->orWhere('jv_debit_member_head_id', $memberId);
            })
            ->get();

        foreach ($jvData as $jv) {
            if ($jv->member_transfer !== null && $jv->member_transfer != $memberTransfer) continue;

            if (!empty($jv->jv_credit_member_head_id) && $jv->jv_credit_member_head_id == $memberId) {
                $ledgerEntries[] = [
                    'date' => $jv->voucher_date,
                    'formattedDate' => date('d/m/Y', strtotime($jv->voucher_date)),
                    'particular' => 'JV Credited. V.No ' . $jv->voucher_no . ' ' . $jv->note,
                    'debit' => '0.00',
                    'credit' => number_format($jv->jv_amount_credited, 2, '.', ''),
                    'flag' => 'jv',
                ];
            }

            if (!empty($jv->jv_debit_member_head_id) && $jv->jv_debit_member_head_id == $memberId) {
                $ledgerEntries[] = [
                    'date' => $jv->voucher_date,
                    'formattedDate' => date('d/m/Y', strtotime($jv->voucher_date)),
                    'particular' => 'JV Debited. V.No ' . $jv->voucher_no . ' ' . $jv->note,
                    'debit' => number_format($jv->jv_amount_debited, 2, '.', ''),
                    'credit' => '0.00',
                    'flag' => 'jv',
                ];
            }
        }

        usort($ledgerEntries, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        $yearStartDate = session('fy.year_start_date', date('Y') . '-04-01');
        $openingDate = date('d/m/Y', strtotime($yearStartDate));

        $memberTransfered = MemberIdentification::where('member_id', $memberId)->exists();

        $society = Society::where('user_id', $sid)->first();

        return response()->json([
            'Member' => [
                'id' => $member->id,
                'member_name' => $member->member_name,
                'flat_no' => $member->flat_no,
                'building_id' => $member->building_id,
                'wing_id' => $member->wing_id,
                'floor_no' => $member->floor_no,
                'unit_type' => $member->unit_type,
                'area' => $member->area,
                'carpet' => $member->carpet,
                'commercial' => $member->commercial,
                'residential' => $member->residential,
                'terrace' => $member->terrace,
                'op_principal' => $opPrincipal,
                'op_interest' => $opInterest,
                'op_tax' => $opTax,
                'supplementary_principal' => $supPrincipal,
                'supplementary_interest' => $supInterest,
                'supplementary_tax' => $supTax,
                'member_transfer' => $member->member_transfer,
                'previous_id' => $prevMember ? $prevMember->id : 0,
                'next_id' => $nextMember ? $nextMember->id : 0,
                'previous' => empty($prevMember) ? 1 : 0,
                'next' => empty($nextMember) ? 1 : 0,
                'status' => 1,
            ],
            'Building' => [
                'building_name' => $building ? $building->building_name : '',
            ],
            'Wing' => [
                'wing_name' => $wing ? $wing->wing_name : '',
            ],
            'Society' => [
                'society_name' => $society ? $society->society_name : '',
                'registration_no' => $society ? $society->registration_no : '',
                'address' => $society ? $society->address : '',
            ],
            'memberBillPayment' => $ledgerEntries,
            'openingdate' => $openingDate,
            'tranasfer_status' => $memberTransfered,
        ]);
    }

    public function getMemberDetailsByFlatNo(Request $request)
    {
        $sid = $this->societyId();
        $flatNo = $request->input('flat_no');
        $buildingId = $request->input('building_id');
        $wingId = $request->input('wing_id');

        $query = Member::where('society_id', $sid)
            ->where('status', 1)
            ->where('flat_no', $flatNo);

        if ($buildingId) $query->where('building_id', $buildingId);
        if ($wingId) $query->where('wing_id', $wingId);

        $member = $query->first();

        return response()->json(['id' => $member ? $member->id : 0]);
    }

    public function updateSocietyMemberDetails(Request $request)
    {
        $sid = $this->societyId();
        $memberId = $request->input('member_id');

        $member = Member::where('id', $memberId)->where('society_id', $sid)->first();
        if (!$member) {
            return response()->json(['error' => 1, 'message' => 'Member not found']);
        }

        $member->member_name = $request->input('member_name', $member->member_name);
        $member->building_id = $request->input('building_id', $member->building_id);
        $member->wing_id = $request->input('wing_id', $member->wing_id);
        $member->floor_no = $request->input('floor_no', $member->floor_no);
        $member->unit_type = $request->input('unit_type', $member->unit_type);
        $member->area = $request->input('area', $member->area);
        $member->carpet = $request->input('carpet', $member->carpet);
        $member->commercial = $request->input('commercial', $member->commercial);
        $member->residential = $request->input('residential', $member->residential);
        $member->terrace = $request->input('terrace', $member->terrace);
        $member->save();

        return response()->json(['error' => 0, 'message' => 'Member updated']);
    }

    public function getBillDetails(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();
        $memberId = $request->input('member_id', 0);
        $month = $request->input('month', '');

        $member = Member::where('id', $memberId)->where('society_id', $sid)->first();

        $billCondition = [
            'society_id' => $sid,
            'member_id' => $memberId,
            'financial_year_id' => $fyId,
        ];
        if ($month) $billCondition['month'] = $month;

        $bill = MemberBillSummary::where($billCondition)
            ->orderBy('id', 'desc')
            ->first();

        $charges = [];
        if ($bill) {
            $billCharges = MemberBillGenerate::where('society_id', $sid)
                ->where('member_id', $memberId)
                ->where('month', $bill->month)
                ->where('bill_number', $bill->bill_no)
                ->get();

            foreach ($billCharges as $ch) {
                $lh = SocietyLedgerHead::find($ch->ledger_head_id);
                $charges[] = [
                    'title' => $lh ? $lh->title : 'N/A',
                    'amount' => $ch->amount,
                ];
            }
        }

        return response()->json([
            'bill' => $bill,
            'charges' => $charges,
            'member_flat_no' => $member ? $member->flat_no : '',
            'member_area' => $member ? $member->area : '',
        ]);
    }

    public function getAllMembersBillSummaryDetails(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();

        $response = ['error' => 1, 'ifDelete' => 0, 'ifSettlement' => 0];

        $billMonth = $request->input('month', '');
        $billId = $request->input('id', '');
        $billNo = $request->input('bill_no', '');
        $billDate = $request->input('bill_generated_date', '');
        $memberId = $request->input('member_id', '');

        $query = MemberBillSummary::where('society_id', $sid);

        if ($billMonth) $query->where('month', $billMonth);
        if ($billId) {
            $query->where('id', $billId);
        }
        if ($billNo) $query->where('bill_no', $billNo);
        if ($billDate) $query->where('bill_generated_date', $billDate);
        if ($memberId) $query->where('member_id', $memberId);

        $bill = $query->orderBy('id', 'asc')->first();

        if (!$bill) {
            $response['memberrole'] = session('society_role', 'Society');
            return response()->json($response);
        }

        $memberTariffData = [];
        $billCharges = MemberBillGenerate::where('society_id', $sid)
            ->where('month', $bill->month)
            ->where('member_id', $bill->member_id)
            ->where('bill_number', $bill->bill_no)
            ->where('financial_year_id', $bill->financial_year_id)
            ->get();

        $tariffSr = 1;
        foreach ($billCharges as $ch) {
            $lh = SocietyLedgerHead::find($ch->ledger_head_id);
            $memberTariffData[] = [
                'tariff_serial' => $ch->tariff_serial ?? $tariffSr,
                'ledger_head_id' => $ch->ledger_head_id,
                'amount' => $ch->amount,
                'title' => $lh ? $lh->title : '',
            ];
            $tariffSr++;
        }

        $nextBill = MemberBillSummary::where('id', '>', $bill->id)
            ->where('society_id', $sid)
            ->orderBy('id', 'asc')
            ->select('id', 'month')
            ->first();

        $prevBill = MemberBillSummary::where('id', '<', $bill->id)
            ->where('society_id', $sid)
            ->orderBy('id', 'desc')
            ->select('id', 'month')
            ->first();

        $member = Member::find($bill->member_id);
        $building = $member ? Building::find($member->building_id) : null;
        $wing = $member ? Wing::find($member->wing_id) : null;

        $billData = $bill->toArray();
        $billData['next'] = $nextBill ? 1 : 0;
        $billData['previous'] = $prevBill ? 1 : 0;
        $billData['next_id'] = $nextBill ? $nextBill->id : 0;
        $billData['next_month'] = $nextBill ? $nextBill->month : 0;
        $billData['previous_id'] = $prevBill ? $prevBill->id : 0;
        $billData['previous_month'] = $prevBill ? $prevBill->month : 0;

        $paymentTotal = MemberPayment::where('society_id', $sid)
            ->where('member_id', $bill->member_id)
            ->where('bill_generated_id', $bill->bill_no)
            ->where('financial_year_id', $bill->financial_year_id)
            ->sum('amount_paid');

        $lastBill = MemberBillSummary::where('society_id', $sid)
            ->where('member_id', $bill->member_id)
            ->orderBy('bill_generated_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastBill && $lastBill->id == $bill->id) {
            $response['ifDelete'] = 1;
            $settlement = MemberBillSettlement::where('bill_summary_id', $bill->id)->first();
            $response['ifSettlement'] = $settlement ? 1 : 0;
        }

        $interestDetails = [];
        $societyParam = SocietyParameter::where('society_id', $sid)->first();
        if ($societyParam && $bill->interest_on_due_amount > 0) {
            $interestRate = $societyParam->interest_rate ?? 0;
            $interestType = \App\Models\InterestType::find($societyParam->interest_type_id);
            $interestMethod = \DB::table('interest_methods')->where('id', $societyParam->method_id)->first();

            $dueDate = $bill->bill_due_date;
            $billDate = $bill->bill_generated_date;
            $delayDays = 0;
            if ($dueDate && $billDate) {
                $d1 = new \DateTime($dueDate);
                $d2 = new \DateTime($billDate);
                $delayDays = $d1->diff($d2)->days;
            }

            $prevBillForInterest = MemberBillSummary::where('society_id', $sid)
                ->where('member_id', $bill->member_id)
                ->where('id', '<', $bill->id)
                ->orderBy('id', 'desc')
                ->first();
            $prevBillDate = $prevBillForInterest ? $prevBillForInterest->bill_generated_date : null;

            if (!$prevBillDate) {
                $yearStartDate = session('fy.year_start_date');
                if ($yearStartDate) {
                    $prevBillDate = $yearStartDate;
                }
            }

            if ($prevBillDate && $billDate) {
                $pd1 = new \DateTime($prevBillDate);
                $pd2 = new \DateTime($billDate);
                $cycleDays = $pd1->diff($pd2)->days;
            } else {
                $cycleDays = 0;
            }

            if ($cycleDays == 0 && $bill->interest_on_due_amount > 0 && $bill->op_principal_arrears > 0 && $interestRate > 0) {
                $cycleDays = round(($bill->interest_on_due_amount * 365) / ($bill->op_principal_arrears * $interestRate / 100));
            }

            $interestDetails = [
                'interest_rate' => $interestRate,
                'interest_type' => $interestType ? $interestType->interest_type : '',
                'interest_method' => $interestMethod ? $interestMethod->method_title : '',
                'delay_days' => $delayDays,
                'cycle_days' => $cycleDays,
                'due_date' => $dueDate,
                'bill_date' => $billDate,
                'prev_bill_date' => $prevBillDate,
                'interest_amount' => $bill->interest_on_due_amount,
                'principal_arrears' => $bill->op_principal_arrears,
            ];
        }

        $response['error'] = 0;
        $response['MemberBillSummary'] = $billData;
        $response['MemberTariff'] = $memberTariffData;
        $response['InterestDetails'] = $interestDetails;
        $response['Member'] = $member ? $member->toArray() : [];
        $response['Building'] = $building ? $building->toArray() : [];
        $response['Wing'] = $wing ? $wing->toArray() : [];
        $response['MemberPayment'] = ['amount_paid' => $paymentTotal];
        $response['memberrole'] = session('society_role', 'Society');

        return response()->json($response);
    }

    public function updateMemberBillSummaryById(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();
        $response = ['error' => 1, 'error_message' => 'Bill summary could not update'];

        $summaryId = $request->input('id');
        if (!$summaryId) {
            return response()->json($response);
        }

        $bill = MemberBillSummary::where('id', $summaryId)->where('society_id', $sid)->first();
        if (!$bill) {
            return response()->json($response);
        }

        $bill->update([
            'discount' => floatval($request->input('discount', 0)),
            'principal_adjusted' => floatval($request->input('principal_adjusted', 0)),
            'interest_adjusted' => floatval($request->input('interest_adjusted', 0)),
            'interest_on_due_amount' => floatval($request->input('interest_on_due_amount', 0)),
        ]);

        $settlementService = app(BillSettlementService::class);
        $memberTransfer = $settlementService->getLatestTransferNo($bill->member_id, $sid);
        $billType = $bill->bill_type ?: 'reg';

        $settlementService->recalculateMemberBills(
            $bill->member_id, $sid, $billType, $fyId, $memberTransfer
        );

        $response['error'] = 0;
        $response['error_message'] = '';
        return response()->json($response);
    }

    public function generateBill(Request $request)
    {
        set_time_limit(2700);

        $sid = $this->societyId();
        $fyId = $this->fyId();

        $buildingId = $request->input('building_id');
        $wingId = $request->input('wing_id');
        $billGeneratedDate = $request->input('bill_date');
        $billDueDate = $request->input('due_date');
        $billStartingNo = $request->input('starting_no', 1);
        $supplementary = $request->input('supplementary', 0);
        $billGeneratedMonths = $request->input('month');

        if (!$billGeneratedMonths || !$billGeneratedDate) {
            return response()->json(['error' => 1, 'error_message' => 'Bill month and date are required']);
        }

        $billType = $supplementary ? 'sup' : 'reg';

        $memberQuery = Member::where('society_id', $sid)->where('status', 1)->orderBy('id', 'asc');
        if ($buildingId) $memberQuery->where('building_id', $buildingId);
        if ($wingId) $memberQuery->where('wing_id', $wingId);
        $societyMemberLists = $memberQuery->with('tariffs')->get();

        if ($societyMemberLists->isEmpty()) {
            return response()->json(['error' => 1, 'error_message' => 'No members found']);
        }

        $billLedgerHeadSettings = $this->memberRegularBillLedgerHeadSettings($sid);

        $societyTariffParameterCheck = SocietyParameter::where('society_id', $sid)->first();
        if (!$societyTariffParameterCheck) {
            return response()->json(['error' => 1, 'error_message' => 'Society parameters not set']);
        }

        $multiplyBillingValue = $this->multiplyTariffAmountByBillingFrequency($societyTariffParameterCheck);
        $billEndDate = $this->getBillEndDate($societyTariffParameterCheck, $billGeneratedDate);
        $memberListWithTransferStatus = $this->memberTransferList($sid, $fyId);
        $memberClosingbalance = $this->getMembersLastClosingBalance($sid, $fyId, $billType);

        $response = ['error' => 1, 'error_message' => 'Member Tariff has not been set.'];

        foreach ($societyMemberLists as $memberRow) {
            $memberDetails = $memberRow;
            $memberId = $memberDetails->id;
            $member_transfer = $memberDetails->member_transfer ?? 0;
            $memberTariffs = $memberRow->tariffs;

            $transferInCurrentyear = false;
            if (!empty($memberListWithTransferStatus)) {
                $memberTransferData = $memberListWithTransferStatus[$memberId] ?? [];
                $transferInCurrentyear = !empty($memberTransferData);
            }

            $billNo = $this->memberBillsUniqueNumber($sid, $fyId, $billStartingNo);

            if ($memberTariffs->isNotEmpty()) {
                $responseArray = $this->calculateMembersRegularBillByTariff(
                    $memberId, $memberTariffs, $billNo,
                    $billGeneratedMonths, $billGeneratedDate, $billDueDate,
                    $billLedgerHeadSettings, $memberDetails, $member_transfer,
                    $transferInCurrentyear, $societyTariffParameterCheck,
                    $sid, $fyId, $multiplyBillingValue, $billEndDate,
                    $memberClosingbalance, $billType
                );
                if (!empty($responseArray)) {
                    $response = $responseArray;
                }
            }
        }

        return response()->json($response);
    }

    private function memberRegularBillLedgerHeadSettings($sid)
    {
        $ledgerHeads = SocietyLedgerHead::where('status', 1)
            ->where('society_id', $sid)
            ->where('is_in_bill_charges', 1)
            ->get(['id', 'is_tax_applicable', 'is_rebate_applicable', 'is_interest_free']);

        $settings = [];
        foreach ($ledgerHeads as $head) {
            if ($head->is_tax_applicable == 1) {
                $settings['tax'][] = $head->id;
            }
            if ($head->is_rebate_applicable == 1) {
                $settings['rebate'][] = $head->id;
            }
            if ($head->is_interest_free == 1) {
                $settings['interestFree'][] = $head->id;
            }
        }
        return $settings;
    }

    private function multiplyTariffAmountByBillingFrequency($params)
    {
        $multiplyBillingValue = 1;
        if (isset($params->is_tariff_mothly) && $params->is_tariff_mothly == 1) {
            switch ($params->billing_frequency_id) {
                case 1: $multiplyBillingValue = 1; break;
                case 2: $multiplyBillingValue = 2; break;
                case 3: $multiplyBillingValue = 3; break;
                case 4: $multiplyBillingValue = 4; break;
                case 5: $multiplyBillingValue = 6; break;
                case 6: $multiplyBillingValue = 12; break;
                default: $multiplyBillingValue = 1; break;
            }
        }
        return $multiplyBillingValue;
    }

    private function getBillEndDate($params, $billGeneratedDate)
    {
        $freqId = $params->billing_frequency_id;
        $monthsToAdd = [1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 5, 6 => 11];
        $add = $monthsToAdd[$freqId] ?? 0;

        $dt = \Carbon\Carbon::parse($billGeneratedDate)->addMonths($add);
        return $dt->endOfMonth()->format('Y-m-d');
    }

    private function memberTransferList($sid, $fyId)
    {
        $rows = DB::table('members')
            ->leftJoin('member_identifications', 'member_identifications.member_id', '=', 'members.id')
            ->where('members.society_id', $sid)
            ->where('member_identifications.financial_year_id', $fyId)
            ->select('members.id', 'members.member_transfer', 'member_identifications.member_id', 'member_identifications.financial_year_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = (array) $row;
        }
        return $result;
    }

    private function getMembersLastClosingBalance($sid, $fyId, $billType = 'reg')
    {
        $yearId = $fyId - 1;
        $rows = DB::table('member_year_wise_closing_balance')
            ->where('society_id', $sid)
            ->where('year_id', $yearId)
            ->where('bill_type', $billType)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->member_id] = (array) $row;
        }
        return $result;
    }

    private function memberBillsUniqueNumber($sid, $fyId, $billStartingNo = 1)
    {
        $billNumber = empty($billStartingNo) ? 1 : intval($billStartingNo);

        $maxBillNo = MemberBillSummary::where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->max('bill_no');

        if ($maxBillNo !== null && $maxBillNo >= 0) {
            $billNumber = $maxBillNo + 1;
        }

        return $billNumber;
    }

    private function societyMemberBillExists($memberId, $billGeneratedMonths, $billType, $fyId)
    {
        return MemberBillGenerate::where('member_id', $memberId)
            ->where('month', $billGeneratedMonths)
            ->where('bill_type', $billType)
            ->where('financial_year_id', $fyId)
            ->first();
    }

    private function getMonthNoFromBillFreq($billFreq)
    {
        if ($billFreq == 1) return 1;
        elseif ($billFreq == 2) return 2;
        elseif ($billFreq == 3) return 3;
        elseif ($billFreq == 4) return 4;
        elseif ($billFreq == 5) return 6;
        else return 12;
    }

    private function completeMonths($params, $lastMonthBillDetails, $billGeneratedDate)
    {
        $interestOnDue = 0;
        if (!empty($lastMonthBillDetails['bill_generated_date']) && !empty($billGeneratedDate)) {
            if (!empty($lastMonthBillDetails['bill_frequency_id'])) {
                $delayMonth = $this->getMonthNoFromBillFreq($lastMonthBillDetails['bill_frequency_id']);
            } else {
                $date1 = \Carbon\Carbon::parse($billGeneratedDate);
                $date2 = \Carbon\Carbon::parse($lastMonthBillDetails['bill_generated_date']);
                $delayMonth = $date1->diffInMonths($date2);
            }

            $interestTypeId = $params->interest_type_id;
            $interestRate = $params->interest_rate;

            if ($interestTypeId == 1) {
                $interestOnDue = 0;
            } elseif ($interestTypeId == 2) {
                $principalAmount = floatval($lastMonthBillDetails['principal_balance'] ?? 0) - floatval($lastMonthBillDetails['interest_free_amount'] ?? 0);
                $interestPerMonth = $principalAmount * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            } elseif ($interestTypeId == 3) {
                $balAmount = floatval($lastMonthBillDetails['balance_amount'] ?? 0) - floatval($lastMonthBillDetails['interest_free_amount'] ?? 0);
                $interestPerMonth = $balAmount * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            }
        }
        return $interestOnDue;
    }

    private function delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate, &$totalDays)
    {
        $interestOnDue = 0;
        $date1 = \Carbon\Carbon::parse($billDueDate);
        $date2 = \Carbon\Carbon::parse($paymentDate)->addDay();
        $delayDays = $date1->diffInDays($date2);
        $interestRate = $params->interest_rate;
        $interestTypeId = $params->interest_type_id;

        if ($interestTypeId == 1) {
            $interestOnDue = 0;
        } elseif ($interestTypeId == 2) {
            $totalDays += $delayDays;
            if ($principalAmt > 0) {
                $interestForYear = $principalAmt * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            }
        } elseif ($interestTypeId == 3) {
            if ($totalBal > 0) {
                $interestForYear = $totalBal * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            }
        }
        return $interestOnDue;
    }

    private function delayMonths($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate)
    {
        $interestOnDue = 0;
        $date1 = \Carbon\Carbon::parse($billDueDate);
        $date2 = \Carbon\Carbon::parse($paymentDate)->addDay();
        $delayDays = $date1->diffInDays($date2);
        $interestRate = $params->interest_rate;
        $interestTypeId = $params->interest_type_id;

        if ($interestTypeId == 1) {
            $interestOnDue = 0;
        } elseif ($interestTypeId == 2) {
            if ($principalAmt > 0) {
                $interestForYear = $principalAmt * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            }
        } elseif ($interestTypeId == 3) {
            if ($totalBal > 0) {
                $interestForYear = $principalAmt * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            }
        }
        return $interestOnDue;
    }

    private function completeCycleDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate)
    {
        $interestOnDue = 0;
        if (!empty($lastBillGeneratedDate) && !empty($billGeneratedDate)) {
            $date1 = \Carbon\Carbon::parse($billGeneratedDate)->addDay();
            $date2 = \Carbon\Carbon::parse($lastBillGeneratedDate);
            $delayDays = $date2->diffInDays($date1);
            $interestRate = $params->interest_rate;
            $interestTypeId = $params->interest_type_id;

            if ($interestTypeId == 1) {
                $interestOnDue = 0;
            } elseif ($interestTypeId == 2) {
                $interestForYear = $principalAmt * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            } elseif ($interestTypeId == 3) {
                $interestForYear = $totalBal * ($interestRate / 100);
                $interestOnDue = round(($interestForYear / 365) * $delayDays);
            }
        }
        return $interestOnDue;
    }

    private function completeCycleMonthly($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate)
    {
        $interestOnDue = 0;
        if (!empty($lastBillGeneratedDate) && !empty($billGeneratedDate)) {
            $date1 = \Carbon\Carbon::parse($billGeneratedDate)->addDay();
            $date2 = \Carbon\Carbon::parse($lastBillGeneratedDate);
            $diff = $date2->diff($date1);
            $delayMonth = $diff->format('%m');
            $interestRate = $params->interest_rate;
            $interestTypeId = $params->interest_type_id;

            if ($interestTypeId == 1) {
                $interestOnDue = 0;
            } elseif ($interestTypeId == 2) {
                $interestPerMonth = $principalAmt * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            } elseif ($interestTypeId == 3) {
                $interestPerMonth = $totalBal * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            }
        }
        return $interestOnDue;
    }

    private function interestCalculation(&$amountPaid, &$principalAmt, &$interestAmt, &$taxAmount)
    {
        if ($taxAmount > 0 && $amountPaid > 0) {
            $amountPaid = $amountPaid - $taxAmount;
            if ($amountPaid > 0) { $taxAmount = 0; } else { $taxAmount = abs($amountPaid); $amountPaid = 0; }
        }
        if ($interestAmt > 0 && $amountPaid > 0) {
            $amountPaid = $amountPaid - $interestAmt;
            if ($amountPaid > 0) { $interestAmt = 0; } else { $interestAmt = abs($amountPaid); $amountPaid = 0; }
        }
        if ($principalAmt > 0 && $amountPaid > 0) {
            $amountPaid = $amountPaid - $principalAmt;
            if ($amountPaid > 0) { $principalAmt = 0; } else { $principalAmt = abs($amountPaid); $amountPaid = 0; }
        }
    }

    private function getChequeRetPaymentId($memberId, $societyId, $memberTransfer, $fyId)
    {
        $cheRetPayId = [];
        $rows = DB::table('cheque_return_details')
            ->where('member_id', $memberId)
            ->where('society_id', $societyId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $fyId)
            ->get();
        foreach ($rows as $row) {
            if ($row->payment_id > 0) {
                $cheRetPayId[] = $row->payment_id;
            }
        }
        return $cheRetPayId;
    }

    private function getCreditedJvData($endDate, $memberId, $societyId, $paymentData, $memberTransfer, $fyId)
    {
        $jvRows = DB::table('journal_vouchers')
            ->where('voucher_date', '<=', $endDate)
            ->where('jv_credit_member_head_id', $memberId)
            ->where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->where('member_transfer', $memberTransfer)
            ->select('id', 'jv_amount_credited', 'voucher_date')
            ->get();

        if ($jvRows->isNotEmpty()) {
            foreach ($jvRows as $key => $jvData) {
                $paymentData[] = [
                    'id' => 'JV-' . $jvData->id,
                    'amount_paid' => $jvData->jv_amount_credited,
                    'payment_date' => $jvData->voucher_date,
                ];
            }
            usort($paymentData, function ($a, $b) {
                return strtotime($a['payment_date']) <=> strtotime($b['payment_date']);
            });
        }
        return $paymentData;
    }

    private function getDebitedJvAmount($fromDate, $toDate, $memberId, $societyId, $memberTransfer, $fyId)
    {
        if (empty($fromDate) || empty($toDate)) return 0;
        return floatval(DB::table('journal_vouchers')
            ->where('voucher_date', '>=', $fromDate)
            ->where('voucher_date', '<=', $toDate)
            ->where('jv_debit_member_head_id', $memberId)
            ->where('society_id', $societyId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $fyId)
            ->sum('jv_amount_debited'));
    }

    private function setInterestData(&$principalAmt, &$totalBal, $calculatedBillSummary)
    {
        if (!empty($calculatedBillSummary)) {
            $countIndex = count($calculatedBillSummary) - 1;
            $totalPrincipal = $calculatedBillSummary[$countIndex]['principal'];
            $totalTax = $calculatedBillSummary[$countIndex]['tax'];
            $totalInterest = $calculatedBillSummary[$countIndex]['interest'];
            $principalAmt = $totalPrincipal;
            $totalBal = $totalPrincipal + $totalTax + $totalInterest;
        }
    }

    private function interestDeductAndUpdate(&$amountPaid, &$billData)
    {
        $cnt = count($billData);
        foreach ($billData as $index => $data) {
            if ($amountPaid > 0 && ($data['tax'] ?? 0) > 0) {
                $tempTaxPaid = $amountPaid - $data['tax'];
                if ($tempTaxPaid > 0) {
                    $taxPaid = $data['tax'];
                    $billData[$index]['tax'] = 0;
                    $amountPaid = $tempTaxPaid;
                } else {
                    $billData[$index]['tax'] = abs($tempTaxPaid);
                    $taxPaid = abs($amountPaid);
                    $amountPaid = 0;
                }
                for ($t = ($index + 1); $t < $cnt; $t++) {
                    $billData[$t]['tax'] = $billData[$t]['tax'] - $taxPaid;
                }
            }
            if ($amountPaid > 0 && ($data['interest'] ?? 0) > 0) {
                $tempInterestPaid = $amountPaid - $data['interest'];
                if ($tempInterestPaid > 0) {
                    $interestPaid = $data['interest'];
                    $billData[$index]['interest'] = 0;
                    $amountPaid = $tempInterestPaid;
                } else {
                    $billData[$index]['interest'] = abs($tempInterestPaid);
                    $interestPaid = abs($amountPaid);
                    $amountPaid = 0;
                }
                for ($i = ($index + 1); $i < $cnt; $i++) {
                    $billData[$i]['interest'] = $billData[$i]['interest'] - $interestPaid;
                }
            }
            if ($amountPaid > 0 && ($data['principal'] ?? 0) > 0) {
                $tempPrincipalPaid = $amountPaid - $data['principal'];
                if ($tempPrincipalPaid > 0) {
                    $principalPaid = $data['principal'];
                    $billData[$index]['principal'] = 0;
                    $amountPaid = $tempPrincipalPaid;
                } else {
                    $billData[$index]['principal'] = abs($tempPrincipalPaid);
                    $principalPaid = abs($amountPaid);
                    $amountPaid = 0;
                }
                for ($p = ($index + 1); $p < $cnt; $p++) {
                    $billData[$p]['principal'] = $billData[$p]['principal'] - $principalPaid;
                }
            }
        }
    }

    private function calculateAmountAvailable($memberId, $societyId, $chqRetPayId, $lastBillGeneratedDate, $lastBillId, $monthlyBills, &$principalAmt, &$interestAmt, &$taxAmount, $billType, $memberTransfer, $fyId)
    {
        $paymentQuery = DB::table('member_payments')
            ->where('member_id', $memberId)
            ->where('payment_date', '<', $lastBillGeneratedDate)
            ->where('society_id', $societyId)
            ->where('bill_type', $billType)
            ->where('member_transfer', $memberTransfer);
        if (!empty($chqRetPayId)) {
            $paymentQuery->whereNotIn('id', $chqRetPayId);
        }
        $memberPaymentRows = $paymentQuery->orderBy('payment_date', 'asc')->get();

        $memberPaymentData = [];
        foreach ($memberPaymentRows as $row) {
            $memberPaymentData[] = ['id' => $row->id, 'amount_paid' => $row->amount_paid, 'payment_date' => $row->payment_date];
        }

        if ($billType == 'reg') {
            $memberPaymentData = $this->getCreditedJvData($lastBillGeneratedDate, $memberId, $societyId, $memberPaymentData, $memberTransfer, $fyId);
        }

        $principalAmt = 0;
        $interestAmt = 0;
        $taxAmount = 0;
        $paymentIdUsed = [];
        $amountPaid = 0;
        $tempSummary = [];

        if (!empty($monthlyBills)) {
            foreach ($monthlyBills as $index => $billData) {
                $bd = is_array($billData) ? $billData : $billData->toArray();
                $monthlyPrincipalAmt = $bd['monthly_amount'] - $bd['discount'];

                $jvDebitedAmt = 0;
                $tempPrincipal = $monthlyPrincipalAmt + $jvDebitedAmt - $bd['tax_total'] - $bd['principal_adjusted'];
                $principalAmt += $tempPrincipal;

                $tempInterest = $bd['interest_on_due_amount'] - $bd['interest_adjusted'];
                $interestAmt += $tempInterest;

                $tempTax = $bd['tax_total'] - $bd['tax_adjusted'];
                $taxAmount += $tempTax;

                if ($index == 0) {
                    $principalAmt += $bd['op_principal_arrears'];
                    $taxAmount += $bd['op_tax_arrears'];
                    $interestAmt += $bd['op_interest_arrears'];
                }
                $billId = $bd['id'];

                if (!empty($memberPaymentData)) {
                    foreach ($memberPaymentData as $paymentData) {
                        $paymentId = $paymentData['id'];
                        if (!in_array($paymentId, $paymentIdUsed) && $amountPaid <= 0 && ($principalAmt > 0 || $interestAmt > 0 || $taxAmount > 0)) {
                            $amountPaid += $paymentData['amount_paid'];
                            $paymentIdUsed[] = $paymentId;
                        }
                        $this->interestCalculation($amountPaid, $principalAmt, $interestAmt, $taxAmount);
                    }
                }
                $tempSummary[$index] = ['principal' => $principalAmt, 'interest' => $interestAmt, 'tax' => $taxAmount];
                if ($billId == $lastBillId) break;
            }
        }
        return $tempSummary;
    }

    private function getInterestOnDueAmount($params, $lastMonthBillDetails, $billGeneratedDate, $memberId, $sid, $memberAllBills, $billType, $memberTransfer, $fyId)
    {
        $totalDays = 0;
        $interestAmt = 0;
        $methodId = $params->method_id;

        if ($methodId == 5) {
            $interestAmt = $this->completeMonths($params, $lastMonthBillDetails, $billGeneratedDate);
        } else {
            $chqRetPayId = $this->getChequeRetPaymentId($memberId, $sid, $memberTransfer, $fyId);
            $principalAmt = $interestAmtCalc = $taxAmount = 0;

            $lastBillId = $lastMonthBillDetails['id'] ?? 0;
            $lastBillGeneratedDate = $lastMonthBillDetails['bill_generated_date'] ?? '';
            $billDueDate = $lastMonthBillDetails['bill_due_date'] ?? '';

            $calculatedBillSummary = $this->calculateAmountAvailable($memberId, $sid, $chqRetPayId, $lastBillGeneratedDate, $lastBillId, $memberAllBills, $principalAmt, $interestAmtCalc, $taxAmount, $billType, $memberTransfer, $fyId);

            if (!empty($billDueDate) && !empty($billGeneratedDate)) {
                $paymentQuery = DB::table('member_payments')
                    ->where('member_id', $memberId)
                    ->where('payment_date', '<', $billGeneratedDate)
                    ->where('payment_date', '>=', $lastBillGeneratedDate)
                    ->where('society_id', $sid)
                    ->where('bill_type', $billType)
                    ->where('member_transfer', $memberTransfer);
                if (!empty($chqRetPayId)) {
                    $paymentQuery->whereNotIn('id', $chqRetPayId);
                }
                $memberPaymentData = $paymentQuery->orderBy('payment_date', 'asc')->get();

                $billGeneratedDateAdj = date('Y-m-d', strtotime('-1 day', strtotime($billGeneratedDate)));

                if ($memberPaymentData->isNotEmpty()) {
                    $paymentCount = $memberPaymentData->count();
                    $byMonth = 0;
                    foreach ($memberPaymentData as $index => $paymentData) {
                        $paymentDate = $paymentData->payment_date;
                        $amountPaid = $paymentData->amount_paid;

                        if (strtotime($paymentDate) <= strtotime($billDueDate)) {
                            $this->interestDeductAndUpdate($amountPaid, $calculatedBillSummary);
                        } else {
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            if ($methodId == 1) {
                                $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                                $interestAmt += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj, $totalDays);
                                $this->interestDeductAndUpdate($amountPaid, $calculatedBillSummary);
                                $billDueDate = $paymentDate;
                            } elseif ($methodId == 2) {
                                if (empty($byMonth)) $billDueDate = $lastBillGeneratedDate;
                                $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                                $interestAmt += $this->delayMonths($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                                $this->interestDeductAndUpdate($amountPaid, $calculatedBillSummary);
                                $billDueDate = $paymentDate;
                                $byMonth = 1;
                            } elseif ($methodId == 3) {
                                $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                                $interestAmt += $this->completeCycleDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                                break;
                            } elseif ($methodId == 4) {
                                $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                                $interestAmt += $this->completeCycleMonthly($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                                break;
                            }
                        }

                        if ($methodId == 1 && $index == ($paymentCount - 1)) {
                            $paymentDate = $billGeneratedDateAdj;
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                            $interestAmt += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj, $totalDays);
                        }
                        if ($methodId == 2 && $index == ($paymentCount - 1)) {
                            if (empty($byMonth)) { $billDueDate = $lastBillGeneratedDate; $byMonth = 1; }
                            $paymentDate = $billGeneratedDateAdj;
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                            $interestAmt += $this->delayMonths($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                        }
                        if ($methodId == 4 && $index == ($paymentCount - 1)) {
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                            $interestAmt += $this->completeCycleMonthly($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                        }
                        if ($methodId == 3 && $index == ($paymentCount - 1)) {
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                            $interestAmt += $this->completeCycleDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                        }
                    }
                } else {
                    $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                    $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                    if ($methodId == 1) {
                        $paymentDate = $billGeneratedDateAdj;
                        $interestAmt += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj, $totalDays);
                    } elseif ($methodId == 2) {
                        $billDueDate = $lastBillGeneratedDate;
                        $paymentDate = $billGeneratedDateAdj;
                        $interestAmt += $this->delayMonths($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj);
                    } elseif ($methodId == 3) {
                        $interestAmt += $this->completeCycleDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate ?? '', $lastBillGeneratedDate, $billGeneratedDateAdj);
                    } elseif ($methodId == 4) {
                        $interestAmt += $this->completeCycleMonthly($principalAmt, $totalBal, $params, $billDueDate, $paymentDate ?? '', $lastBillGeneratedDate, $billGeneratedDateAdj);
                    }
                }
            }
        }
        return ($interestAmt < 0) ? 0 : $interestAmt;
    }

    private function getInterestOnDueAmountOnFirstBill($principalAmt, $interestAmt, $taxAmount, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate)
    {
        $interestAmtResult = 0;
        $methodId = $params->method_id;
        $totalBal = $principalAmt + $interestAmt + $taxAmount;

        if ($methodId == 5) {
            $lastMonthBillDetails = [
                'bill_generated_date' => $lastBillGeneratedDate,
                'principal_balance' => $principalAmt,
                'balance_amount' => $totalBal,
            ];
            $interestAmtResult = $this->completeMonths($params, $lastMonthBillDetails, $billGeneratedDate);
        }

        $days = 0;
        if ($methodId == 1) {
            $interestAmtResult += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate, $days);
        } elseif ($methodId == 2) {
            $interestAmtResult += $this->delayMonths($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate);
        } elseif ($methodId == 3) {
            $interestAmtResult += $this->completeCycleDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate);
        } elseif ($methodId == 4) {
            $interestAmtResult += $this->completeCycleMonthly($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate);
        }

        return ($interestAmtResult < 0) ? 0 : $interestAmtResult;
    }

    private function updateGeneratedBillBalanceAmountInAdvance(&$data, $dueAmount)
    {
        if ($dueAmount > 0) {
            $dueAmount = $dueAmount - $data['tax_balance'];
            if ($dueAmount <= 0) {
                $data['tax_balance'] = abs($dueAmount);
                $dueAmount = 0;
            } else {
                $data['tax_balance'] = 0;
            }
        }

        if ($dueAmount > 0) {
            $dueAmount = $dueAmount - $data['interest_balance'];
            if ($dueAmount <= 0) {
                $data['interest_balance'] = abs($dueAmount);
                $dueAmount = 0;
            } else {
                $data['interest_balance'] = 0;
            }
        }

        if ($dueAmount > 0) {
            $dueAmount = $dueAmount - $data['principal_balance'];
            if ($dueAmount <= 0) {
                $data['principal_balance'] = abs($dueAmount);
                $dueAmount = 0;
            } else {
                $data['principal_balance'] = -$dueAmount;
            }
        }
    }

    private function calculateMembersRegularBillByTariff(
        $memberId, $memberTariffs, $paymentBillNo,
        $billGeneratedMonths, $billGeneratedDate, $billDueDate,
        $billLedgerHeadSettings, $memberDetails, $member_transfer,
        $transferInCurrentyear, $params,
        $sid, $fyId, $multiplyTariffAmountValue, $billEndDate,
        $memberClosingbalance, $billType
    ) {
        $responseError = [];

        $societyMemberBillExists = $this->societyMemberBillExists($memberId, $billGeneratedMonths, $billType, $fyId);

        if (!empty($societyMemberBillExists)) {
            return ['error' => 1, 'error_message' => 'Members Bill already exists in the system with the same month.'];
        }

        $jvAdjustment = 0;
        $interestTypeId = $params->interest_type_id;

        $memberAllBills = MemberBillSummary::where('member_id', $memberId)
            ->where('society_id', $sid)
            ->where('bill_type', $billType)
            ->where('member_transfer', $member_transfer)
            ->orderBy('id', 'asc')
            ->get();

        $lastMonthBillDetails = null;
        if ($memberAllBills->isNotEmpty()) {
            $lastMonthBillDetails = $memberAllBills->last()->toArray();
        }

        $memberBillSummaryData = [];

        if (!empty($lastMonthBillDetails)) {
            $balanceAmount = floatval($lastMonthBillDetails['balance_amount']);
            $memberBillSummaryData['op_principal_arrears_original'] = floatval($lastMonthBillDetails['principal_balance']);
            $memberBillSummaryData['jv_adjustment'] = $jvAdjustment;
            $memberBillSummaryData['op_principal_arrears'] = $memberBillSummaryData['op_principal_arrears_original'] + $jvAdjustment;
            $opDueAmount = $memberBillSummaryData['op_due_amount'] = floatval($lastMonthBillDetails['balance_amount']) + $jvAdjustment;
            $memberBillSummaryData['op_interest_arrears'] = floatval($lastMonthBillDetails['interest_balance']);
            $memberBillSummaryData['op_tax_arrears'] = floatval($lastMonthBillDetails['tax_balance']);
            $memberBillSummaryData['tax_balance'] = floatval($lastMonthBillDetails['tax_balance']);
            $memberBillSummaryData['interest_on_due_amount'] = 0;

            if ($opDueAmount > 0 && $interestTypeId != 4) {
                $memberBillSummaryData['interest_on_due_amount'] = $this->getInterestOnDueAmount($params, $lastMonthBillDetails, $billGeneratedDate, $memberId, $sid, $memberAllBills, $billType, $member_transfer, $fyId);
            }
        } else {
            $opPrincipal = floatval($memberDetails->op_principal ?? 0);
            $opInterest = floatval($memberDetails->op_interest ?? 0);
            $opTax = floatval($memberDetails->op_tax ?? 0);

            if ($transferInCurrentyear) {
                $opPrincipal = 0;
                $opTax = 0;
                $opInterest = 0;
            } else {
                if (!empty($memberClosingbalance[$memberId])) {
                    $prevClosingBal = $memberClosingbalance[$memberId];
                    if ($member_transfer == ($prevClosingBal['member_transfer'] ?? 0)) {
                        $opPrincipal = floatval($prevClosingBal['principal_balance'] ?? 0);
                        $opTax = floatval($prevClosingBal['tax_balance'] ?? 0);
                        $opInterest = floatval($prevClosingBal['interest_balance'] ?? 0);
                    }
                }
            }

            $lastMonthBillDetails = [];
            $lastMonthBillDetails['principal_balance'] = $opPrincipal;
            $lastMonthBillDetails['balance_amount'] = $opPrincipal + $opInterest + $opTax;
            $lastMonthBillDetails['bill_due_date'] = $memberDetails->op_bill_due_date ?? '';
            $lastMonthBillDetails['bill_generated_date'] = $memberDetails->op_bill_date ?? '';
            $lastMonthBillDetails['interest_free_amount'] = 0;

            $memberBillSummaryData['op_principal_arrears_original'] = $opPrincipal;
            $memberBillSummaryData['jv_adjustment'] = $jvAdjustment;
            $memberBillSummaryData['op_principal_arrears'] = $opPrincipal + $jvAdjustment;
            $memberBillSummaryData['op_interest_arrears'] = $opInterest;
            $memberBillSummaryData['op_tax_arrears'] = $opTax;
            $memberBillSummaryData['op_due_amount'] = $opPrincipal + $opInterest + $opTax;
            $memberBillSummaryData['interest_on_due_amount'] = 0;
            $memberBillSummaryData['tax_balance'] = $opTax;

            $opBillDueDate = $lastMonthBillDetails['bill_due_date'];
            if (!empty($opBillDueDate) && $opBillDueDate != '0000-00-00' && $memberBillSummaryData['op_due_amount'] > 0) {
                if ($interestTypeId != 4) {
                    $principalAmtForInterest = !empty($memberDetails->op_principal) ? $memberDetails->op_principal : 0;
                    $interestAmtForInterest = !empty($memberDetails->op_interest) ? $memberDetails->op_interest : 0;
                    $taxAmtForInterest = !empty($memberDetails->op_tax) ? $memberDetails->op_tax : 0;
                    $lastBillGenDate = $lastMonthBillDetails['bill_generated_date'];
                    $memberBillSummaryData['interest_on_due_amount'] = $this->getInterestOnDueAmountOnFirstBill($principalAmtForInterest, $interestAmtForInterest, $taxAmtForInterest, $params, $lastBillGenDate, $opBillDueDate, $lastBillGenDate, $billGeneratedDate);
                }
            }
        }

        $memberBillSummaryData['bill_no'] = $paymentBillNo;
        $memberBillSummaryData['month'] = $billGeneratedMonths;
        $memberBillSummaryData['member_id'] = $memberId;
        $memberBillSummaryData['bill_type'] = $billType;
        $memberBillSummaryData['society_id'] = $sid;
        $memberBillSummaryData['flat_no'] = $memberDetails->flat_no ?? '';
        $memberBillSummaryData['monthly_amount'] = 0;
        $memberBillSummaryData['monthly_principal_amount'] = 0;
        $memberBillSummaryData['discount'] = 0;
        $memberBillSummaryData['monthly_bill_amount'] = 0;
        $memberBillSummaryData['amount_payable'] = 0;
        $memberBillSummaryData['principal_paid'] = 0;
        $memberBillSummaryData['interest_paid'] = 0;
        $memberBillSummaryData['principal_adjusted'] = 0;
        $memberBillSummaryData['interest_adjusted'] = 0;
        $memberBillSummaryData['principal_balance'] = 0;
        $memberBillSummaryData['interest_balance'] = 0;
        $memberBillSummaryData['balance_amount'] = 0;
        $memberBillSummaryData['igst_total'] = 0;
        $memberBillSummaryData['cgst_total'] = 0;
        $memberBillSummaryData['sgst_total'] = 0;
        $memberBillSummaryData['tax_total'] = 0;
        $memberBillSummaryData['tax_paid'] = 0;
        $memberBillSummaryData['tax_adjusted'] = 0;
        $memberBillSummaryData['interest_free_amount'] = 0;
        $memberBillSummaryData['bill_tariff_type'] = $params->tariff_id;
        $memberBillSummaryData['bill_generated_date'] = trim($billGeneratedDate);
        $memberBillSummaryData['bill_due_date'] = !empty($billDueDate) ? trim($billDueDate) : null;
        $memberBillSummaryData['bill_frequency_id'] = $params->billing_frequency_id;
        $memberBillSummaryData['bill_end_date'] = $billEndDate;
        $memberBillSummaryData['financial_year_id'] = $fyId;

        $billMonthNo = $memberBillSummaryData['month'];
        $memberTarrifAmount = $this->getTotalMemberTariff($memberId, $billMonthNo, $sid);
        $gstLimit = $params->gst_limit ?? 0;

        $cgstPer = floatval($params->cgst_tax_per);
        $sgstPer = floatval($params->sgst_tax_per);
        $igstPer = floatval($params->igst_tax_per);

        if ($gstLimit > 0 && $memberTarrifAmount < $gstLimit) {
            $cgstPer = 0;
            $sgstPer = 0;
            $igstPer = 0;
        }

        $billGenerateArr = [];
        $tariffCounter = 0;
        $now = now()->format('Y-m-d H:i:s');

        foreach ($memberTariffs as $memberTariffData) {
            $tariffAmount = floatval($memberTariffData->amount ?? 0);
            $ledgerHeadId = intval($memberTariffData->ledger_head_id ?? 0);

            $row = [];
            $row['member_id'] = $memberTariffData->member_id ?: $memberId;
            $row['bill_generated_date'] = trim($billGeneratedDate);
            $row['month'] = $billGeneratedMonths;
            $row['bill_number'] = $paymentBillNo;
            $row['amount'] = $tariffAmount * $multiplyTariffAmountValue;
            $row['ledger_head_id'] = $ledgerHeadId;
            $row['society_id'] = $sid;
            $row['bill_type'] = $billType;
            $row['cdate'] = $now;
            $row['financial_year_id'] = $fyId;
            $row['igst_total'] = 0;
            $row['cgst_total'] = 0;
            $row['sgst_total'] = 0;
            $row['tax_total'] = 0;

            $memberBillSummaryData['monthly_amount'] += $tariffAmount * $multiplyTariffAmountValue;

            if (!empty($billLedgerHeadSettings['tax']) && in_array($ledgerHeadId, $billLedgerHeadSettings['tax'])) {
                $taxAmount = 0;

                if ($igstPer > 0) {
                    $igstTaxAmount = ($tariffAmount * $multiplyTariffAmountValue) * ($igstPer / 100);
                    $memberBillSummaryData['igst_total'] += $igstTaxAmount;
                    $row['igst_total'] = $igstTaxAmount;
                    $taxAmount += $igstTaxAmount;
                }

                if ($cgstPer > 0) {
                    $cgstTaxAmount = ($tariffAmount * $multiplyTariffAmountValue) * ($cgstPer / 100);
                    $memberBillSummaryData['cgst_total'] += $cgstTaxAmount;
                    $row['cgst_total'] = $cgstTaxAmount;
                    $taxAmount += $cgstTaxAmount;
                }

                if ($sgstPer > 0) {
                    $sgstTaxAmount = ($tariffAmount * $multiplyTariffAmountValue) * ($sgstPer / 100);
                    $memberBillSummaryData['sgst_total'] += $sgstTaxAmount;
                    $row['sgst_total'] = $sgstTaxAmount;
                    $taxAmount += $sgstTaxAmount;
                }

                $memberBillSummaryData['tax_total'] += $taxAmount;
                $row['tax_total'] = $taxAmount;
                $memberBillSummaryData['tax_balance'] += $taxAmount;
            }

            if (!empty($billLedgerHeadSettings['interestFree']) && in_array($ledgerHeadId, $billLedgerHeadSettings['interestFree'])) {
                $memberBillSummaryData['interest_free_amount'] += $tariffAmount * $multiplyTariffAmountValue;
            }

            $billGenerateArr[] = $row;
            $tariffCounter++;
        }

        if (!empty($billGenerateArr)) {
            $interestArreasgst = $params->gst_interest_arreas ?? '';
            if ($interestArreasgst == 'Y' && !empty($memberBillSummaryData['op_interest_arrears'])) {
                $cgstOnInterest = ($memberBillSummaryData['op_interest_arrears'] * $multiplyTariffAmountValue) * ($cgstPer / 100);
                $memberBillSummaryData['cgst_total'] += $cgstOnInterest;
                $sgstOnInterest = ($memberBillSummaryData['op_interest_arrears'] * $multiplyTariffAmountValue) * ($sgstPer / 100);
                $memberBillSummaryData['sgst_total'] += $sgstOnInterest;
                $memberBillSummaryData['tax_total'] += $cgstOnInterest + $sgstOnInterest;
                $memberBillSummaryData['tax_balance'] += $cgstOnInterest + $sgstOnInterest;
            }

            $currentInterestgst = $params->gst_interest ?? '';
            if ($currentInterestgst == 'Y' && !empty($memberBillSummaryData['interest_on_due_amount'])) {
                $cgstOnInterest = ($memberBillSummaryData['interest_on_due_amount'] * $multiplyTariffAmountValue) * ($cgstPer / 100);
                $memberBillSummaryData['cgst_total'] += $cgstOnInterest;
                $sgstOnInterest = ($memberBillSummaryData['interest_on_due_amount'] * $multiplyTariffAmountValue) * ($sgstPer / 100);
                $memberBillSummaryData['sgst_total'] += $sgstOnInterest;
                $memberBillSummaryData['tax_total'] += $cgstOnInterest + $sgstOnInterest;
                $memberBillSummaryData['tax_balance'] += $cgstOnInterest + $sgstOnInterest;
            }

            $memberBillSummaryData['monthly_principal_amount'] = $memberBillSummaryData['monthly_amount'] - $memberBillSummaryData['discount'];
            $memberBillSummaryData['monthly_bill_amount'] = $memberBillSummaryData['monthly_principal_amount'] + $memberBillSummaryData['tax_total'] + $memberBillSummaryData['interest_on_due_amount'];
            $memberBillSummaryData['amount_payable'] = $memberBillSummaryData['op_due_amount'] + $memberBillSummaryData['monthly_bill_amount'];

            if ($memberBillSummaryData['op_due_amount'] < 0) {
                $memberBillSummaryData['principal_balance'] = $memberBillSummaryData['monthly_principal_amount'] - $memberBillSummaryData['principal_paid'];
                $memberBillSummaryData['interest_balance'] = $memberBillSummaryData['interest_on_due_amount'] - $memberBillSummaryData['interest_paid'];
                $memberBillSummaryData['op_principal_arrears'] = $memberBillSummaryData['op_due_amount'];
                $tempDueAmount = abs($memberBillSummaryData['op_due_amount']);
                $this->updateGeneratedBillBalanceAmountInAdvance($memberBillSummaryData, $tempDueAmount);
            } else {
                $memberBillSummaryData['principal_balance'] = ($memberBillSummaryData['op_principal_arrears'] + $memberBillSummaryData['monthly_principal_amount']) - $memberBillSummaryData['principal_paid'];
                $memberBillSummaryData['interest_balance'] = ($memberBillSummaryData['op_interest_arrears'] + $memberBillSummaryData['interest_on_due_amount']) - $memberBillSummaryData['interest_paid'];
            }

            $memberBillSummaryData['balance_amount'] = $memberBillSummaryData['principal_balance'] + $memberBillSummaryData['interest_balance'] + $memberBillSummaryData['tax_balance'];
            $memberBillSummaryData['member_transfer'] = $member_transfer;

            DB::table('member_bill_generates')->insert($billGenerateArr);

            MemberBillSummary::create($memberBillSummaryData);

            $appliedParams = [
                'bill_type' => $billType,
                'member_id' => $memberId,
                'society_id' => $sid,
                'interest_type_id' => $params->interest_type_id,
                'method_id' => $params->method_id,
                'bill_no' => $paymentBillNo,
                'bill_frequency_id' => $params->billing_frequency_id,
            ];
            if (isset($gstLimit) && $gstLimit > 0 && $memberTarrifAmount < $gstLimit) {
                $appliedParams['gst_limit'] = $gstLimit;
            }
            $interestArreasgstVal = $params->gst_interest_arreas ?? '';
            if ($interestArreasgstVal == 'Y' && !empty($memberBillSummaryData['op_interest_arrears'])) {
                $appliedParams['gst_interest_arreas'] = $interestArreasgstVal;
                $appliedParams['op_interest_arrears'] = $memberBillSummaryData['op_interest_arrears'];
            }
            $currentInterestgstVal = $params->gst_interest ?? '';
            if ($currentInterestgstVal == 'Y' && !empty($memberBillSummaryData['interest_on_due_amount'])) {
                $appliedParams['gst_interest'] = $currentInterestgstVal;
                $appliedParams['interest_on_due_amount'] = $memberBillSummaryData['interest_on_due_amount'];
            }
            DB::table('member_bill_applied_parameter')->insert($appliedParams);

            $responseError['error'] = 0;
            $responseError['error_message'] = 'Member bill has been generated successfully.';
        }

        return $responseError;
    }

    private function getTotalMemberTariff($memberId, $billMonthNo, $sid)
    {
        $amount = DB::table('member_bill_generates')
            ->join('society_ledger_heads', 'member_bill_generates.ledger_head_id', '=', 'society_ledger_heads.id')
            ->where('member_bill_generates.member_id', $memberId)
            ->where('member_bill_generates.month', $billMonthNo)
            ->where('society_ledger_heads.status', 1)
            ->where('society_ledger_heads.is_tax_applicable', 1)
            ->sum('member_bill_generates.amount');

        return floatval($amount);
    }

    public function savePaymentEntry(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();

        $paymentDate = $request->input('payment_date');
        $paymentByLedgerId = $request->input('payment_by_ledger_id');
        $paymentType = $request->input('payment_type');
        $singleVoucher = $request->input('single_voucher', 0);
        $entries = $request->input('entries', []);

        if (empty($entries)) {
            return response()->json(['error' => 1, 'message' => 'No entries provided']);
        }

        $voucherNo = SocietyPayment::where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->max('bill_voucher_number');
        $voucherNo = $voucherNo ? $voucherNo + 1 : 1;

        $savedCount = 0;
        foreach ($entries as $entry) {
            $amount = floatval($entry['amount'] ?? 0);
            if ($amount <= 0) continue;

            SocietyPayment::create([
                'society_id' => $sid,
                'ledger_head_id' => $entry['ledger_head_id'],
                'particulars' => $entry['particulars'] ?? '',
                'amount' => $amount,
                'total_amount' => $amount,
                'bill_voucher_number' => $singleVoucher ? $voucherNo : $voucherNo++,
                'payment_by_ledger_id' => $paymentByLedgerId,
                'cheque_reference_number' => $entry['cheque_reference_number'] ?? '',
                'payment_date' => $entry['payment_date'] ?: $paymentDate,
                'payment_type' => $paymentType,
                'cheque_date' => $entry['cheque_date'] ?: null,
                'notes' => $entry['notes'] ?? '',
                'status' => 1,
                'financial_year_id' => $fyId,
            ]);
            $savedCount++;
        }

        return response()->json(['error' => 0, 'message' => "$savedCount payment(s) saved successfully"]);
    }

    public function saveMemberReceiptAjax(Request $request)
    {
        $sid = $this->societyId();
        $fyId = $this->fyId();
        $receipts = $request->input('receipts', []);

        if (empty($receipts)) {
            return response()->json(['error' => 1, 'message' => 'No receipts provided']);
        }

        $receiptNo = MemberPayment::where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->max('receipt_no');
        $receiptNo = $receiptNo ? $receiptNo + 1 : 1;

        $savedCount = 0;
        foreach ($receipts as $r) {
            $amount = floatval($r['amount'] ?? 0);
            if ($amount <= 0) continue;

            MemberPayment::create([
                'member_id' => $r['member_id'],
                'society_id' => $sid,
                'payment_mode_id' => $r['payment_mode'] === 'Cash' ? 2 : 1,
                'total_amount' => $amount,
                'receipt_no' => $receiptNo++,
                'receipt_date' => $r['receipt_date'] ?? date('Y-m-d'),
                'cheque_no' => $r['cheque_no'] ?? '',
                'cheque_date' => $r['cheque_date'] ?: null,
                'bank_name' => $r['society_bank_id'] ?? '',
                'bank_ledger_head_id' => $r['society_bank_id'] ?? null,
                'narration' => $r['remark'] ?? '',
                'financial_year_id' => $fyId,
                'status' => 1,
            ]);
            $savedCount++;
        }

        return response()->json(['error' => 0, 'message' => "$savedCount receipt(s) saved successfully"]);
    }
}
