@php
    // Options of the "Bill Particulars" select, reused for every added line (and by addVendorBillingLine() in JS).
    $billParticularOptionsHtml = '<option value="">Select</option>';
    foreach ($billParticularHeadLists as $ledgerHeadId => $ledgerHeadTitle) {
        $billParticularOptionsHtml .= '<option value="' . (int) $ledgerHeadId . '">' . e($ledgerHeadTitle) . '</option>';
    }
    $billParticularOptionsHtml .= '<option value="new">+ Add New Ledger Head</option>';

    $lineRow = function ($index, $line) use ($billParticularHeadLists) {
        $selectedId = $line['ledger_head_id'] ?? '';
        $options = '<option value="">Select</option>';
        foreach ($billParticularHeadLists as $ledgerHeadId => $ledgerHeadTitle) {
            $options .= '<option value="' . (int) $ledgerHeadId . '"' . ((string) $selectedId === (string) $ledgerHeadId ? ' selected' : '') . '>' . e($ledgerHeadTitle) . '</option>';
        }
        $options .= '<option value="new">+ Add New Ledger Head</option>';
        $val = fn ($k, $default = '') => e($line[$k] ?? $default);

        return '<tr class="vendor-billing-line">'
            . '<td style="min-width:180px;"><select class="form-control vendor-line-particular" name="VendorBillDetail[' . $index . '][ledger_head_id]" onchange="onBillParticularChange(this);">' . $options . '</select></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-amount" name="VendorBillDetail[' . $index . '][amount]" value="' . $val('amount') . '" placeholder="0.00"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-sgst-rate" name="VendorBillDetail[' . $index . '][sgst_rate]" value="' . $val('sgst_rate') . '" placeholder="%"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-sgst-amount" readonly value="' . $val('sgst_amount', '0.00') . '"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-cgst-rate" name="VendorBillDetail[' . $index . '][cgst_rate]" value="' . $val('cgst_rate') . '" placeholder="%"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-cgst-amount" readonly value="' . $val('cgst_amount', '0.00') . '"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-igst-rate" name="VendorBillDetail[' . $index . '][igst_rate]" value="' . $val('igst_rate') . '" placeholder="%"></td>'
            . '<td><input type="text" class="form-control text-right vendor-line-igst-amount" readonly value="' . $val('igst_amount', '0.00') . '"></td>'
            . '<td><input type="text" class="form-control vendor-line-hsn" name="VendorBillDetail[' . $index . '][hsn_sac]" value="' . $val('hsn_sac') . '"></td>'
            . '<td><button type="button" class="btn btn-danger btn-sm btn-remove-vendor-line">&times;</button></td>'
            . '</tr>';
    };
@endphp
<form method="post" id="vendorBillingForm" name="vendorBillingForm" autocomplete="off" onsubmit="return false;">
    <div id="vendor_billing_notify_error"></div>
    <input type="hidden" id="vendor_bill_id" name="VendorBill[id]" value="{{ $header['id'] ?? '' }}">

    <div class="vf-row">
        <div class="vf-col" style="flex:4;">
            <label>Select Vendor<span class="required">*</span></label>
            <select class="form-control" id="vendor_bill_vendor_id" name="VendorBill[vendor_ledger_head_id]" onchange="onBillingVendorChange(this.value);" required>
                <option value="">Select</option>
                @foreach ($vendorLists as $id => $title)
                    <option value="{{ $id }}" @selected(isset($header['vendor_ledger_head_id']) && $header['vendor_ledger_head_id'] == $id)>{{ $title }}</option>
                @endforeach
                <option value="new">+ Add New Vendor</option>
            </select>
        </div>
        <div class="vf-col" style="flex:1;">
            <label>Select BillType</label>
            <select class="form-control" name="VendorBill[bill_type]">
                @foreach (['Sales', 'Purchase', 'Debit Note', 'Credit Note'] as $billTypeOption)
                    <option value="{{ $billTypeOption }}" @selected(isset($header['bill_type']) ? $header['bill_type'] == $billTypeOption : $billTypeOption == 'Sales')>{{ $billTypeOption }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="vf-row">
        <div class="vf-col"><label>Bill No</label><input type="text" class="form-control" name="VendorBill[bill_no]" value="{{ $header['bill_no'] ?? '' }}"></div>
        <div class="vf-col"><label>Bill Date</label><input type="date" class="form-control" name="VendorBill[bill_date]" value="{{ $header['bill_date'] ?? '' }}"></div>
        <div class="vf-col"><label>Due Date</label><input type="date" class="form-control" name="VendorBill[due_date]" value="{{ $header['due_date'] ?? '' }}"></div>
        <div class="vf-col"><label>PO No</label><input type="text" class="form-control" name="VendorBill[po_no]" value="{{ $header['po_no'] ?? '' }}"></div>
    </div>
    <div class="vf-row">
        <div class="vf-col"><label>Title</label><input type="text" class="form-control" name="VendorBill[title]" value="{{ $header['title'] ?? '' }}"></div>
        <div class="vf-col"><label>Remarks</label><input type="text" class="form-control" name="VendorBill[remarks]" value="{{ $header['remarks'] ?? '' }}"></div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="vendor_billing_table">
            <thead>
                <tr>
                    <th>Bill Particulars</th><th>Amount</th><th>SGST %</th><th>SGST Amt</th><th>CGST %</th><th>CGST Amt</th>
                    <th>IGST %</th><th>IGST Amt</th><th>HSN/SAC</th><th>Action</th>
                </tr>
            </thead>
            <tbody id="vendor_billing_lines_container">
                @if (!empty($lines))
                    @foreach ($lines as $index => $line){!! $lineRow($index, $line) !!}@endforeach
                @else
                    {!! $lineRow(0, []) !!}
                @endif
            </tbody>
            <tfoot>
                <tr><td colspan="10"><button type="button" class="btn btn-success btn-sm" onclick="addVendorBillingLine();">+ Add Line</button></td></tr>
            </tfoot>
        </table>
    </div>

    <div class="vf-row">
        <div class="vf-col"><label>Total</label><input type="text" class="form-control text-right" id="vendor_bill_total" readonly value="0.00"></div>
        <div class="vf-col"><label>TDS %</label><input type="text" class="form-control text-right" id="vendor_bill_tds_percent" name="VendorBill[tds_percent]" value="{{ $header['tds_percent'] ?? '0' }}"></div>
        <div class="vf-col"><label>TDS</label><input type="text" class="form-control text-right" id="vendor_bill_tds_amount" readonly value="0.00"></div>
        <div class="vf-col"><label>Deduct</label><input type="text" class="form-control text-right" id="vendor_bill_deduct" name="VendorBill[deduct_amount]" value="{{ $header['deduct_amount'] ?? '0' }}"></div>
        <div class="vf-col"><label>Total Bill</label><input type="text" class="form-control text-right" id="vendor_bill_total_bill" readonly value="0.00"></div>
        <div class="vf-col"><label>Rounded Off</label><input type="text" class="form-control text-right" id="vendor_bill_round_off" readonly value="0.00"></div>
    </div>
</form>
<script>
    window.billParticularOptionsHtml = @json($billParticularOptionsHtml);
    if (typeof recalculateVendorBillingTotals === 'function') { recalculateVendorBillingTotals(); }
</script>
