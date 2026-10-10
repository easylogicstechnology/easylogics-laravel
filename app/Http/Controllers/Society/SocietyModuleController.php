<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\BillingFrequency;
use App\Models\BillSettlementOrder;
use App\Models\Bank;
use App\Models\Building;
use App\Models\CashWithdraw;
use App\Models\ChequeReturnDetail;
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
use App\Models\SocietyOtherIncome;
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
use App\Support\JournalNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    // Ported from Cake SocietysMembersController::_memberPaymentsLocked(): Current Bill
    // Update = Yes means the old, full-chain recalculation (which add/edit/delete payment
    // all trigger via recalculateMemberBills()) must never run again - so payment add, edit
    // and delete are all locked while it's Yes, not just edit/delete of old rows. Cake does
    // NOT lock bulk paste the same way (checked against its own bulk_paste_payment /
    // bulk_paste_last_bill actions - neither has this guard), so this doesn't touch those.
    private function paymentsLocked(): bool
    {
        $param = SocietyParameter::where('society_id', $this->societyId())->first();
        return (int) ($param->current_bill_update_enabled ?? 0) === 1;
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

    // Ported from Cake SocietysController::society_parameter(). Two fields on Cake's
    // form - Penality and Both Interest And Penality - have no matching column in
    // society_parameters (confirmed against the shared DB) and Cake's save() silently
    // drops them; kept as display-only inputs here too, same as Cake. Settlement
    // order (Principal/Interest/Tax) is a SEPARATE table (bill_settlement_order,
    // keyed by society_id + is_active), not a society_parameters column - see
    // Cake's manageSettlementOrder(). Current Bill Update needs a column
    // (current_bill_update_enabled) that does not exist on this database yet either
    // (same as Cake's own hasCurrentBillUpdateColumn() check) - disabled with the
    // same explanatory message until that column is added, never silently dropped.
    public function parameters(Request $request)
    {
        $societyId = $this->societyId();
        $society = $this->getSociety();
        $params = SocietyParameter::where('society_id', $societyId)->first();
        $hasCurrentBillUpdateColumn = Schema::hasColumn('society_parameters', 'current_bill_update_enabled');

        if ($request->isMethod('post')) {
            $data = $request->only([
                'billing_frequency_id', 'interest_type_id', 'interest_rate',
                'method_id', 'tariff_id', 'cgst_tax_per', 'igst_tax_per', 'sgst_tax_per',
                'is_tariff_mothly', 'show_all_tariff_name', 'bill_note', 'special_field',
                'show_bills_in_receipt', 'gst_interest', 'gst_interest_arreas',
                'settlement', 'gst_limit',
            ]);

            if ($hasCurrentBillUpdateColumn) {
                $curBillUpdate = $request->input('current_bill_update_enabled', '');
                $data['current_bill_update_enabled'] = $curBillUpdate === '' ? null : (int) $curBillUpdate;
            }

            foreach (['signature_image', 'scanner_image'] as $field) {
                $file = $request->file($field);
                if ($file) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                        return redirect()->route('society.parameters')->with('error', ucfirst(str_replace('_', ' ', $field)) . ' format type is not valid.');
                    }
                    $folder = $field === 'signature_image' ? 'society_signature' : 'payment_scanner';
                    $filename = $societyId . '_' . uniqid() . '.' . $ext;
                    $file->move(public_path("img/{$folder}/{$societyId}"), $filename);
                    $data[$field . '_path'] = "img/{$folder}/{$societyId}/{$filename}";
                }
            }

            if ($params) {
                $params->update($data);
            } else {
                $data['society_id'] = $societyId;
                $params = SocietyParameter::create($data);
            }

            $orderString = $request->input('sorted_order');
            if ($orderString) {
                BillSettlementOrder::updateOrCreate(
                    ['society_id' => $societyId, 'is_active' => 1],
                    ['settle_order' => $orderString]
                );
            }

            return redirect()->route('society.parameters')->with('success', 'Society parameter has been set successfully.');
        }

        $billingFrequencies = BillingFrequency::all();
        $interestTypes = InterestType::all();
        $interestMethods = InterestMethod::all();
        $tariffTypes = TariffType::all();

        $settlementOrder = BillSettlementOrder::where('society_id', $societyId)->where('is_active', 1)->value('settle_order');
        $settleOrder = $settlementOrder ? explode(',', $settlementOrder) : ['Tax', 'Interest', 'Principle'];
        $orderMap = [];
        foreach ($settleOrder as $idx => $component) {
            $orderMap[$component] = $idx + 1;
        }

        return view('society.modules.parameters', compact(
            'society', 'params', 'billingFrequencies', 'interestTypes', 'interestMethods', 'tariffTypes',
            'hasCurrentBillUpdateColumn', 'orderMap'
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

    /**
     * Port of SocietysAjaxController::getSocietyMembersOpBalance() - the member's latest bill summary of the current
     * year (highest id), whose principal / interest / tax balances fill the Outstanding panel of Make Payment.
     * Same JSON shape as Cake: {"MemberBillSummary": {...}}, or [] when the member has no bill summary.
     */
    public function getMemberOpBalance($memberId)
    {
        // FLOAT(15,2) columns: read them as text so they keep their two decimals
        $row = MemberBillSummary::where('society_id', $this->societyId())
            ->where('financial_year_id', $this->fyId())
            ->where('member_id', $memberId)
            ->orderByDesc('id')
            ->selectRaw('id, CAST(principal_balance AS CHAR) AS principal_balance, CAST(interest_balance AS CHAR) AS interest_balance, CAST(tax_balance AS CHAR) AS tax_balance, CAST(balance_amount AS CHAR) AS balance_amount')
            ->first();

        return response()->json($row ? ['MemberBillSummary' => $row->toArray()] : []);
    }

    public function updatePaymentField(Request $request)
    {
        $societyId = $this->societyId();
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value', '');
        $allowedFields = ['payment_date', 'cheque_date', 'debited_date', 'cheque_reference_number', 'amount', 'tax_amount', 'total_amount'];

        if ($id && $field && in_array($field, $allowedFields, true)) {
            $dateFields = ['payment_date', 'cheque_date', 'debited_date'];
            $numericFields = ['amount', 'tax_amount', 'total_amount'];
            if (in_array($field, $dateFields, true)) {
                $value = ($value !== '') ? $value : null;
            } elseif (in_array($field, $numericFields, true)) {
                $value = ($value !== '' && is_numeric($value)) ? (float) $value : 0;
            }

            $payment = SocietyPayment::where('id', $id)->where('society_id', $societyId)->first();
            if ($payment) {
                $payment->update([$field => $value]);
                return response()->json(['error' => 0, 'error_message' => 'Updated successfully.']);
            }
        }

        return response()->json(['error' => 1, 'error_message' => 'Could not update.']);
    }

    // ─── Bulk Paste Society Payment (Excel copy-paste grid) ──────────

    private function societyExpenseLedgerHeadsList($societyId)
    {
        // "Debit To" list - income heads (account_category_id 3) excluded, same as
        // SocietyBillComponent::societyAllLedgerHeadsLists() in the CakePHP app.
        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('account_category_id', '!=', 3)
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function societyBankBalanceHeadsList($societyId)
    {
        $subCategory = SocietyHeadSubCategory::where('title', 'LIKE', '%Bank Balances%')
            ->where('status', 1)
            ->orderBy('id')
            ->first();
        if (!$subCategory) return collect();

        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('society_head_sub_category_id', $subCategory->id)
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function societyCashBalanceHeadsList($societyId)
    {
        $subCategory = SocietyHeadSubCategory::where('title', 'LIKE', '%Cash Balance%')
            ->where('status', 1)
            ->orderBy('id')
            ->first();
        if (!$subCategory) return collect();

        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('society_head_sub_category_id', $subCategory->id)
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function nextBillVoucherNumber($societyId, $fyId)
    {
        $max = SocietyPayment::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->max(DB::raw('CAST(bill_voucher_number AS UNSIGNED)'));

        return ($max !== null && $max >= 0) ? ((int) $max + 1) : 1;
    }

    private function isDateInCurrentFinancialYear($date)
    {
        $yearStartDate = session('fy.year_start_date');
        $yearEndDate = session('fy.year_end_date');
        if (!$yearStartDate || !$yearEndDate) return true;

        $ts = strtotime($date);
        return $ts !== false && $ts >= strtotime($yearStartDate) && $ts <= strtotime($yearEndDate);
    }

    // Port of SocietysController::_parseExcelDate() from the CakePHP app -
    // must accept the same date shapes (Excel serial numbers, ISO, DD/MM/YYYY,
    // DD-Mon-YYYY) so pasted data behaves identically in both apps.
    private function parseExcelDate($dateValue)
    {
        if (empty($dateValue)) return '';
        $dateValue = trim($dateValue);

        if (is_numeric($dateValue) && $dateValue > 25000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $dateValue);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return '';
            }
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateValue, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $dateValue : '';
        }

        if (preg_match('/^(\d{1,2})[\s\-\/\.]+([A-Za-z]{3,9})[\s\-\/\.]+(\d{2,4})$/', $dateValue, $m)) {
            $dd = (int) $m[1];
            $monthTs = strtotime($m[2] . ' 1 2000');
            $mm = $monthTs !== false ? (int) date('n', $monthTs) : 0;
            $yy = $m[3];
            if (strlen($yy) != 4) {
                $dt = \DateTime::createFromFormat('y', $yy);
                $yy = $dt ? (int) $dt->format('Y') : (int) ('20' . $yy);
            } else {
                $yy = (int) $yy;
            }
            return ($mm > 0 && checkdate($mm, $dd, $yy))
                ? $yy . '-' . str_pad($mm, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dd, 2, '0', STR_PAD_LEFT)
                : '';
        }

        if (strstr($dateValue, '/') || strstr($dateValue, '-')) {
            $sep = strstr($dateValue, '/') ? '/' : '-';
            $parts = explode($sep, $dateValue);
            if (count($parts) == 3 && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                [$dd, $mm, $yy] = $parts;
                $dd = (int) $dd;
                $mm = (int) $mm;
                if (strlen($yy) != 4) {
                    $dt = \DateTime::createFromFormat('y', $yy);
                    $yy = $dt ? (int) $dt->format('Y') : (int) ('20' . $yy);
                } else {
                    $yy = (int) $yy;
                }
                if ($mm > 12 && $dd <= 12) { $tmp = $dd; $dd = $mm; $mm = $tmp; }
                if (checkdate($mm, $dd, $yy)) {
                    return $yy . '-' . str_pad($mm, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dd, 2, '0', STR_PAD_LEFT);
                }
            }
            return '';
        }

        $ts = strtotime($dateValue);
        if ($ts !== false) {
            $result = date('Y-m-d', $ts);
            [$ry, $rm, $rd] = explode('-', $result);
            return checkdate((int) $rm, (int) $rd, (int) $ry) ? $result : '';
        }

        return '';
    }

    public function bulkPastePayments()
    {
        $societyId = $this->societyId();

        $societyExpenseLedgerHeadsLists = $this->societyExpenseLedgerHeadsList($societyId);
        $societyBankBalanceHeadsLists = $this->societyBankBalanceHeadsList($societyId);
        $societyCashBalanceHeadsLists = $this->societyCashBalanceHeadsList($societyId);

        return view('society.modules.bulk-paste-payments', compact(
            'societyExpenseLedgerHeadsLists', 'societyBankBalanceHeadsLists', 'societyCashBalanceHeadsLists'
        ));
    }

    public function saveBulkPastePayments(Request $request)
    {
        set_time_limit(0);
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $postData = json_decode($request->getContent(), true);
        if (empty($postData)) $postData = $request->all();

        $rows = $postData['rows'] ?? [];
        $defaultBankAccountId = $postData['default_bank_account_id'] ?? '';
        $defaultCashAccountId = $postData['default_cash_account_id'] ?? '';

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No rows to save.']);
        }

        $societyParameter = SocietyParameter::where('society_id', $societyId)->first();
        if (!$societyParameter) {
            return response()->json(['success' => false, 'message' => 'Society parameters not configured. Cannot save payments.']);
        }

        $ledgerHeadsByTitle = [];
        foreach ($this->societyExpenseLedgerHeadsList($societyId) as $lid => $ltitle) {
            $ledgerHeadsByTitle[strtoupper(trim($ltitle))] = $lid;
        }

        $bankByTitle = [];
        foreach ($this->societyBankBalanceHeadsList($societyId) as $bid => $btitle) {
            $bankByTitle[strtoupper(trim($btitle))] = $bid;
        }
        $cashByTitle = [];
        foreach ($this->societyCashBalanceHeadsList($societyId) as $cid => $ctitle) {
            $cashByTitle[strtoupper(trim($ctitle))] = $cid;
        }
        $allByTitle = $bankByTitle + $cashByTitle;

        // "Direct Expense" (society_id 0, shared) is the default sub-category for
        // an auto-created expense head from a paste - mirrors the CakePHP logic.
        $defaultExpenseSubCategory = SocietyHeadSubCategory::where('title', 'Direct Expense')
            ->where('account_category_id', 4)
            ->where('account_head_id', 16)
            ->where('society_id', 0)
            ->where('status', 1)
            ->first();
        $defaultExpenseSubCategoryId = $defaultExpenseSubCategory->id ?? 0;

        $billVoucherNumber = $this->nextBillVoucherNumber($societyId, $fyId);
        $seenVouchers = [];

        $results = [];
        $added = 0;
        $failed = 0;

        foreach ($rows as $idx => $row) {
            $errors = [];

            $ledgerTitle = trim($row['paid_to'] ?? '');
            $particulars = trim($row['particulars'] ?? '');
            $voucherNo = trim($row['bill_voucher_number'] ?? '');
            $paymentDateRaw = trim($row['payment_date'] ?? '');
            $chequeDateRaw = trim($row['cheque_date'] ?? '');
            $chequeNo = trim($row['cheque_number'] ?? '');
            $amountRaw = trim(str_replace(',', '', $row['amount'] ?? ''));
            $taxAmountRaw = trim(str_replace(',', '', $row['tax_amount'] ?? ''));
            $paymentTypeRaw = trim($row['payment_type'] ?? '');
            $paidFromName = trim($row['paid_from'] ?? '');
            $notes = trim($row['notes'] ?? '');

            if (empty($ledgerTitle)) $errors[] = 'Paid To is required';
            if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw <= 0) $errors[] = 'Valid amount required';

            $paymentDate = $this->parseExcelDate($paymentDateRaw);
            if (empty($paymentDate)) {
                $errors[] = 'Invalid/missing Payment Date';
            } elseif (!$this->isDateInCurrentFinancialYear($paymentDate)) {
                $errors[] = 'Payment Date not in current financial year';
            }

            if (!empty($voucherNo) && isset($seenVouchers[$voucherNo])) {
                $errors[] = 'Duplicate Bill Voucher No in this batch';
            }

            if (!empty($errors)) {
                $results[] = ['row' => $idx, 'status' => 'error', 'errors' => $errors];
                $failed++;
                continue;
            }

            $ledgerKey = strtoupper($ledgerTitle);
            if (isset($ledgerHeadsByTitle[$ledgerKey])) {
                $ledgerHeadId = $ledgerHeadsByTitle[$ledgerKey];
            } else {
                $newLedger = SocietyLedgerHead::create([
                    'title' => $ledgerTitle,
                    'short_code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $ledgerTitle), 0, 10)),
                    'society_head_sub_category_id' => $defaultExpenseSubCategoryId,
                    'account_category_id' => 4,
                    'account_head_id' => 16,
                    'society_id' => $societyId,
                    'financial_year_id' => $fyId,
                    'opening_amount' => 0,
                    'status' => 1,
                ]);
                if ($newLedger) {
                    $ledgerHeadId = $newLedger->id;
                    $ledgerHeadsByTitle[$ledgerKey] = $ledgerHeadId;
                } else {
                    $results[] = ['row' => $idx, 'status' => 'error', 'errors' => ['Could not create expense head "' . $ledgerTitle . '"']];
                    $failed++;
                    continue;
                }
            }

            $paymentTypeUpper = strtoupper($paymentTypeRaw);
            if ($paymentTypeRaw === 'Bank' || $paymentTypeRaw === 'Cash') {
                $paymentType = $paymentTypeRaw;
            } elseif ($paymentTypeUpper === 'CASH') {
                $paymentType = 'Cash';
            } else {
                $paymentType = 'Bank';
            }

            $paymentByLedgerId = 0;
            if (!empty($paidFromName)) {
                $byKey = strtoupper($paidFromName);
                if ($paymentType == 'Bank' && isset($bankByTitle[$byKey])) {
                    $paymentByLedgerId = $bankByTitle[$byKey];
                } elseif ($paymentType == 'Cash' && isset($cashByTitle[$byKey])) {
                    $paymentByLedgerId = $cashByTitle[$byKey];
                } elseif (isset($allByTitle[$byKey])) {
                    $paymentByLedgerId = $allByTitle[$byKey];
                }
            }
            if (empty($paymentByLedgerId)) {
                $paymentByLedgerId = ($paymentType == 'Bank') ? $defaultBankAccountId : $defaultCashAccountId;
            }
            if (empty($paymentByLedgerId)) {
                $results[] = ['row' => $idx, 'status' => 'error', 'errors' => [
                    ($paymentType == 'Bank' ? 'Bank' : 'Cash') . ' account not selected/matched for "Paid From"'
                ]];
                $failed++;
                continue;
            }

            $chequeDate = $this->parseExcelDate($chequeDateRaw);

            $amount = (float) $amountRaw;
            $taxAmount = ($taxAmountRaw !== '' && is_numeric($taxAmountRaw)) ? (float) $taxAmountRaw : 0.00;
            $totalAmount = $amount - $taxAmount;

            $finalVoucherNo = $voucherNo;
            if (!empty($voucherNo)) {
                $seenVouchers[$voucherNo] = true;
            } else {
                $finalVoucherNo = (string) $billVoucherNumber;
            }

            $payment = SocietyPayment::create([
                'ledger_head_id' => $ledgerHeadId,
                'payment_date' => $paymentDate,
                'payment_type' => $paymentType,
                'payment_by_ledger_id' => $paymentByLedgerId,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'cheque_reference_number' => $chequeNo,
                'cheque_date' => $chequeDate ?: null,
                'bill_voucher_number' => $finalVoucherNo,
                'particulars' => $particulars,
                'notes' => $notes,
                'society_id' => $societyId,
                'financial_year_id' => $fyId,
                'tds_account_id' => 0,
                'status' => 1,
            ]);

            if ($payment) {
                $added++;
                if (empty($voucherNo)) $billVoucherNumber++;
                $results[] = ['row' => $idx, 'status' => 'success', 'voucher_no' => $finalVoucherNo];
            } else {
                $failed++;
                $results[] = ['row' => $idx, 'status' => 'error', 'errors' => ['Database save failed']];
            }
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'failed' => $failed,
            'total' => count($rows),
            'results' => $results,
        ]);
    }

    public function downloadSampleSocietyPaymentTemplate()
    {
        $societyId = $this->societyId();

        $societyExpenseLedgerHeadsLists = $this->societyExpenseLedgerHeadsList($societyId);
        $societyBankLists = $this->societyBankBalanceHeadsList($societyId);
        $societyCashLists = $this->societyCashBalanceHeadsList($societyId);

        $allByNames = [];
        foreach ($societyBankLists as $title) {
            $allByNames[] = $title;
        }
        foreach ($societyCashLists as $title) {
            if (!in_array($title, $allByNames)) $allByNames[] = $title;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $spreadsheet->getDefaultStyle()->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Society Payment');
        $sheet->freezePane('A2');
        $sheet->getSheetView()->setZoomScale(100);

        $headerRow = ['DebitTo', 'PaymentDate', 'PaymentType', 'PaymentBy', 'Amount', 'TaxAmount', 'Particulars', 'ChequeNo', 'ChequeDate', 'VoucherNo', 'Notes'];
        foreach ($headerRow as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->setSize(12);
        }

        $lastRow = count($societyExpenseLedgerHeadsLists) + 1;
        $rowNum = 2;
        foreach ($societyExpenseLedgerHeadsLists as $id => $title) {
            $sheet->setCellValue('A' . $rowNum, $title);
            $rowNum++;
        }
        if ($lastRow < 2) $lastRow = 2;

        // PaymentDate - date format + validation (Column B)
        $sheet->getStyle('B2:B' . $lastRow)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        for ($r = 2; $r <= $lastRow; $r++) {
            $dv = $sheet->getCell('B' . $r)->getDataValidation();
            $dv->setType(DataValidation::TYPE_DATE);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid Date');
            $dv->setError('Please enter a valid date (DD/MM/YYYY).');
            $dv->setShowInputMessage(true);
            $dv->setPromptTitle('Payment Date');
            $dv->setPrompt('Enter payment date in DD/MM/YYYY format.');
        }

        // PaymentType - dropdown Bank/Cash (Column C)
        for ($r = 2; $r <= $lastRow; $r++) {
            $dv = $sheet->getCell('C' . $r)->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowDropDown(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid Payment Type');
            $dv->setError('Please select Bank or Cash.');
            $dv->setFormula1('"Bank,Cash"');
        }

        // PaymentBy - dropdown of bank + cash names (Column D)
        $byNamesStr = implode(',', $allByNames);
        for ($r = 2; $r <= $lastRow; $r++) {
            $dv = $sheet->getCell('D' . $r)->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowDropDown(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid Bank/Cash Name');
            $dv->setError('Please select a valid Bank or Cash account name.');
            $dv->setShowInputMessage(true);
            $dv->setPromptTitle('Payment By');
            $dv->setPrompt('Select the Bank or Cash account.');
            $dv->setFormula1('"' . $byNamesStr . '"');
        }

        // ChequeDate - date format + validation (Column I)
        $sheet->getStyle('I2:I' . $lastRow)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        for ($r = 2; $r <= $lastRow; $r++) {
            $dv = $sheet->getCell('I' . $r)->getDataValidation();
            $dv->setType(DataValidation::TYPE_DATE);
            $dv->setErrorStyle(DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid Date');
            $dv->setError('Please enter a valid date (DD/MM/YYYY).');
            $dv->setShowInputMessage(true);
            $dv->setPromptTitle('Cheque Date');
            $dv->setPrompt('Enter cheque date in DD/MM/YYYY format.');
        }

        $widths = ['A' => 30, 'B' => 15, 'C' => 15, 'D' => 20, 'E' => 12, 'F' => 12, 'G' => 20, 'H' => 15, 'I' => 15, 'J' => 12, 'K' => 20];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Reference sheet with ledger heads and bank/cash names
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Reference');
        $refSheet->setCellValue('A1', 'Ledger Head Name');
        $refSheet->setCellValue('B1', 'Bank Accounts');
        $refSheet->setCellValue('C1', 'Cash Accounts');
        $refSheet->getStyle('A1:C1')->getFont()->setBold(true)->setSize(12);
        $refSheet->getColumnDimension('A')->setWidth(30);
        $refSheet->getColumnDimension('B')->setWidth(25);
        $refSheet->getColumnDimension('C')->setWidth(25);

        $refRow = 2;
        foreach ($societyExpenseLedgerHeadsLists as $id => $title) {
            $refSheet->setCellValue('A' . $refRow, $title);
            $refRow++;
        }
        $refRow = 2;
        foreach ($societyBankLists as $id => $title) {
            $refSheet->setCellValue('B' . $refRow, $title);
            $refRow++;
        }
        $refRow = 2;
        foreach ($societyCashLists as $id => $title) {
            $refSheet->setCellValue('C' . $refRow, $title);
            $refRow++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'SampleImportSocietyPayment.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function bankReconciliation()
    {
        return view('society.modules.placeholder', [
            'title' => 'Bank Reconciliation',
        ]);
    }

    public function memberReceiptBulkPaste()
    {
        $societyId = $this->societyId();

        $societyBankLists = $this->societyBankBalanceHeadsList($societyId);
        $societyCashLists = $this->societyCashBalanceHeadsList($societyId);
        $membersList = Member::where('society_id', $societyId)->where('status', 1)->pluck('id', 'flat_no');

        return view('society.modules.member-receipt-bulk-paste', compact(
            'societyBankLists', 'societyCashLists', 'membersList'
        ));
    }

    // Port of SocietysMembersController::_extractReceiptNumber() - pasted
    // receipt numbers often carry a prefix like "BR/04/000145"; pull the
    // trailing digit run out since receipt_id is a strict int column.
    private function extractReceiptNumber($raw)
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        if (is_numeric($raw)) return (string) intval($raw);
        if (preg_match_all('/\d+/', $raw, $matches) && !empty($matches[0])) {
            return (string) intval(end($matches[0]));
        }
        return '';
    }

    public function saveMemberReceiptBulkPaste(Request $request)
    {
        set_time_limit(0);
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $postData = json_decode($request->getContent(), true);
        if (empty($postData)) $postData = $request->all();

        $rows = $postData['rows'] ?? [];
        $cashAccountId = $postData['cash_account_id'] ?? 0;
        $bankAccountId = $postData['bank_account_id'] ?? 0;

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No rows to save.']);
        }

        $existingReceiptsArr = array_flip(
            MemberPayment::where('society_id', $societyId)
                ->where('financial_year_id', $fyId)
                ->pluck('receipt_id')
                ->map(fn ($v) => (string) $v)
                ->all()
        );

        $membersList = [];
        foreach (Member::where('society_id', $societyId)->where('status', 1)->get(['id', 'flat_no']) as $m) {
            $membersList[trim($m->flat_no)] = $m->id;
        }

        $settlementService = app(\App\Services\BillSettlementService::class);

        $results = [];
        $addedCount = 0;
        $failedCount = 0;
        $usedReceiptIds = [];

        foreach ($rows as $index => $row) {
            $errors = [];

            $flatNo = trim($row['flat_number'] ?? '');
            $receiptNoRaw = trim($row['receipt_number'] ?? '');
            $receiptNo = $this->extractReceiptNumber($receiptNoRaw);
            if (!empty($receiptNoRaw) && $receiptNo === '') {
                $errors[] = 'Could not read a number from Receipt Number: ' . $receiptNoRaw;
            }
            $receiptDate = trim($row['receipt_date'] ?? '');
            $chequeNo = trim($row['cheque_number'] ?? '');
            $chequeDate = trim($row['cheque_date'] ?? '');
            $upi = trim($row['upi'] ?? '');
            $amount = trim(str_replace(',', '', $row['amount'] ?? ''));
            $remarks = trim($row['remarks'] ?? '');

            if (empty($flatNo)) $errors[] = 'Flat Number required';
            if (empty($receiptDate)) $errors[] = 'Receipt Date required';
            if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0) $errors[] = 'Valid Amount required';

            $memberId = 0;
            if (!empty($flatNo)) {
                if (isset($membersList[$flatNo])) {
                    $memberId = $membersList[$flatNo];
                } else {
                    $errors[] = 'Flat Number not found: ' . $flatNo;
                }
            }

            $parsedReceiptDate = $this->parseExcelDate($receiptDate);
            if (!empty($receiptDate) && empty($parsedReceiptDate)) {
                $errors[] = 'Invalid Receipt Date format';
            } elseif (!empty($parsedReceiptDate) && !$this->isDateInCurrentFinancialYear($parsedReceiptDate)) {
                $errors[] = 'Receipt Date not in current financial year';
            }

            $parsedChequeDate = '';
            if (!empty($chequeDate)) {
                $parsedChequeDate = $this->parseExcelDate($chequeDate);
                if (empty($parsedChequeDate)) {
                    $errors[] = 'Invalid Cheque Date format';
                }
            }

            if (!empty($receiptNo) && (isset($existingReceiptsArr[$receiptNo]) || isset($usedReceiptIds[$receiptNo]))) {
                $errors[] = 'Duplicate Receipt Number: ' . $receiptNo;
            }

            if (!empty($errors)) {
                $failedCount++;
                $results[] = ['row' => $index, 'status' => 'error', 'errors' => $errors];
                continue;
            }

            $paymentMode = 1;
            $chequeRef = '';
            if (!empty($chequeNo)) {
                $paymentMode = is_numeric($chequeNo) ? 3 : 4;
                $chequeRef = $chequeNo;
            } elseif (!empty($upi)) {
                $paymentMode = 2;
                $chequeRef = $upi;
            }

            $societyBankId = ($paymentMode == 1) ? $cashAccountId : $bankAccountId;
            if (empty($societyBankId)) {
                $failedCount++;
                $results[] = ['row' => $index, 'status' => 'error', 'errors' => [
                    $paymentMode == 1 ? 'Cash Account not selected' : 'Bank Account not selected'
                ]];
                continue;
            }

            if (empty($receiptNo)) {
                $receiptNo = (string) ((MemberPayment::where('society_id', $societyId)->max('receipt_id') ?? 0) + 1);
                while (isset($usedReceiptIds[$receiptNo]) || isset($existingReceiptsArr[$receiptNo])) {
                    $receiptNo = (string) ((int) $receiptNo + 1);
                }
            }
            $usedReceiptIds[$receiptNo] = true;

            $memberTransfer = $settlementService->getLatestTransferNo($memberId, $societyId);

            $payment = MemberPayment::create([
                'society_id' => $societyId,
                'member_id' => $memberId,
                'receipt_id' => $receiptNo,
                'amount_paid' => (float) $amount,
                'bill_month' => date('m', strtotime($parsedReceiptDate)),
                'payment_mode' => $paymentMode,
                'cheque_reference_number' => $chequeRef,
                'payment_date' => $parsedReceiptDate,
                'entry_date' => !empty($parsedChequeDate) ? $parsedChequeDate : now(),
                'society_bank_id' => $societyBankId,
                'member_bank_id' => 0,
                'bill_type' => 'reg',
                'narration' => $remarks,
                'financial_year_id' => $fyId,
                'member_transfer' => $memberTransfer,
            ]);

            if ($payment) {
                $addedCount++;
                $settlementService->recalculateMemberBills($memberId, $societyId, 'reg', $fyId, $memberTransfer);
                $results[] = ['row' => $index, 'status' => 'success', 'receipt_id' => $receiptNo, 'payment_id' => $payment->id];
            } else {
                $failedCount++;
                $results[] = ['row' => $index, 'status' => 'error', 'errors' => ['Database save failed']];
            }
        }

        return response()->json([
            'success' => true,
            'total' => count($rows),
            'added' => $addedCount,
            'failed' => $failedCount,
            'results' => $results,
        ]);
    }

    public function recalculateBills()
    {
        return view('society.modules.placeholder', [
            'title' => 'Recalculate Bills',
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

    // Port of SocietysController::add_cash_withdraws() / delete_cash_withdraws()
    // (CakePHP). One "Type" field (Contra/Deposit/Withdraw) drives the whole
    // form - only Contra needs the second "Transfer to Bank" ledger.
    public function addCashContra(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $societyBankBalanceHeadsLists = $this->societyBankBalanceHeadsList($societyId);
        $editItem = $id ? CashWithdraw::where('id', $id)->where('society_id', $societyId)->first() : null;

        if ($request->isMethod('post')) {
            $data = $request->only([
                'txn_type', 'payment_date', 'bank_ledger_head_id', 'bank_to_ledger_head_id',
                'amount', 'cheque_no', 'particulars', 'narration',
            ]);
            $data['society_id'] = $societyId;
            $data['financial_year_id'] = $fyId;
            $txnTypeLabel = ucfirst($data['txn_type'] ?? '');

            if (!$this->isDateInCurrentFinancialYear($data['payment_date'] ?? '')) {
                return redirect()->route('society.cashContra')->with('error', $txnTypeLabel . ' could not be saved! Check payment date.');
            }

            if ($editItem) {
                $editItem->update($data);
            } else {
                CashWithdraw::create($data);
            }

            return redirect()->route('society.cashContra')->with('success', $txnTypeLabel . ' added successfully.');
        }

        return view('society.modules.add-cash-contra', compact('societyBankBalanceHeadsLists', 'editItem'));
    }

    public function deleteCashContra($id)
    {
        $societyId = $this->societyId();
        $deleted = CashWithdraw::where('id', $id)->where('society_id', $societyId)->delete();

        if ($deleted) {
            return redirect()->route('society.cashContra')->with('success', 'Record has been deleted.');
        }
        return redirect()->route('society.cashContra')->with('error', 'Record could not be deleted.');
    }

    // ─── Registers / TDS / GST / Help (sidebar groups present in the CakePHP
    // menu but not yet built out here) - placeholder pages per slug so the
    // menu is complete and navigable; each mirrors one submenu entry from
    // MenuComponent::navigation() in the CakePHP app. ──────────────────────

    private static $registersPages = [
        'index' => 'Registers',
        'fd-register' => 'FD Register',
        'shares-register' => 'Shares Register',
        'lien-register' => 'Lien Register',
        'nominee-register' => 'Nominee Register',
        'form-i' => 'Form I',
        'form-j' => 'Form J',
    ];

    private static $tdsPages = [
        'dashboard' => 'TDS Dashboard',
        'sections' => 'TDS Sections',
        'deductees' => 'TDS Deductees',
        'transactions' => 'TDS Transactions',
        'report' => 'TDS Report',
        'ledger' => 'TDS Ledger',
        'challans' => 'Challan Management',
        'certificates' => 'TDS Certificates',
    ];

    private static $gstPages = [
        'dashboard' => 'GST Dashboard',
        'master-setup' => 'GST Master',
        'hsn-master' => 'HSN/SAC Master',
        'outward-register' => 'Outward Register',
        'input-register' => 'Input / Purchase Register',
        'itc-summary' => 'ITC Summary',
        'liability' => 'GST Liability',
        'ledger' => 'GST Ledger',
        'advance-receipts' => 'Advance Receipts',
        'credit-debit-notes' => 'Credit/Debit Notes',
        'payments' => 'GST Payment/Challan',
        'reconciliation' => 'Reconciliation',
        'return-reports' => 'Return Reports',
        'year-end-report' => 'Year-End Report',
    ];

    public function registersPage($page = 'index')
    {
        return view('society.modules.placeholder', ['title' => self::$registersPages[$page] ?? 'Registers']);
    }

    public function tdsPage($page = 'dashboard')
    {
        return view('society.modules.placeholder', ['title' => self::$tdsPages[$page] ?? 'TDS']);
    }

    public function gstPage($page = 'dashboard')
    {
        return view('society.modules.placeholder', ['title' => self::$gstPages[$page] ?? 'GST']);
    }

    public function helpGuide()
    {
        return view('society.modules.placeholder', ['title' => 'Help & Guide']);
    }

    // ─── General Receipt (Non-Member Bank/Cash Receipt = SocietyOtherIncome) ──
    // Port of SocietysController::general_receipt() / add_general_receipt() /
    // delete_general_receipts() / bulk_paste_general_receipt() /
    // save_bulk_paste_general_receipt() (CakePHP) plus the AJAX edit modal
    // endpoints from SocietysAjaxController (get_general_receipt_details() /
    // update_general_receipt()).

    private function societyOtherIncomeHeadsList($societyId)
    {
        // "Received By" / "TDS Bank" dropdown - expense heads (account_category_id 4)
        // excluded, same as SocietyBillComponent::societyOtherIncomeHeadsLists().
        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->where('account_category_id', '!=', 4)
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function nextGeneralReceiptNumber($societyId, $fyId)
    {
        $max = SocietyOtherIncome::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->max('general_receipt_number');

        return ($max !== null && $max >= 0) ? ((int) $max + 1) : 1;
    }

    public function generalReceipt()
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $items = SocietyOtherIncome::where('society_id', $societyId)
            ->where('status', 1)
            ->where('financial_year_id', $fyId)
            ->with('ledgerHead')
            ->orderByDesc('payment_date')
            ->get();

        return view('society.modules.general-receipt', compact('items'));
    }

    public function addGeneralReceipt(Request $request, $id = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        if ($request->isMethod('post')) {
            $rows = $request->input('SocietyOtherIncome', []);
            $header = $request->input('SocietyOtherIncomeHeader', []);
            $generateOneVoucher = $request->input('generate_one_voucher');
            $paymentMode = isset($header['payment_mode']) ? trim($header['payment_mode']) : '';
            $societyBankId = $header['society_bank_id'] ?? '';

            $toInsert = [];
            $wrongDataEntryCount = 0;
            $sharedVoucherNumber = null;

            foreach ($rows as $row) {
                if (empty($row['ledger_head_id'])) continue;

                $paymentDate = $row['payment_date'] ?? '';
                if (empty($paymentDate) || !$this->isDateInCurrentFinancialYear($paymentDate)) {
                    $wrongDataEntryCount++;
                    continue;
                }

                if (!empty($generateOneVoucher)) {
                    if ($sharedVoucherNumber === null) {
                        $sharedVoucherNumber = $this->nextGeneralReceiptNumber($societyId, $fyId);
                    }
                    $voucherNumber = $sharedVoucherNumber;
                } else {
                    $voucherNumber = $this->nextGeneralReceiptNumber($societyId, $fyId);
                }

                $data = [
                    'general_receipt_number' => $voucherNumber,
                    'society_id'             => $societyId,
                    'amount_paid'            => !empty($row['amount_paid']) ? $row['amount_paid'] : 0,
                    'tds_amount'             => !empty($row['tds_amount']) ? $row['tds_amount'] : 0,
                    'net_amount'             => !empty($row['net_amount']) ? $row['net_amount'] : 0,
                    'tds_bank_id'            => $row['tds_bank_id'] ?? '',
                    'payment_mode'           => $paymentMode,
                    'description'            => $row['description'] ?? '',
                    'general_bank_name'      => $row['general_bank_name'] ?? '',
                    'title'                  => $row['title'] ?? '',
                    'ledger_head_id'         => $row['ledger_head_id'],
                    'payment_date'           => $paymentDate,
                    'entry_date'             => now(),
                    'financial_year_id'      => $fyId,
                    'status'                 => 1,
                ];

                if ($paymentMode == 'Bank') {
                    $data['cheque_no'] = !empty($row['cheque_no']) ? $row['cheque_no'] : 0;
                    $data['cheque_date'] = !empty($row['cheque_date']) ? $row['cheque_date'] : null;
                    $data['society_bank_id'] = $societyBankId !== '' ? $societyBankId : 0;
                } else {
                    $data['cheque_no'] = 0;
                    $data['cheque_date'] = null;
                    // A cash receipt is still tied to a ledger - the "By" (Cash in Hand)
                    // dropdown. Cash book / trial balance / balance sheet all match cash
                    // receipts by this id, so keep the selected ledger (mirrors the CakePHP
                    // fix that stopped forcing this to null for the Cash branch).
                    $data['society_bank_id'] = $societyBankId !== '' ? $societyBankId : 0;
                }

                $toInsert[] = $data;
            }

            $message = $wrongDataEntryCount > 0 ? ' and Some Entries Not Saved Due to year Issue' : '';

            if (!empty($toInsert)) {
                DB::transaction(function () use ($toInsert) {
                    foreach ($toInsert as $data) {
                        SocietyOtherIncome::create($data);
                    }
                });
                return redirect()->route('society.generalReceipt')->with('success', 'The general receipts has been added' . $message);
            }

            return redirect()->route('society.generalReceipt')->with('error', 'The general receipts could not be Saved.' . $message . ' Please, try again.');
        }

        $societyOtherIncomeHeadsLists = $this->societyOtherIncomeHeadsList($societyId);
        $societyBankBalanceHeadsLists = $this->societyBankBalanceHeadsList($societyId);
        $societyCashBalanceHeadsLists = $this->societyCashBalanceHeadsList($societyId);

        $editItems = $id ? SocietyOtherIncome::where('id', $id)->where('society_id', $societyId)->get() : collect();

        return view('society.modules.add-general-receipt', compact(
            'societyOtherIncomeHeadsLists', 'societyBankBalanceHeadsLists', 'societyCashBalanceHeadsLists', 'editItems'
        ));
    }

    public function deleteGeneralReceipt($id)
    {
        $societyId = $this->societyId();
        $item = SocietyOtherIncome::where('id', $id)->where('society_id', $societyId)->first();

        if ($item) {
            $item->delete();
            return redirect()->route('society.generalReceipt')->with('error', 'The payment has been deleted.');
        }

        return redirect()->route('society.generalReceipt')->with('error', 'The payment could not be deleted. Please, try again.');
    }

    public function getGeneralReceiptDetails(Request $request)
    {
        $societyId = $this->societyId();
        $id = $request->input('general_receipt_id', 0);

        $item = SocietyOtherIncome::where('id', $id)->where('society_id', $societyId)->first();

        if (!$item) {
            return response()->json(['error' => 1, 'error_message' => 'Requested general receipt is not found.', 'data' => []]);
        }

        $data = $item->toArray();
        foreach (['payment_date', 'cheque_date'] as $field) {
            if (empty($data[$field]) || $data[$field] == '0000-00-00') {
                $data[$field] = '';
            }
        }

        return response()->json(['error' => 0, 'error_message' => '', 'data' => $data]);
    }

    public function updateGeneralReceipt(Request $request)
    {
        $societyId = $this->societyId();
        $requestData = $request->input('SocietyOtherIncome', []);
        $id = $requestData['id'] ?? 0;

        $item = SocietyOtherIncome::where('id', $id)->where('society_id', $societyId)->first();
        if (!$item) {
            return response()->json(['error' => 1, 'error_message' => 'Requested general receipt is not found.']);
        }

        if (empty($requestData['ledger_head_id'])) {
            return response()->json(['error' => 1, 'error_message' => 'Please select the head in Received By.']);
        }

        $paymentDate = $requestData['payment_date'] ?? '';
        if (empty($paymentDate) || !$this->isDateInCurrentFinancialYear($paymentDate)) {
            return response()->json(['error' => 1, 'error_message' => 'Payment date should be within the current financial year.']);
        }

        $data = [
            'ledger_head_id' => $requestData['ledger_head_id'],
            'payment_mode'   => isset($requestData['payment_mode']) ? trim($requestData['payment_mode']) : '',
            'amount_paid'    => ($requestData['amount_paid'] ?? '') !== '' ? $requestData['amount_paid'] : 0,
            'tds_amount'     => ($requestData['tds_amount'] ?? '') !== '' ? $requestData['tds_amount'] : 0,
            'net_amount'     => ($requestData['net_amount'] ?? '') !== '' ? $requestData['net_amount'] : 0,
            'tds_bank_id'    => $requestData['tds_bank_id'] ?? '',
            'title'          => $requestData['title'] ?? '',
            'description'    => $requestData['description'] ?? '',
            'payment_date'   => $paymentDate,
        ];

        if ($data['payment_mode'] == 'Bank') {
            $data['society_bank_id']   = ($requestData['society_bank_id'] ?? '') !== '' ? $requestData['society_bank_id'] : 0;
            $data['cheque_no']         = ($requestData['cheque_no'] ?? '') !== '' ? $requestData['cheque_no'] : 0;
            $data['cheque_date']       = ($requestData['cheque_date'] ?? '') !== '' ? $requestData['cheque_date'] : null;
            $data['general_bank_name'] = $requestData['general_bank_name'] ?? '';
        } else {
            $data['society_bank_id']   = 0;
            $data['cheque_no']         = 0;
            $data['cheque_date']       = null;
            $data['general_bank_name'] = '';
        }

        $item->update($data);

        return response()->json(['error' => 0, 'error_message' => 'The general receipt has been updated.']);
    }

    public function bulkPasteGeneralReceipt()
    {
        $societyId = $this->societyId();

        $societyOtherIncomeHeadsLists = $this->societyOtherIncomeHeadsList($societyId);
        $societyBankLists = $this->societyBankBalanceHeadsList($societyId);
        $societyCashLists = $this->societyCashBalanceHeadsList($societyId);

        return view('society.modules.bulk-paste-general-receipt', compact(
            'societyOtherIncomeHeadsLists', 'societyBankLists', 'societyCashLists'
        ));
    }

    public function saveBulkPasteGeneralReceipt(Request $request)
    {
        set_time_limit(0);
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $postData = json_decode($request->getContent(), true);
        if (empty($postData)) $postData = $request->all();

        $rows = $postData['rows'] ?? [];
        // Each row picks Bank or Cash independently (row['payment_type']) so one
        // batch can mix both - unlike addGeneralReceipt()'s single-entry form,
        // which only has one header payment_mode for the whole voucher.
        $cashAccountId = $postData['cash_account_id'] ?? '';
        $bankAccountId = $postData['bank_account_id'] ?? '';

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No rows to save.']);
        }

        $societyParameter = SocietyParameter::where('society_id', $societyId)->first();
        if (!$societyParameter) {
            return response()->json(['success' => false, 'message' => 'Society parameters not configured. Cannot save receipts.']);
        }

        $ledgerHeadsByTitle = [];
        foreach ($this->societyOtherIncomeHeadsList($societyId) as $lid => $ltitle) {
            $ledgerHeadsByTitle[strtoupper(trim($ltitle))] = $lid;
        }

        // The master "Other Income" sub-group (society_id 0, shared across all
        // societies) - matches account_category_id/account_head_id (3/9) hardcoded
        // below, so an auto-created head maps under it exactly like a manually
        // added one (see SocietyBillComponent-equivalent auto-create logic).
        $otherIncomeSubCategory = SocietyHeadSubCategory::where('title', 'Other Income')
            ->where('account_category_id', 3)
            ->where('account_head_id', 9)
            ->where('society_id', 0)
            ->where('status', 1)
            ->first();
        $otherIncomeSubCategoryId = $otherIncomeSubCategory->id ?? 0;

        $results = [];
        $added = 0;
        $failed = 0;

        foreach ($rows as $idx => $row) {
            $errors = [];

            $ledgerTitle = trim($row['paid_to'] ?? '');
            $particulars = trim($row['particulars'] ?? '');
            $remark = trim($row['remark'] ?? '');
            $paymentDateRaw = trim($row['payment_date'] ?? '');
            $chequeDateRaw = trim($row['cheque_date'] ?? '');
            $chequeNo = trim($row['cheque_number'] ?? '');
            $bankNameText = trim($row['bank_name'] ?? '');
            $tdsBankText = trim($row['tds_bank'] ?? '');
            $amountRaw = trim(str_replace(',', '', $row['amount'] ?? ''));
            $tdsAmountRaw = trim(str_replace(',', '', $row['tds_amount'] ?? ''));
            $paymentTypeRaw = trim($row['payment_type'] ?? '');
            $paymentMode = (strtoupper($paymentTypeRaw) === 'CASH') ? 'Cash' : 'Bank';
            $societyBankId = ($paymentMode == 'Cash') ? $cashAccountId : $bankAccountId;

            if (empty($ledgerTitle)) $errors[] = 'Paid To is required';
            if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw <= 0) $errors[] = 'Valid amount required';
            if (empty($societyBankId)) $errors[] = ($paymentMode == 'Cash' ? 'Cash' : 'Bank') . ' account not selected';

            $paymentDate = $this->parseExcelDate($paymentDateRaw);
            if (empty($paymentDate)) {
                $errors[] = 'Invalid/missing Payment Date';
            } elseif (!$this->isDateInCurrentFinancialYear($paymentDate)) {
                $errors[] = 'Payment Date not in current financial year';
            }

            if (!empty($errors)) {
                $results[] = ['row' => $idx, 'status' => 'error', 'errors' => $errors];
                $failed++;
                continue;
            }

            $ledgerKey = strtoupper($ledgerTitle);
            if (isset($ledgerHeadsByTitle[$ledgerKey])) {
                $ledgerHeadId = $ledgerHeadsByTitle[$ledgerKey];
            } else {
                $newLedger = SocietyLedgerHead::create([
                    'title' => $ledgerTitle,
                    'short_code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $ledgerTitle), 0, 10)),
                    'society_head_sub_category_id' => $otherIncomeSubCategoryId,
                    'account_category_id' => 3,
                    'account_head_id' => 9,
                    'society_id' => $societyId,
                    'financial_year_id' => $fyId,
                    'opening_amount' => 0,
                    'status' => 1,
                ]);
                if ($newLedger) {
                    $ledgerHeadId = $newLedger->id;
                    $ledgerHeadsByTitle[$ledgerKey] = $ledgerHeadId;
                } else {
                    $results[] = ['row' => $idx, 'status' => 'error', 'errors' => ['Could not create income head "' . $ledgerTitle . '"']];
                    $failed++;
                    continue;
                }
            }

            $chequeDate = $this->parseExcelDate($chequeDateRaw);
            $amount = (float) $amountRaw;
            $tdsAmount = ($tdsAmountRaw !== '' && is_numeric($tdsAmountRaw)) ? (float) $tdsAmountRaw : 0.00;
            $netAmount = $amount - $tdsAmount;

            $billVoucherNumber = $this->nextGeneralReceiptNumber($societyId, $fyId);

            $receipt = SocietyOtherIncome::create([
                'general_receipt_number' => $billVoucherNumber,
                'society_id'             => $societyId,
                'amount_paid'            => $amount,
                'tds_amount'             => $tdsAmount,
                'net_amount'             => $netAmount,
                // society_other_incomes.tds_bank_id is a free-text varchar(50), not a
                // foreign key despite the name (matches addGeneralReceipt()'s convention).
                'tds_bank_id'            => $tdsBankText,
                'payment_mode'           => $paymentMode,
                'cheque_no'              => !empty($chequeNo) ? $chequeNo : 0,
                'cheque_date'            => !empty($chequeDate) ? $chequeDate : null,
                'society_bank_id'        => $societyBankId,
                'description'            => !empty($particulars) ? $particulars : $remark,
                'general_bank_name'      => $bankNameText,
                'title'                  => $ledgerTitle,
                'ledger_head_id'         => $ledgerHeadId,
                'payment_date'           => $paymentDate,
                'entry_date'             => now(),
                'financial_year_id'      => $fyId,
                'status'                 => 1,
            ]);

            if ($receipt) {
                $added++;
                $results[] = ['row' => $idx, 'status' => 'success', 'voucher_no' => $billVoucherNumber];
            } else {
                $failed++;
                $results[] = ['row' => $idx, 'status' => 'error', 'errors' => ['Database save failed']];
            }
        }

        return response()->json([
            'success' => true,
            'added' => $added,
            'failed' => $failed,
            'total' => count($rows),
            'results' => $results,
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

    /**
     * Updatable columns of the opening-balance sheet: key => [CSV header, members column, type].
     * name / flat / wing double as the row's reference columns; when not ticked they are shown
     * as "... (Reference)" and ignored on upload.
     */
    private function memberOpeningFieldMap(): array
    {
        return [
            'name'             => ['Member Name', 'member_name', 'text'],
            'flat'             => ['Flat/Shop No', 'flat_no', 'text'],
            'wing'             => ['Wing Name', 'wing_id', 'wing'],
            'mobile'           => ['Mobile No', 'member_phone', 'text'],
            'email'            => ['Email Address', 'member_email', 'text'],
            'area'             => ['Area', 'area', 'text'],
            'op_principal'     => ['Opening Principal', 'op_principal', 'num'],
            'op_interest'      => ['Opening Interest', 'op_interest', 'num'],
            'op_tax'           => ['Opening Tax', 'op_tax', 'num'],
            'op_bill_date'     => ['Opening Bill Date', 'op_bill_date', 'date'],
            'op_bill_due_date' => ['Opening Bill Due Date', 'op_bill_due_date', 'date'],
        ];
    }

    /** dd-mm-yyyy / dd/mm/yyyy / yyyy-mm-dd (and the like) to Y-m-d; null when it is not a real date. */
    private function parseOpeningSheetDate(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $value, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($y < 100) {
                $y += 2000;
            }
            if ($mo > 12 && $d <= 12) {
                [$d, $mo] = [$mo, $d];
            }
        } else {
            $ts = strtotime($value);
            return $ts === false ? null : date('Y-m-d', $ts);
        }

        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    public function downloadMemberOpeningBalance(Request $request)
    {
        $societyId = $this->societyId();
        $fieldMap = $this->memberOpeningFieldMap();

        // only the ticked columns are downloaded (none ticked = all, as before)
        $selected = array_values(array_intersect(array_keys($fieldMap), (array) $request->query('fields', [])));
        if (!$selected) {
            $selected = array_keys($fieldMap);
        }

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

        $callback = function () use ($members, $selected, $fieldMap) {
            $out = fopen('php://output', 'w');
            $nameSelected = in_array('name', $selected, true);
            $flatSelected = in_array('flat', $selected, true);
            $wingSelected = in_array('wing', $selected, true);
            $columns = array_values(array_diff($selected, ['name', 'flat', 'wing']));

            $head = [
                'Member ID',
                $nameSelected ? 'Member Name' : 'Member (Reference)',
                $flatSelected ? 'Flat/Shop No' : 'Flat/Shop No (Reference)',
                'Building Name',
                $wingSelected ? 'Wing Name' : 'Wing Name (Reference)',
            ];
            foreach ($columns as $key) {
                $head[] = $fieldMap[$key][0];
            }
            fputcsv($out, $head);

            foreach ($members as $m) {
                $line = [
                    $m->id,
                    // the full name (with prefix) is only a reference unless Member Name itself is ticked for update
                    $nameSelected ? ($m->member_name ?? '') : trim($m->member_prefix . ' ' . $m->member_name),
                    $m->flat_no ?? '',
                    $m->building->building_name ?? '',
                    $m->wing->wing_name ?? '',
                ];
                foreach ($columns as $key) {
                    [, $col, $type] = $fieldMap[$key];
                    $value = $m->{$col};
                    if ($type === 'num') {
                        $value = $value ?? '0.00';
                    } elseif ($type === 'date') {
                        $ts = $value ? strtotime((string) $value) : false;
                        $value = ($ts && $ts > 86400) ? date('d-m-Y', $ts) : '';
                    }
                    $line[] = $value ?? '';
                }
                fputcsv($out, $line);
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
        $header = fgetcsv($handle) ?: [];

        // The header row decides what is updated: only the columns present in the file
        // (the ones ticked at download) are touched, everything else stays as it is.
        $fieldMap = $this->memberOpeningFieldMap();
        $columnIndex = [];
        $memberIdIndex = 0;
        foreach ($header as $i => $title) {
            $title = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $title)));
            if ($title === 'member id') {
                $memberIdIndex = $i;
            }
            foreach ($fieldMap as $key => $def) {
                if ($title === strtolower($def[0])) {
                    $columnIndex[$key] = $i;
                }
            }
        }

        if (!$columnIndex) {
            fclose($handle);
            return redirect()->route('society.memberIdentity')->with('error', 'No updatable columns found in the file. Please use the file downloaded from this page.');
        }

        $updated = 0;
        $problems = 0;
        $wingIds = [];

        while (($row = fgetcsv($handle)) !== false) {
            $memberId = trim($row[$memberIdIndex] ?? '');
            if ($memberId === '') continue;

            $member = Member::where('id', $memberId)->where('society_id', $societyId)->first();
            if (!$member) {
                $problems++;
                continue;
            }

            // blank cells are skipped, so an empty cell never wipes existing data
            $data = [];
            $rowFailed = false;
            foreach ($columnIndex as $key => $i) {
                $value = trim($row[$i] ?? '');
                if ($value === '') continue;
                [, $col, $type] = $fieldMap[$key];

                if ($type === 'num') {
                    $data[$col] = (float) str_replace(',', '', $value);
                } elseif ($type === 'date') {
                    $date = $this->parseOpeningSheetDate($value);
                    $date === null ? $rowFailed = true : $data[$col] = $date;
                } elseif ($type === 'wing') {
                    // wing is picked by name from the member's own building
                    $cacheKey = $member->building_id . '|' . strtoupper($value);
                    $wingIds[$cacheKey] ??= (int) Wing::where('society_id', $societyId)
                        ->where('building_id', $member->building_id)->where('wing_name', $value)->value('id');
                    $wingIds[$cacheKey] > 0 ? $data[$col] = $wingIds[$cacheKey] : $rowFailed = true;
                } else {
                    $data[$col] = $value;
                }
            }

            if ($rowFailed) $problems++;
            if ($data) {
                $member->update($data);
                $updated++;
            }
        }
        fclose($handle);

        $message = "{$updated} members updated." . ($problems ? " {$problems} rows had a problem (unknown member, wing or date) and were not fully updated." : '');
        return redirect()->route('society.memberIdentity')->with('success', $message);
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
        $paymentsLocked = $this->paymentsLocked();

        return view('society.modules.member-payments', compact('items', 'fyId', 'paymentModes', 'paymentsLocked'));
    }

    public function addMemberPayment(Request $request, $id = null)
    {
        if ($this->paymentsLocked()) {
            return redirect()->route('society.memberPayments')->with('error', 'Member payments are locked while Current Bill Update is set to Yes in Society Parameters. Change that setting to edit or delete a payment.');
        }

        $societyId = $this->societyId();
        $fyId = $this->fyId();
        $members = Member::where('society_id', $societyId)->where('status', 1)->orderBy('member_name')->get();
        $societyBanks = SocietyBank::where('society_id', $societyId)->get();
        $paymentModes = [1 => 'Cash', 3 => 'Cheque', 2 => 'NEFT', 4 => 'Other'];
        $editItem = $id ? MemberPayment::findOrFail($id) : null;
        $banks = Bank::active()->orderBy('bank_name')->get();

        if ($request->isMethod('post')) {
            $settlementService = app(BillSettlementService::class);

            // Cheque Return: mirrors SocietysMembersController::memberChecqueReturn()
            // (CakePHP) - filling in Cheque Return Date on an existing payment's edit
            // form takes this branch instead of a normal save. The payment record
            // itself is left untouched; only a cheque_return_details row is written
            // (upsert by payment_id), and the member's bill summary is recalculated -
            // BillSettlementService already excludes cheque-returned payments from the
            // paid amount, which is what "reverts" the payment's effect on balances.
            $chequeReturnDate = $request->input('cheque_return_date');
            if ($editItem && !empty($chequeReturnDate)) {
                $chequeReturnData = [
                    'member_id' => $editItem->member_id,
                    'payment_id' => $editItem->id,
                    'society_id' => $societyId,
                    'cheque_no' => $editItem->cheque_reference_number,
                    'cheque_amount' => $editItem->amount_paid,
                    'cheque_return_date' => $chequeReturnDate,
                    'cheque_return_reason' => $request->input('cheque_return_reason', ''),
                    'financial_year_id' => $fyId,
                ];

                $existingReturn = ChequeReturnDetail::where('payment_id', $editItem->id)->first();
                if ($existingReturn) {
                    $existingReturn->update($chequeReturnData);
                } else {
                    ChequeReturnDetail::create($chequeReturnData);
                }

                $settlementService->recalculateMemberBills(
                    $editItem->member_id, $societyId, $editItem->bill_type ?: 'reg', $fyId, $editItem->member_transfer ?? 0
                );

                return redirect()->route('society.memberPayments')->with('success', 'Cheque Data reverted successfully.');
            }

            $data = $request->only([
                'member_id', 'payment_date', 'amount_paid', 'payment_mode',
                'society_bank_id', 'cheque_reference_number', 'credited_date', 'narration',
                'entry_date', 'bank_slip_no', 'member_bank_id', 'member_bank_ifsc', 'member_bank_branch',
            ]);
            $data['society_id'] = $societyId;
            $data['financial_year_id'] = $fyId;
            $data['bill_type'] = $request->input('bill_type', 'reg');

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

        return view('society.modules.add-member-payment', compact('members', 'societyBanks', 'paymentModes', 'editItem', 'banks'));
    }

    public function deleteMemberPayment($id)
    {
        if ($this->paymentsLocked()) {
            return redirect()->route('society.memberPayments')->with('error', 'Member payments are locked while Current Bill Update is set to Yes in Society Parameters. Change that setting to edit or delete a payment.');
        }

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

    // ─── Member Payment Voucher (view / print / PDF) ──────────────────

    private static $numOnes = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    private static $numTens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    // Indian-scale (Lakh/Crore) number-to-words, same as the CakePHP app's
    // UtilComponent::convertToWords() used for society_receipt_voucher.ctp.
    private function convertAmountToWords($number)
    {
        $number = (int) $number;
        $arab = intdiv($number, 1000000000);
        $number -= $arab * 1000000000;
        $crores = intdiv($number, 10000000);
        $number -= $crores * 10000000;
        $lakhs = intdiv($number, 100000);
        $number -= $lakhs * 100000;
        $thousands = intdiv($number, 1000);
        $number -= $thousands * 1000;
        $hundreds = intdiv($number, 100);
        $number -= $hundreds * 100;
        $tens = intdiv($number, 10);
        $ones = $number % 10;

        $res = '';
        if ($arab) { $res .= $this->convertAmountToWords($arab) . ($arab > 10 ? ' Arabs ' : ' Arab '); }
        if ($crores) { $res .= $this->convertAmountToWords($crores) . ($crores > 10 ? ' Crores ' : ' Crore '); }
        if ($lakhs) { $res .= $this->convertAmountToWords($lakhs) . ($lakhs > 10 ? ' Lakhs' : ' Lakh'); }
        if ($thousands) { $res .= (empty($res) ? '' : ' ') . $this->convertAmountToWords($thousands) . ' Thousand'; }
        if ($hundreds) { $res .= (empty($res) ? '' : ' ') . $this->convertAmountToWords($hundreds) . ' Hundred'; }
        if ($tens || $ones) {
            if (!empty($res)) { $res .= ' and '; }
            if ($tens < 2) {
                $res .= self::$numOnes[$tens * 10 + $ones];
            } else {
                $res .= self::$numTens[$tens];
                if ($ones) { $res .= ' ' . self::$numOnes[$ones]; }
            }
        }
        return empty($res) ? 'Zero' : $res;
    }

    private function getAmountInRupeesWords($amount)
    {
        $split = explode('.', number_format((float) $amount, 2, '.', ''));
        $words = 'Rupees ' . $this->convertAmountToWords((int) $split[0]);
        if ((int) $split[1] > 0) {
            $words .= ' and ' . $this->convertAmountToWords((int) $split[1]) . ' Paise';
        }
        return $words . ' Only';
    }

    private function buildMemberPaymentVoucherData($id)
    {
        $societyId = $this->societyId();
        $payment = MemberPayment::with('member')->where('society_id', $societyId)->findOrFail($id);
        $society = $this->getSociety();
        $societyParameter = SocietyParameter::where('society_id', $societyId)->first();

        $bankName = '';
        if (!empty($payment->member_bank_id)) {
            $bank = Bank::find($payment->member_bank_id);
            $bankName = $bank->bank_name ?? '';
        }

        $billInfo = null;
        if ($societyParameter && $societyParameter->show_bills_in_receipt == 1) {
            $settlement = MemberBillSettlement::with('billSummary')->where('payment_id', $payment->id)->first();
            if ($settlement) {
                $billInfo = [
                    'bill_no' => $settlement->bill_no,
                    'bill_date' => $settlement->billSummary->bill_generated_date ?? null,
                ];
            }
        }

        $amountWords = $this->getAmountInRupeesWords($payment->amount_paid);

        return compact('payment', 'society', 'bankName', 'billInfo', 'amountWords');
    }

    public function memberPaymentVoucher($id)
    {
        $data = $this->buildMemberPaymentVoucherData($id);
        $data['isPdf'] = false;
        return view('society.modules.member-payment-voucher', $data);
    }

    public function memberPaymentVoucherPdf($id)
    {
        $data = $this->buildMemberPaymentVoucherData($id);
        $data['isPdf'] = true;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('society.modules.member-payment-voucher', $data)
            ->setPaper('a5');

        return $pdf->stream('Receipt-' . $data['payment']->receipt_id . '.pdf');
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

    public function downloadSampleMemberPaymentTemplate()
    {
        $societyId = $this->societyId();
        $members = Member::where('society_id', $societyId)
            ->orderBy('flat_no')
            ->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $spreadsheet->getDefaultStyle()->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Member Payment');
        $sheet->freezePane('A2');
        $sheet->getSheetView()->setZoomScale(100);

        $headers = ['UnitNo', 'Amount', 'ReceiptDate', 'RctNo', 'MemberName', 'ChqNo', 'ChqDate', 'BankName', 'BranchName', 'ClearDate', 'Remarks'];
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue($colLetters[$i] . '1', $header);
            $sheet->getStyle($colLetters[$i] . '1')->getFont()->setBold(true)->setSize(11);
            $sheet->getColumnDimension($colLetters[$i])->setAutoSize(true);
        }

        $rowNum = 2;
        foreach ($members as $m) {
            $sheet->setCellValue('A' . $rowNum, $m->flat_no);
            $sheet->setCellValue('E' . $rowNum, $m->member_name);
            $rowNum++;
        }

        $filename = 'SampleMemberPayment.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    private const MONTH_NAMES = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];

    // Ported from Cake SocietyBillsController::society_generated_bills(). Cake reads
    // the vw_member_transaction_data DB view; that view's DEFINER account doesn't
    // exist on every MySQL instance (error 1449), so this queries the same two
    // tables the view's SELECT actually draws from (members + member_bill_summaries
    // - the view's other joins are LEFT JOINs whose columns it never selects) rather
    // than the view itself. Same result set, no DEFINER dependency. The view is also
    // missing interest_paid/interest_on_due_amount, so those cells are blank here
    // too, matching Cake's own display exactly.
    public function allGeneratedBills(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        $rows = collect();

        if ($request->isMethod('post') && $fromDate !== '' && $toDate !== '') {
            session(['date.from_date' => $fromDate, 'date.to_date' => $toDate]);

            $rows = DB::table('member_bill_summaries as mbs')
                ->join('members as mem', 'mem.id', '=', 'mbs.member_id')
                ->where('mbs.society_id', $societyId)
                ->where('mbs.financial_year_id', $fyId)
                ->whereBetween('mbs.bill_generated_date', [$fromDate, $toDate])
                ->orderBy('mbs.bill_generated_date')
                ->select('mem.member_prefix', 'mem.member_name', 'mem.flat_no', 'mem.member_email', 'mem.member_phone',
                    'mbs.society_id', 'mem.building_id', 'mem.floor_no', 'mem.unit_type', 'mbs.financial_year_id',
                    'mbs.id', 'mbs.bill_no', 'mbs.bill_type', 'mbs.month', 'mbs.member_transfer', 'mbs.interest_free_amount',
                    'mbs.op_principal_arrears_original', 'mbs.jv_adjustment', 'mbs.op_principal_arrears', 'mbs.op_interest_arrears',
                    'mbs.op_due_amount', 'mbs.bill_generated_date', 'mbs.monthly_amount', 'mbs.monthly_bill_amount',
                    'mbs.amount_payable', 'mbs.op_tax_arrears', 'mbs.principal_balance', 'mbs.tax_total', 'mbs.tax_balance',
                    'mbs.interest_balance', 'mbs.principal_paid', 'mbs.tax_paid', 'mbs.interest_adjusted', 'mbs.balance_amount',
                    'mbs.monthly_principal_amount')
                ->get()
                ->unique('id')
                ->values();
        }

        return view('society.modules.generated-bills', [
            'rows' => $rows,
            'fyId' => $fyId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'monthNames' => self::MONTH_NAMES,
        ]);
    }

    // Ported from Cake SocietyBillsController::delete_all_bills_payments(), with a
    // 'delete_type' dropdown added on top of Cake's single always-delete-everything
    // button:
    //   'both'    - Cake's original behavior: bill_generates + bill_summaries +
    //               payments + journal_vouchers in range, plus the FY's
    //               MemberIdentification wipe (Cake's own comment says that wipe
    //               ignores the date range - kept exactly, tied to 'both' and
    //               'bill' since identifications describe the billing/transfer
    //               cycle, not receipts).
    //   'bill'    - only bill_generates + bill_summaries (+ identifications) in
    //               range; payments and journal vouchers are left untouched.
    //   'receipt' - only payments in range; bills, journal vouchers and
    //               identifications are left untouched.
    public function deleteAllBillsAndPayments(Request $request)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');
        if ($fromDate === '') $fromDate = (string) session('date.from_date', '');
        if ($toDate === '') $toDate = (string) session('date.to_date', '');

        if ($fromDate === '' || $toDate === '') {
            return redirect()->route('society.allGeneratedBills')->with('error', 'Select a From and To date before deleting bills.');
        }

        $deleteType = $request->input('delete_type', 'both');
        if (!in_array($deleteType, ['both', 'bill', 'receipt'], true)) {
            $deleteType = 'both';
        }

        $matchedBillCount = MemberBillSummary::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->where('bill_generated_date', '>=', $fromDate)
            ->where('bill_generated_date', '<=', $toDate)
            ->count();
        $matchedReceiptCount = MemberPayment::where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->count();

        if ($deleteType === 'both' || $deleteType === 'bill') {
            MemberIdentification::where('society_id', $societyId)->where('financial_year_id', $fyId)->delete();

            MemberBillGenerate::where('society_id', $societyId)->where('financial_year_id', $fyId)
                ->whereBetween('bill_generated_date', [$fromDate, $toDate])->delete();

            MemberBillSummary::where('society_id', $societyId)->where('financial_year_id', $fyId)
                ->whereBetween('bill_generated_date', [$fromDate, $toDate])->delete();
        }

        if ($deleteType === 'both' || $deleteType === 'receipt') {
            MemberPayment::where('society_id', $societyId)->where('financial_year_id', $fyId)
                ->whereBetween('payment_date', [$fromDate, $toDate])->delete();
        }

        if ($deleteType === 'both') {
            JournalVoucher::where('society_id', $societyId)->where('financial_year_id', $fyId)
                ->whereBetween('voucher_date', [$fromDate, $toDate])->delete();
        }

        $deletedCount = $deleteType === 'receipt' ? $matchedReceiptCount : $matchedBillCount;
        $label = $deleteType === 'receipt' ? 'receipt(s)' : ($deleteType === 'bill' ? 'bill(s)' : 'bill(s) and receipt(s)');

        if ($deletedCount > 0) {
            return redirect()->route('society.allGeneratedBills')->with('success', "Deleted {$deletedCount} {$label} from {$fromDate} to {$toDate}.");
        }

        return redirect()->route('society.allGeneratedBills')->with('error', "No {$label} found between {$fromDate} and {$toDate} for the current financial year - nothing was deleted.");
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
        return view('society.modules.update-opening-balance');
    }

    private const OPENING_BALANCE_CSV_HEADER = [
        'Sr.No.', 'Member ID', 'Member Name', 'Flat No', 'Society ID',
        'Year ID', 'Year', 'Principal Balance', 'Interest Balance', 'Tax Balance',
    ];

    // Ported from Cake SocietysController::download_member_details - CSV of the
    // current financial year's member_year_wise_closing_balance rows.
    public function downloadMemberDetails()
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        $rows = DB::table('members as m')
            ->join('member_year_wise_closing_balance as cb', function ($j) {
                $j->on('m.id', '=', 'cb.member_id')->on('m.society_id', '=', 'cb.society_id');
            })
            ->leftJoin('financial_year_master as fy', 'cb.year_id', '=', 'fy.id')
            ->where('m.society_id', $societyId)
            ->where('cb.year_id', $fyId)
            ->selectRaw('cb.id AS cd_id, m.id AS m_id, m.member_name, m.flat_no, m.society_id, cb.year_id, fy.year, '
                . 'CAST(cb.principal_balance AS CHAR) AS principal_balance, '
                . 'CAST(cb.interest_balance AS CHAR) AS interest_balance, '
                . 'CAST(cb.tax_balance AS CHAR) AS tax_balance')
            ->get();

        $filename = 'Member_details_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) {
                fputcsv($out, self::OPENING_BALANCE_CSV_HEADER);
            }
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->cd_id ?? '',
                    $r->m_id,
                    $r->member_name,
                    $r->flat_no,
                    $r->society_id,
                    $r->year !== null ? $r->year_id : '',
                    $r->year ?? '',
                    $r->principal_balance ?? 0,
                    $r->interest_balance ?? 0,
                    $r->tax_balance ?? 0,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Ported from Cake SocietysController::upload_member_details. Updates
    // member_year_wise_closing_balance by Sr.No. and the latest
    // member_bill_summaries row of that member/year. Unlike Cake, rows are
    // limited to the logged-in society.
    public function uploadMemberDetails(Request $request)
    {
        $request->validate(['member_csv' => 'required|file']);

        $file = $request->file('member_csv');
        if (strtolower($file->getClientOriginalExtension()) !== 'csv') {
            return redirect()->route('society.updateOpeningBalance')->with('error', 'Only CSV files are allowed.');
        }

        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), $header ?: []);

        $missing = array_diff(self::OPENING_BALANCE_CSV_HEADER, $header);
        if ($missing) {
            fclose($handle);
            return redirect()->route('society.updateOpeningBalance')
                ->with('error', 'Invalid CSV format. Missing columns: ' . implode(', ', $missing));
        }

        $societyId = $this->societyId();
        $updated = $skipped = $billUpdated = $billSkipped = 0;
        $errors = [];
        $line = 1;

        while (($raw = fgetcsv($handle)) !== false) {
            $line++;
            if (!array_filter($raw, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $raw = array_pad($raw, count($header), '');
            $row = array_combine($header, array_slice($raw, 0, count($header)));

            if (empty($row['Sr.No.']) || !is_numeric($row['Sr.No.'])) {
                $skipped++;
                $errors[] = "Row {$line}: Invalid Sr.No.";
                continue;
            }

            $id = (int) $row['Sr.No.'];
            $principal = (float) ($row['Principal Balance'] ?? 0);
            $interest = (float) ($row['Interest Balance'] ?? 0);
            $tax = (float) ($row['Tax Balance'] ?? 0);

            $closing = DB::table('member_year_wise_closing_balance')
                ->where('id', $id)->where('society_id', $societyId);
            if (!$closing->exists()) {
                $skipped++;
                $errors[] = "Row {$line}: Record not found (ID: {$id})";
                continue;
            }

            $closing->update([
                'principal_balance' => $principal,
                'interest_balance' => $interest,
                'tax_balance' => $tax,
            ]);
            $updated++;

            $memberId = (int) ($row['Member ID'] ?? 0);
            $yearId = (int) ($row['Year ID'] ?? 0);
            if (!$memberId || !$yearId) {
                $billSkipped++;
                continue;
            }

            $billId = DB::table('member_bill_summaries')
                ->where('society_id', $societyId)
                ->where('member_id', $memberId)
                ->where('financial_year_id', $yearId)
                ->orderByDesc('id')->orderByDesc('udate')
                ->value('id');

            if ($billId) {
                DB::table('member_bill_summaries')->where('id', $billId)->update([
                    'principal_balance' => $principal,
                    'interest_balance' => $interest,
                    'tax_balance' => $tax,
                    'balance_amount' => $principal + $interest + $tax,
                ]);
                $billUpdated++;
            } else {
                $billSkipped++;
            }
        }
        fclose($handle);

        return redirect()->route('society.updateOpeningBalance')->with('ob_result', [
            'closing' => "Updated: {$updated}, Skipped: {$skipped}",
            'bill' => "Updated: {$billUpdated}, Skipped: {$billSkipped}",
            'errors' => $errors,
        ]);
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

    /**
     * Report - Accounts (CakePHP: account_reports/account). A page of report buttons; the list
     * lives in config/account_reports.php. This route is Society-only, and a Society login sees
     * every button (the CakePHP Member restriction is recorded in the config for the day a
     * Member version of this page is built).
     */
    public function reportAccounts()
    {
        return view('society.modules.report-accounts', [
            'title' => 'Report - Accounts',
            'reports' => $this->accountReportButtons(),
        ]);
    }

    /**
     * One report of the Accounts page. The reports themselves are not migrated yet, so this
     * shows the same "under development" page the other unmigrated modules show.
     */
    public function reportAccountsItem(string $report)
    {
        $found = collect(config('account_reports'))->firstWhere('slug', $report);
        if (!$found) {
            abort(404);
        }

        return view('society.modules.placeholder', [
            'title' => $found['label'],
        ]);
    }

    private function accountReportButtons(): array
    {
        $buttons = [];
        foreach (config('account_reports') as $report) {
            if (!empty($report['action'])) {
                $report['route'] = 'society.reports.' . $report['action'];
            }
            $report['url'] = $report['route'] && \Illuminate\Support\Facades\Route::has($report['route'])
                ? route($report['route'])
                : route('society.reportAccountsItem', $report['slug']);
            $buttons[] = $report;
        }

        return $buttons;
    }

    public function reportSociety()
    {
        return view('society.modules.placeholder', [
            'title' => 'Report - Society',
        ]);
    }

    // ─── Journal Voucher ───────────────────────────────────────────
    // Port of ReportsController::journal_vouchers() / delete_journal_voucher()
    // (CakePHP). Two account "sides" per row: a Society Member (jv_*_member_head_id)
    // or a Ledger Head (jv_*_ledger_head_id) - the add-form combines both into one
    // dropdown ("member-123", "member-123-old" for a transferred-out member, or
    // "ledger-45"); the edit-form instead posts two separate selects.

    private function jvMemberList($societyId)
    {
        return Member::where('status', 1)->where('society_id', $societyId)->orderBy('id')->pluck('member_name', 'id');
    }

    private function jvMemberFlatNoList($societyId)
    {
        return Member::where('status', 1)->where('society_id', $societyId)->orderBy('id')->pluck('flat_no', 'id');
    }

    private function jvOldMemberList($societyId)
    {
        // Port of SocietyBillComponent::getSocietyOldMeberFlatList() - the most
        // recent member_identifications row (by id) for every member who has a
        // transfer history (member_transfer > 0), i.e. the previous owner's name.
        return DB::select('
            select third_member, member_id, flat_no from member_identifications where id in (
                select max(id) from member_identifications where member_id in (
                    select id from members where member_transfer > 0 and society_id = ? and status = 1
                ) group by member_id
            )
        ', [$societyId]);
    }

    private function jvLedgerHeadList($societyId)
    {
        // society_head_sub_category_id 20/21 (Bank/Cash Balances) excluded, same as
        // the CakePHP view's $ledgerHeadForJournalVouchers.
        return SocietyLedgerHead::where('status', 1)
            ->where('society_id', $societyId)
            ->whereNotIn('society_head_sub_category_id', [20, 21])
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function nextJournalVoucherNumber($societyId, $fyId)
    {
        $max = JournalVoucher::where('society_id', $societyId)->where('financial_year_id', $fyId)->max('voucher_no');
        return ($max !== null && $max >= 0) ? ((int) $max + 1) : 1;
    }

    private function jvApplyArrearsAdjustment($societyId, $fyId, $jvBlock, $paymentType)
    {
        if (empty($jvBlock['type'])) return;

        foreach ($jvBlock['type'] as $count => $jvType) {
            $rawId = $jvBlock['jv_member_ledger_head_id'][$count] ?? '';
            $exploded = explode('-', $rawId);
            if (($exploded[0] ?? '') !== 'member') continue;
            $memberId = $exploded[1] ?? null;
            if (empty($memberId)) continue;

            $summary = MemberBillSummary::where('member_id', $memberId)
                ->where('society_id', $societyId)
                ->where('financial_year_id', $fyId)
                ->orderByDesc('bill_no')
                ->first();
            if (!$summary) continue;

            $principal = (float) $summary->principal_balance;
            $interest = (float) $summary->interest_balance;
            $tax = (float) $summary->tax_balance;

            if ($jvType === 'Debit') {
                $amount = (float) ($jvBlock['jv_amount_debit'][$count] ?? 0);
                if ($paymentType === 'Principal Arrears') $principal += $amount;
                elseif ($paymentType === 'Interest Arrears') $interest += $amount;
                elseif ($paymentType === 'Tax Arrears') $tax += $amount;
            } elseif ($jvType === 'Credit') {
                $amount = (float) ($jvBlock['jv_amount_credit'][$count] ?? 0);
                if ($paymentType === 'Principal Arrears') $principal -= $amount;
                elseif ($paymentType === 'Interest Arrears') $interest -= $amount;
                elseif ($paymentType === 'Tax Arrears') $tax -= $amount;
            } else {
                continue;
            }

            $summary->update([
                'principal_balance' => $principal,
                'interest_balance' => $interest,
                'tax_balance' => $tax,
                'balance_amount' => $principal + $interest + $tax,
            ]);
        }
    }

    public function journalVoucher(Request $request, $voucherNo = null)
    {
        $societyId = $this->societyId();
        $fyId = $this->fyId();

        if ($request->isMethod('post')) {
            $postData = $request->all();
            $jvBlock = $postData['JournalVoucher'] ?? [];
            $paymentType = $postData['payment']['type'][0] ?? '';

            // Only ever populated for an add-mode submission - an edit-mode submission
            // posts per-row keyed fields instead, so this is a no-op there (matches the
            // CakePHP original, where foreach() over the missing 'type' key is also a no-op).
            $this->jvApplyArrearsAdjustment($societyId, $fyId, $jvBlock, $paymentType);

            $voucherDate = $jvBlock['voucher_date'] ?? '';
            $note = $jvBlock['note'] ?? '';
            $success = [];

            if (!empty($jvBlock['voucher_no'])) {
                // Edit of an existing voucher: top-level numeric keys of $postData are
                // journal_vouchers.id values (see the edit-mode table in the view).
                foreach ($postData as $rowId => $rowValue) {
                    if (!is_numeric($rowId)) continue;

                    $rowJv = $rowValue['JournalVoucher'] ?? [];
                    $jvType = $rowJv['jv_type'] ?? '';
                    $jvMemberId = !empty($rowJv['jv_member_head_id']) ? $rowJv['jv_member_head_id'] : 0;
                    $jvLedgerId = !empty($rowJv['jv_ledger_head_id']) ? $rowJv['jv_ledger_head_id'] : 0;

                    $isOldMember = false;
                    if (strpos((string) $jvMemberId, '-') !== false) {
                        $isOldMember = true;
                        $explodedMemberId = explode('-', $jvMemberId);
                        // Ported CakePHP bug, kept intentionally: this should be index [1]
                        // (the numeric member id) but instead keeps index [0] (the literal
                        // string "member"), so editing a row onto an Old Member looks up
                        // Member.id = 'member' (no match) and saves a bogus member_transfer.
                        $jvMemberId = $explodedMemberId[0];
                    }

                    $data = [
                        'voucher_no' => $jvBlock['voucher_no'],
                        'note' => $note,
                    ];

                    if ($jvType == 'Credit') {
                        $data['jv_credit_ledger_head_id'] = $jvLedgerId;
                        $data['jv_credit_member_head_id'] = $jvMemberId;
                        $data['jv_creadit_type'] = 'Credit';
                        $data['jv_type'] = 'Credit';
                        $data['jv_amount_credited'] = $rowJv['jv_amount_credited'] ?? '';
                        $data['jv_debit_ledger_head_id'] = 0;
                        $data['jv_debit_member_head_id'] = 0;
                        $data['jv_amount_debited'] = 0;
                        $data['jv_debit_type'] = '';
                    } else {
                        $data['jv_debit_ledger_head_id'] = $jvLedgerId;
                        $data['jv_debit_member_head_id'] = $jvMemberId;
                        $data['jv_debit_type'] = 'Debit';
                        $data['jv_type'] = 'Debit';
                        $data['jv_amount_debited'] = $rowJv['jv_amount_debited'] ?? '';
                        $data['jv_credit_ledger_head_id'] = 0;
                        $data['jv_credit_member_head_id'] = 0;
                        $data['jv_amount_credited'] = 0;
                        $data['jv_creadit_type'] = '';
                    }

                    $memberTransfer = Member::where('id', $jvMemberId)->value('member_transfer');
                    if ($isOldMember) $memberTransfer = ($memberTransfer ?? 0) - 1;
                    $data['member_transfer'] = $memberTransfer;

                    if ($this->isDateInCurrentFinancialYear($voucherDate)) {
                        // Scoped to this society - not in the CakePHP original, added so an
                        // edit can never touch another society's voucher row by id.
                        // a crafted post carrying a Debit/Credit Note row id must not update that note
                        $updated = JournalNotes::jvOnly(JournalVoucher::where('id', $rowId)->where('society_id', $societyId))->update($data);
                        $success[] = $updated ? 1 : 0;
                    } else {
                        $success[] = 0;
                    }
                }
            } else {
                // Addition of a new voucher: parallel arrays, one entry per Dr/Cr row,
                // all sharing one freshly generated voucher_no.
                $newVoucherNo = $this->nextJournalVoucherNumber($societyId, $fyId);

                foreach (($jvBlock['type'] ?? []) as $key => $jvType) {
                    $rawId = $jvBlock['jv_member_ledger_head_id'][$key] ?? '';
                    $isOldMember = false;

                    if (strpos($rawId, 'member-') !== false) {
                        $memberId = str_replace('member-', '', $rawId);
                        $ledgerId = 0;
                        if (strpos($memberId, 'old') !== false) {
                            $isOldMember = true;
                            $explodedMemberId = explode('-', $memberId);
                            $memberId = $explodedMemberId[0];
                        }
                    } else {
                        $ledgerId = str_replace('ledger-', '', $rawId);
                        $memberId = 0;
                    }

                    $data = [
                        'society_id' => $societyId,
                        'voucher_no' => $newVoucherNo,
                        'voucher_date' => $voucherDate,
                        'note' => $note,
                        'financial_year_id' => $fyId,
                    ];

                    if ($jvType == 'Debit') {
                        $data['jv_debit_ledger_head_id'] = $ledgerId;
                        $data['jv_debit_member_head_id'] = $memberId;
                        $data['jv_credit_ledger_head_id'] = 0;
                        $data['jv_credit_member_head_id'] = 0;
                        $data['jv_amount_debited'] = !empty($jvBlock['jv_amount_debit'][$key]) ? $jvBlock['jv_amount_debit'][$key] : 0;
                        $data['jv_amount_credited'] = 0;
                        $data['jv_type'] = 'Debit';
                        $data['jv_debit_type'] = 'Debit';
                    } elseif ($jvType == 'Credit') {
                        $data['jv_credit_ledger_head_id'] = $ledgerId;
                        $data['jv_credit_member_head_id'] = $memberId;
                        $data['jv_debit_ledger_head_id'] = 0;
                        $data['jv_debit_member_head_id'] = 0;
                        $data['jv_amount_credited'] = !empty($jvBlock['jv_amount_credit'][$key]) ? $jvBlock['jv_amount_credit'][$key] : 0;
                        $data['jv_amount_debited'] = 0;
                        $data['jv_type'] = 'Credit';
                        $data['jv_creadit_type'] = 'Credit';
                    } else {
                        continue;
                    }

                    $memberTransfer = Member::where('id', $memberId)->value('member_transfer');
                    if ($isOldMember) $memberTransfer = ($memberTransfer ?? 0) - 1;
                    $data['member_transfer'] = $memberTransfer;

                    if ($this->isDateInCurrentFinancialYear($voucherDate)) {
                        $success[] = JournalVoucher::create($data) ? 1 : 0;
                    } else {
                        $success[] = 0;
                    }
                }
            }

            if (!empty($success) && !in_array(0, $success, true)) {
                return redirect()->route('society.journalVoucher')->with('success', 'The journal vouchers has been saved.');
            }
            return redirect()->route('society.journalVoucher')->with('error', 'The journal vouchers could not be saved. Please, try again.');
        }

        $memberList = $this->jvMemberList($societyId);
        $oldMemberList = $this->jvOldMemberList($societyId);
        $flatNoList = $this->jvMemberFlatNoList($societyId);
        $ledgerHeadList = $this->jvLedgerHeadList($societyId);

        $oldMemberNameById = [];
        foreach ($oldMemberList as $om) {
            if (!empty($om->member_id)) {
                $oldMemberNameById[$om->member_id] = $om->third_member;
            }
        }

        $editRows = collect();
        if (!empty($voucherNo)) {
            // Debit/Credit Note rows share this table but are never opened on the Journal Voucher screen.
            $editRows = JournalNotes::jvOnly(JournalVoucher::where('voucher_no', $voucherNo)
                ->where('society_id', $societyId)
                ->where('financial_year_id', $fyId))
                ->orderBy('voucher_no')
                ->get();
        }

        $items = JournalNotes::jvOnly(JournalVoucher::where('society_id', $societyId)
            ->where('financial_year_id', $fyId))
            ->orderBy('id')
            ->get();

        // Enrich each row with the display title for whichever "side" (credit or
        // debit) actually has a ledger/member set - mirrors the CakePHP loop that
        // builds creadit_title / debit_title for the register table below the form.
        foreach ($items as $jv) {
            $ledgerId = $jv->jv_credit_ledger_head_id ?: $jv->jv_debit_ledger_head_id;
            if ($ledgerId) {
                $title = SocietyLedgerHead::where('id', $ledgerId)->value('title') ?? '';
                if ($jv->jv_credit_ledger_head_id) $jv->creadit_title = $title;
                elseif ($jv->jv_debit_ledger_head_id) $jv->debit_title = $title;
            }

            $memberId = $jv->jv_credit_member_head_id ?: $jv->jv_debit_member_head_id;
            if ($memberId) {
                $member = Member::find($memberId);
                $displayName = $member->member_name ?? '';
                // A row saved with a lower transfer number belongs to the previous
                // owner; transfer has since overwritten member_name, so show the old name.
                if ($member && $jv->member_transfer !== null && $jv->member_transfer < $member->member_transfer
                        && !empty($oldMemberNameById[$memberId] ?? null)) {
                    $displayName = $oldMemberNameById[$memberId];
                }
                if ($displayName !== '' && !empty($flatNoList[$memberId] ?? null)) {
                    $displayName .= ' -- ' . $flatNoList[$memberId];
                }
                if ($jv->jv_credit_member_head_id) $jv->creadit_title = $displayName;
                elseif ($jv->jv_debit_member_head_id) $jv->debit_title = $displayName;
            }
        }

        return view('society.modules.journal-voucher', compact(
            'items', 'fyId', 'memberList', 'oldMemberList', 'flatNoList', 'ledgerHeadList', 'editRows', 'voucherNo'
        ));
    }

    public function deleteJournalVoucher($voucherNo)
    {
        $societyId = $this->societyId();
        $deleted = JournalNotes::jvOnly(JournalVoucher::where('voucher_no', $voucherNo)->where('society_id', $societyId))->delete();

        if ($deleted) {
            return redirect()->route('society.journalVoucher')->with('success', 'The journal voucher has been deleted');
        }
        return redirect()->route('society.journalVoucher')->with('error', 'There was an error deleting the journal voucher. Please, try again.');
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

        // Ported from Cake getAllMembersBillSummaryDetails() (~SocietyBillsController.php:8025-8059):
        // drives which of Update / Current Bill Update / "Historical bill - locked" the bill
        // modal shows. NULL (never configured) = old Update available on every bill. Explicit
        // 0/1 both hide old Update; 1 additionally allows Current Bill Update, but only on the
        // member's own actual latest bill (isCurrentBill), independently re-derived here rather
        // than trusted from the request - never MAX(id), the same member+society+bill_type+
        // financial_year+transfer scope used everywhere else this "current bill" concept appears.
        $societyParamForGate = SocietyParameter::where('society_id', $sid)->first();
        $curBillUpdateRawVal = $societyParamForGate->current_bill_update_enabled ?? null;
        $response['oldUpdateAvailable'] = is_null($curBillUpdateRawVal) ? 1 : 0;
        $response['currentBillUpdateEnabled'] = ((int) $curBillUpdateRawVal === 1) ? 1 : 0;
        $response['isCurrentBill'] = 0;
        if ($response['currentBillUpdateEnabled'] && $bill->member_id) {
            $settlementServiceForGate = app(BillSettlementService::class);
            $latestTransferNo = $settlementServiceForGate->getLatestTransferNo($bill->member_id, $sid);
            $currentBillForModal = MemberBillSummary::where('member_id', $bill->member_id)
                ->where('society_id', $sid)
                ->where('bill_type', $bill->bill_type)
                ->where('member_transfer', $latestTransferNo)
                ->where('financial_year_id', $bill->financial_year_id)
                ->orderByDesc('bill_generated_date')->orderByDesc('id')
                ->first();
            if ($currentBillForModal && (int) $currentBillForModal->id === (int) $bill->id) {
                $response['isCurrentBill'] = 1;
            }
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

    // Ported from Cake updateMemberBillSummaryById() plus the "Current Bill Update" society
    // parameter it now respects. Cake itself never added a server-side check here (the old
    // button is only hidden client-side there) - this adds one, since the user-facing
    // requirement is that previous/all-bill updates must be genuinely blocked, not just
    // hidden, once the parameter has been explicitly set either way.
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

        $params = SocietyParameter::where('society_id', $sid)->first();
        $curVal = $params->current_bill_update_enabled ?? null;
        if (!is_null($curVal)) {
            $response['error_message'] = ((int) $curVal === 1)
                ? 'All-bill Update is disabled for this society. Use Current Bill Update instead (only available on the latest bill).'
                : 'Manual bill update is disabled for this society (Society Parameters > Current Bill Update = No).';
            return response()->json($response);
        }

        $bill->update([
            'discount' => floatval($request->input('discount', 0)),
            'principal_adjusted' => floatval($request->input('principal_adjusted', 0)),
            'interest_adjusted' => floatval($request->input('interest_adjusted', 0)),
            'interest_on_due_amount' => floatval($request->input('interest_on_due_amount', 0)),
        ]);

        $billType = $bill->bill_type ?: 'reg';
        $tariff = $request->input('tariff', []);
        $memberIdForTariff = $request->input('member_id', $bill->member_id);
        $billNoForTariff = $request->input('bill_no');
        if (!empty($tariff) && !empty($billNoForTariff)) {
            $this->updateMemberTariffDetails(
                $tariff, $memberIdForTariff, $billNoForTariff, $request->input('month', $bill->month),
                $request->input('bill_generated_date', $bill->bill_generated_date), $billType, $bill->financial_year_id
            );
        }

        $settlementService = app(BillSettlementService::class);
        $memberTransfer = $settlementService->getLatestTransferNo($bill->member_id, $sid);

        $settlementService->recalculateMemberBills(
            $bill->member_id, $sid, $billType, $fyId, $memberTransfer
        );

        $response['error'] = 0;
        $response['error_message'] = '';
        return response()->json($response);
    }

    // Ported from Cake updateCurrentMemberBillSummaryById() (~SocietyBillsController.php:9464):
    // only runs when the society has current_bill_update_enabled = 1 (Yes), and only against
    // the member's own actual latest bill - both re-checked here independently, never trusted
    // from the request. Recalculation itself goes through
    // BillSettlementService::recalculateCurrentBillOnly(), which guarantees no other bill's
    // row is ever persisted-changed (see that method's docblock for how).
    public function updateCurrentMemberBillSummaryById(Request $request)
    {
        $sid = $this->societyId();
        $response = ['error' => 1, 'error_message' => 'Current bill could not be updated'];

        $summaryId = $request->input('id');
        if (!$summaryId) {
            $response['error_message'] = 'Invalid bill id.';
            return response()->json($response);
        }

        $bill = MemberBillSummary::where('id', $summaryId)->where('society_id', $sid)->first();
        if (!$bill) {
            $response['error_message'] = 'Bill not found.';
            return response()->json($response);
        }

        $params = SocietyParameter::where('society_id', $sid)->first();
        if (empty($params->current_bill_update_enabled)) {
            $response['error_message'] = 'Current Bill Update is not enabled for this society. Enable it from Society Parameters.';
            return response()->json($response);
        }

        $settlementService = app(BillSettlementService::class);
        $billType = $bill->bill_type ?: 'reg';
        $financialYearId = $bill->financial_year_id;
        $memberTransfer = $settlementService->getLatestTransferNo($bill->member_id, $sid);

        $currentBillLookup = MemberBillSummary::where('member_id', $bill->member_id)
            ->where('society_id', $sid)
            ->where('bill_type', $billType)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $financialYearId)
            ->orderByDesc('bill_generated_date')->orderByDesc('id')
            ->first();

        if (!$currentBillLookup) {
            $response['error_message'] = 'No current bill found for this member.';
            return response()->json($response);
        }

        if ((int) $currentBillLookup->id !== (int) $bill->id) {
            $response['error_message'] = 'This is not the current/latest bill. Current Bill Update only applies to the latest generated bill.';
            return response()->json($response);
        }

        $bill->update([
            'discount' => floatval($request->input('discount', $bill->discount ?? 0)),
            'principal_adjusted' => floatval($request->input('principal_adjusted', $bill->principal_adjusted ?? 0)),
            'interest_adjusted' => floatval($request->input('interest_adjusted', $bill->interest_adjusted ?? 0)),
            'interest_on_due_amount' => floatval($request->input('interest_on_due_amount', $bill->interest_on_due_amount ?? 0)),
        ]);

        $tariff = $request->input('tariff', []);
        $memberIdForTariff = $request->input('member_id', $bill->member_id);
        $billNoForTariff = $request->input('bill_no', $bill->bill_no);
        if (!empty($tariff) && !empty($billNoForTariff)) {
            $this->updateMemberTariffDetails(
                $tariff, $memberIdForTariff, $billNoForTariff, $request->input('month', $bill->month),
                $request->input('bill_generated_date', $bill->bill_generated_date), $billType, $financialYearId
            );
        }

        $ok = $settlementService->recalculateCurrentBillOnly(
            $bill->member_id, $sid, $billType, $financialYearId, $memberTransfer, $bill->id
        );

        if (!$ok) {
            $response['error_message'] = 'Current bill could not be updated.';
            return response()->json($response);
        }

        $response['error'] = 0;
        $response['error_message'] = 'Current bill updated successfully.';
        $response['id'] = $bill->id;
        $response['month'] = $bill->month;
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

    // Ported from Cake updateMemberTarrifDetails() (~SocietyBillsController.php:10875):
    // the only inputs on the bill-edit modal meant to be hand-edited are the Particulars/
    // Amount lines - this writes each edited line back to member_bill_generates (recomputing
    // GST the same way generateBill() does), then re-sums monthly_amount/tax totals onto the
    // bill itself. Everything else on the modal (Bill Amount, arrears, Amount Payable) is
    // read-only in the view and gets its numbers from BillSettlementService afterward, never
    // written here. Skips Cake's updateAppliedParameterOnBill()/BillAppliedParameter write -
    // that's per-bill historical interest-method tracking, a separate feature this doesn't
    // touch; BillSettlementService currently always uses the society's CURRENT parameters.
    private function updateMemberTariffDetails($tariff, $memberId, $billNo, $billMonth, $billGeneratedDate, $billType, $financialYearId)
    {
        if (empty($tariff) || empty($memberId) || empty($billNo)) {
            return false;
        }

        $sid = $this->societyId();
        $params = SocietyParameter::where('society_id', $sid)->first();
        if (!$params) {
            return false;
        }

        $multiplyTariffAmountValue = $this->multiplyTariffAmountByBillingFrequency($params);
        $billLedgerHeadSettings = $this->memberRegularBillLedgerHeadSettings($sid);

        $totalMonthlyAmount = 0;
        $igstTotal = $cgstTotal = $sgstTotal = $taxTotal = 0;

        foreach ($tariff as $ledgerHeadId => $amount) {
            $amount = floatval($amount);
            $totalMonthlyAmount += $amount;
            $igstAmt = $cgstAmt = $sgstAmt = $lineTax = 0;

            if (!empty($billLedgerHeadSettings['tax']) && in_array((int) $ledgerHeadId, $billLedgerHeadSettings['tax'], true)) {
                if ($params->igst_tax_per > 0) {
                    $igstAmt = ($amount * $multiplyTariffAmountValue) * ($params->igst_tax_per / 100);
                }
                if ($params->cgst_tax_per > 0) {
                    $cgstAmt = ($amount * $multiplyTariffAmountValue) * ($params->cgst_tax_per / 100);
                }
                if ($params->sgst_tax_per > 0) {
                    $sgstAmt = ($amount * $multiplyTariffAmountValue) * ($params->sgst_tax_per / 100);
                }
                $lineTax = $igstAmt + $cgstAmt + $sgstAmt;
                $igstTotal += $igstAmt;
                $cgstTotal += $cgstAmt;
                $sgstTotal += $sgstAmt;
                $taxTotal += $lineTax;
            }

            $existing = MemberBillGenerate::where('society_id', $sid)
                ->where('month', $billMonth)
                ->where('ledger_head_id', $ledgerHeadId)
                ->where('member_id', $memberId)
                ->where('bill_type', $billType)
                ->where('financial_year_id', $financialYearId)
                ->first();

            if ($existing) {
                $existing->update([
                    'amount' => $amount, 'igst_total' => $igstAmt, 'cgst_total' => $cgstAmt,
                    'sgst_total' => $sgstAmt, 'tax_total' => $lineTax,
                ]);
            } else {
                MemberBillGenerate::create([
                    'member_id' => $memberId, 'bill_type' => $billType, 'society_id' => $sid,
                    'month' => $billMonth, 'ledger_head_id' => $ledgerHeadId, 'amount' => $amount,
                    'igst_total' => $igstAmt, 'cgst_total' => $cgstAmt, 'sgst_total' => $sgstAmt,
                    'tax_total' => $lineTax, 'bill_number' => $billNo, 'bill_generated_date' => $billGeneratedDate,
                    'financial_year_id' => $financialYearId,
                ]);
            }
        }

        return MemberBillSummary::where('society_id', $sid)
            ->where('bill_no', $billNo)
            ->where('month', $billMonth)
            ->where('member_id', $memberId)
            ->where('financial_year_id', $financialYearId)
            ->update([
                'monthly_amount' => $totalMonthlyAmount,
                'igst_total' => $igstTotal,
                'cgst_total' => $cgstTotal,
                'sgst_total' => $sgstTotal,
                'tax_total' => $taxTotal,
            ]) !== false;
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
                // Nothing unpaid on the last bill (zero, or an advance shown as a negative balance): no interest.
                if ($principalAmount <= 0) {
                    return 0;
                }
                $interestPerMonth = $principalAmount * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            } elseif ($interestTypeId == 3) {
                $balAmount = floatval($lastMonthBillDetails['balance_amount'] ?? 0) - floatval($lastMonthBillDetails['interest_free_amount'] ?? 0);
                if ($balAmount <= 0) {
                    return 0;
                }
                $interestPerMonth = $balAmount * (($interestRate / 12) / 100);
                $interestOnDue = round($interestPerMonth * $delayMonth);
            }
        }
        return $interestOnDue;
    }

    /**
     * Calendar days from $startDate to $endDate; an end on or before the start is 0 days (never negative, and
     * never the absolute value). Delay Days counts a late payment as  payment date - due date  (due 15-Jul, paid
     * 16-Jul = 1 day, 20-Jul = 5 days, on or before the due date = 0). A segment that ends on a payment date and the
     * next one that starts on it share that one boundary day, so it is not counted twice.
     */
    private function delayDayCount($startDate, $endDate): int
    {
        if (empty($startDate) || empty($endDate) || $startDate == '0000-00-00' || $endDate == '0000-00-00') {
            return 0;
        }
        $days = (int) \Carbon\Carbon::parse($startDate)->startOfDay()->diffInDays(\Carbon\Carbon::parse($endDate)->startOfDay(), false);
        return $days > 0 ? $days : 0;
    }

    /**
     * Due date protection: a bill whose due date is on or after the date the next bill is generated has not become
     * overdue, so it carries no interest. An empty / zero due date is left to the caller's old behaviour.
     */
    private function dueDateNotCrossed($dueDate, $newBillDate): bool
    {
        if (empty($dueDate) || empty($newBillDate) || $dueDate == '0000-00-00' || $newBillDate == '0000-00-00') {
            return false;
        }
        $due = strtotime($dueDate);
        $new = strtotime($newBillDate);
        return $due !== false && $new !== false && $due >= $new;
    }

    /**
     * Credit Notes dated INSIDE the interest window (after the last bill's date, before the new bill's date) settle
     * principal exactly like a payment does, so interest must not keep running on that principal. Credits dated on
     * or before the last bill's date are already counted by getCreditedJvData() in calculateAmountAvailable().
     * Regular bills only; manual notes pinned to a bill are applied to that bill. Debit Notes are NOT a payment
     * and are not touched here.
     *
     * @return \Illuminate\Support\Collection<int, object> payment-shaped rows (payment_date, amount_paid), oldest first
     */
    private function mergeCreditNotesIntoWindow($memberPaymentData, $afterDate, $beforeDate, $memberId, $sid, $memberTransfer, $fyId)
    {
        $jvRows = JournalNotes::excludeManual(DB::table('journal_vouchers')
            ->where('voucher_date', '>', $afterDate)
            ->where('voucher_date', '<', $beforeDate)
            ->where('jv_credit_member_head_id', $memberId)
            ->where('society_id', $sid)
            ->where('financial_year_id', $fyId)
            ->where('member_transfer', $memberTransfer))
            ->select('id', 'jv_amount_credited', 'voucher_date')
            ->get();

        if ($jvRows->isEmpty()) {
            return $memberPaymentData;
        }

        $credits = $jvRows->map(fn ($jv) => (object) [
            'id' => 'JV-' . $jv->id,
            'amount_paid' => $jv->jv_amount_credited,
            'payment_date' => $jv->voucher_date,
        ]);

        return $memberPaymentData->concat($credits)->sortBy(fn ($p) => strtotime($p->payment_date))->values();
    }

    private function delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDate, &$totalDays)
    {
        $interestOnDue = 0;
        // $paymentDate is where this segment ENDS: the payment date, or for the part still unpaid when the next bill
        // is generated, the date of that bill. Days = end - start, no "+1 day" (that charged 1 day late as 2).
        $delayDays = $this->delayDayCount($billDueDate, $paymentDate);
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
        // Same day count as delayDays(): $paymentDate is where the segment ends, days = end - start.
        $delayDays = $this->delayDayCount($billDueDate, $paymentDate);
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
            // $billGeneratedDate arrives as the day BEFORE the new bill, so $date1 is the new bill's own date.
            // Due date protection: due on or after the new bill's date = not overdue yet = no interest.
            if ($this->dueDateNotCrossed($billDueDate, $date1->format('Y-m-d'))) {
                return 0;
            }
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
        $jvRows = JournalNotes::excludeManual(DB::table('journal_vouchers')
            ->where('voucher_date', '<=', $endDate)
            ->where('jv_credit_member_head_id', $memberId)
            ->where('society_id', $societyId)
            ->where('financial_year_id', $fyId)
            ->where('member_transfer', $memberTransfer))
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
        return floatval(JournalNotes::excludeManual(DB::table('journal_vouchers')
            ->where('voucher_date', '>=', $fromDate)
            ->where('voucher_date', '<=', $toDate)
            ->where('jv_debit_member_head_id', $memberId)
            ->where('society_id', $societyId)
            ->where('member_transfer', $memberTransfer)
            ->where('financial_year_id', $fyId))
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
        // Each entry is a RUNNING balance and a payment taken from one entry is subtracted from every later
        // one below. Looping "as $data" iterated a snapshot taken before those subtractions, so a payment
        // bigger than the oldest unpaid entry was under-deducted and interest was charged on money that
        // had been paid. Read the entry fresh on every pass.
        foreach (array_keys($billData) as $index) {
            $data = $billData[$index];
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
                // The tax of a bill (tax_total) is charged ON TOP of the tariff amount: monthly_amount is the tariff total
                // WITHOUT tax and the bill's principal_balance is op_principal_arrears + monthly_principal_amount; the tax
                // has its own bucket below. Taking tax_total out of the principal as well charged interest on the
                // principal LESS the GST.
                $tempPrincipal = $monthlyPrincipalAmt + $jvDebitedAmt - $bd['principal_adjusted'];
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

                // The date the new bill is generated: the boundary the interest runs up to. ($billGeneratedDateAdj
                // below is the day before it, which is what the cycle based methods expect.)
                $newBillDate = $billGeneratedDate;

                // Due date protection: if the last bill's due date has not been crossed by the time the new bill is
                // generated (due date on or after the new bill's date) nothing is overdue, so no interest at all.
                if (($methodId == 1 || $methodId == 3) && $this->dueDateNotCrossed($billDueDate, $newBillDate)) {
                    return 0;
                }

                // Credit Notes inside the window settle principal like a payment does.
                if ($billType == 'reg') {
                    $memberPaymentData = $this->mergeCreditNotesIntoWindow($memberPaymentData, $lastBillGeneratedDate, $newBillDate, $memberId, $sid, $memberTransfer, $fyId);
                }

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
                            $paymentDate = $newBillDate; // open segment ends on the new bill's date (delayDays counts end - start)
                            $totalBal = $principalAmt + $interestAmtCalc + $taxAmount;
                            $this->setInterestData($principalAmt, $totalBal, $calculatedBillSummary);
                            $interestAmt += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj, $totalDays);
                        }
                        if ($methodId == 2 && $index == ($paymentCount - 1)) {
                            if (empty($byMonth)) { $billDueDate = $lastBillGeneratedDate; $byMonth = 1; }
                            $paymentDate = $newBillDate; // open segment ends on the new bill's date (delayDays counts end - start)
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
                        $paymentDate = $newBillDate; // open segment ends on the new bill's date (delayDays counts end - start)
                        $interestAmt += $this->delayDays($principalAmt, $totalBal, $params, $billDueDate, $paymentDate, $lastBillGeneratedDate, $billGeneratedDateAdj, $totalDays);
                    } elseif ($methodId == 2) {
                        $billDueDate = $lastBillGeneratedDate;
                        $paymentDate = $newBillDate; // open segment ends on the new bill's date (delayDays counts end - start)
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

        // The arrears being charged are those of the OPENING bill. What the callers pass in:
        //   $lastBillGeneratedDate = opening bill date        $paymentDate = opening bill DUE date
        //   $billGeneratedDate     = date of the bill being generated now (the first bill)
        // ($billDueDate is not used: callers pass the opening bill date there). The interest period is worked out
        // like a normal bill's: it ends the day BEFORE the new bill, and each method counts from where it starts:
        //   Delay Days (1)                        opening due date  ->  day before the new bill
        //   Delay Months (2), Cycle Days (3), Cycle Monthly (4)   opening bill date -> day before the new bill
        $valid = fn ($d) => !empty($d) && $d != '0000-00-00' && strtotime($d) !== false;
        if (!$valid($billGeneratedDate)) {
            return ($interestAmtResult < 0) ? 0 : $interestAmtResult;
        }
        $periodEnd = date('Y-m-d', strtotime('-1 day', strtotime($billGeneratedDate)));

        $days = 0;
        if ($methodId == 1) {
            // Only the days after the due date count; due on or after the new bill's date means not late yet.
            if ($valid($paymentDate) && strtotime($paymentDate) < strtotime($billGeneratedDate)) {
                $interestAmtResult += $this->delayDays($principalAmt, $totalBal, $params, $paymentDate, $billGeneratedDate, $lastBillGeneratedDate, $periodEnd, $days); // days = new bill date - opening due date
            }
        } elseif ($valid($lastBillGeneratedDate) && strtotime($lastBillGeneratedDate) < strtotime($billGeneratedDate)) {
            if ($methodId == 2) {
                $interestAmtResult += $this->delayMonths($principalAmt, $totalBal, $params, $lastBillGeneratedDate, $billGeneratedDate, $lastBillGeneratedDate, $periodEnd);
            } elseif ($methodId == 3) {
                $interestAmtResult += $this->completeCycleDays($principalAmt, $totalBal, $params, $paymentDate, $periodEnd, $lastBillGeneratedDate, $periodEnd);
            } elseif ($methodId == 4) {
                $interestAmtResult += $this->completeCycleMonthly($principalAmt, $totalBal, $params, $paymentDate, $periodEnd, $lastBillGeneratedDate, $periodEnd);
            }
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
