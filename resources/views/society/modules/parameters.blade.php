@extends('layouts.app')
@section('title', 'Society Parameters')
@section('content')
<div class="page-header">
    <h2>Society Parameters</h2>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.parameters') }}" enctype="multipart/form-data" id="societyParameters">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Billing Frequency</label>
                <select name="billing_frequency_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($billingFrequencies as $bf)
                        <option value="{{ $bf->id }}" {{ ($params->billing_frequency_id ?? '') == $bf->id ? 'selected' : '' }}>{{ $bf->frequency_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Interest Type</label>
                <select name="interest_type_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($interestTypes as $it)
                        <option value="{{ $it->id }}" {{ ($params->interest_type_id ?? '') == $it->id ? 'selected' : '' }}>{{ $it->interest_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Interest Rate (% Yearly)</label>
                <input type="number" step="0.01" name="interest_rate" class="form-control" value="{{ old('interest_rate', $params->interest_rate ?? '') }}">
            </div>
            <div class="form-group">
                <label>Interest Method</label>
                <select name="method_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($interestMethods as $im)
                        <option value="{{ $im->id }}" {{ ($params->method_id ?? '') == $im->id ? 'selected' : '' }}>{{ $im->method_title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Tariff Type</label>
                <select name="tariff_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($tariffTypes as $tt)
                        <option value="{{ $tt->id }}" {{ ($params->tariff_id ?? '') == $tt->id ? 'selected' : '' }}>{{ $tt->tariff_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Penality</label>
                <input type="text" name="penality" class="form-control" value="{{ old('penality') }}">
            </div>
            <div class="form-group">
                <label>Both Interest And Penality</label>
                <input type="text" name="interest_plus_penality" class="form-control" value="{{ old('interest_plus_penality') }}">
            </div>
            <div class="form-group">
                <label>GST Limit</label>
                <input type="number" step="0.01" name="gst_limit" class="form-control" value="{{ old('gst_limit', $params->gst_limit ?? '') }}">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Special Field</label>
                <textarea name="special_field" class="form-control" rows="4">{{ old('special_field', $params->special_field ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>Signature Image</label>
                @if(!empty($params->signature_image_path))
                    <div><img src="{{ asset($params->signature_image_path) }}" style="height:70px; display:block; margin-bottom:6px;"></div>
                @endif
                <input type="file" name="signature_image" class="form-control" accept=".jpg,.jpeg,.png">
            </div>

            <div class="form-group">
                <label>IGST(%)</label>
                <input type="number" step="0.01" name="igst_tax_per" class="form-control" value="{{ old('igst_tax_per', $params->igst_tax_per ?? '') }}">
            </div>
            <div class="form-group">
                <label>SGST(%)</label>
                <input type="number" step="0.01" name="sgst_tax_per" class="form-control" value="{{ old('sgst_tax_per', $params->sgst_tax_per ?? '') }}">
            </div>
            <div class="form-group">
                <label>CGST(%)</label>
                <input type="number" step="0.01" name="cgst_tax_per" class="form-control" value="{{ old('cgst_tax_per', $params->cgst_tax_per ?? '') }}">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Bill Notes</label>
                <textarea name="bill_note" class="form-control" rows="4">{{ old('bill_note', $params->bill_note ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>Is Tariff Monthly</label>
                <select name="is_tariff_mothly" class="form-control">
                    <option value="0" {{ ($params->is_tariff_mothly ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->is_tariff_mothly ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Settlement</label>
                <select name="settlement" class="form-control">
                    <option value="1" {{ ($params->settlement ?? 1) == 1 ? 'selected' : '' }}>FIFO</option>
                    <option value="2" {{ ($params->settlement ?? 1) == 2 ? 'selected' : '' }}>LIFO</option>
                </select>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Current Bill Update</label>
                @php $curBillUpdateVal = old('current_bill_update_enabled', $params->current_bill_update_enabled ?? null); @endphp
                <select name="current_bill_update_enabled" class="form-control" {{ $hasCurrentBillUpdateColumn ? '' : 'disabled' }}>
                    <option value="" {{ ($curBillUpdateVal === null || $curBillUpdateVal === '') ? 'selected' : '' }}>-- Not set (old Update works as before) --</option>
                    <option value="0" {{ (string) $curBillUpdateVal === '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ (string) $curBillUpdateVal === '1' ? 'selected' : '' }}>Yes</option>
                </select>
                @if(!$hasCurrentBillUpdateColumn)
                    <div class="text-danger" style="font-size:12px; margin-top:4px;"><b>Not available yet:</b> this database has not been updated for Current Bill Update, so this choice cannot be saved. Please contact support.</div>
                @endif
                <div class="text-muted" style="font-size:12px; margin-top:4px;">Not set (default): nothing changes - the old all-bills "Update" button keeps working as it always has. Yes: old "Update" is hidden; "Current Bill Update" becomes available, only on the current/latest bill - recalculates that bill alone and never touches previous bills. No: no manual bill-update button is available at all.</div>
            </div>

            <div style="grid-column: span 2;">
                <div class="card" style="margin-bottom:0;">
                    <h3 style="margin-bottom:12px;">Set Settlement Order</h3>
                    <table class="table" style="max-width:400px;">
                        <thead><tr><th>Component</th><th style="width:100px;">Order</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Principal</td>
                                <td><input type="number" class="form-control" id="order_principal" min="1" max="3" value="{{ $orderMap['Principle'] ?? 3 }}"></td>
                            </tr>
                            <tr>
                                <td>Interest</td>
                                <td><input type="number" class="form-control" id="order_interest" min="1" max="3" value="{{ $orderMap['Interest'] ?? 2 }}"></td>
                            </tr>
                            <tr>
                                <td>Tax</td>
                                <td><input type="number" class="form-control" id="order_tax" min="1" max="3" value="{{ $orderMap['Tax'] ?? 1 }}"></td>
                            </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="sorted_order" id="sorted_order">
                </div>
            </div>

            <div class="form-group">
                <label>Show All Tariff In Bill</label>
                <select name="show_all_tariff_name" class="form-control">
                    <option value="0" {{ ($params->show_all_tariff_name ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->show_all_tariff_name ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Show Bill Numbers In Receipt</label>
                <select name="show_bills_in_receipt" class="form-control">
                    <option value="0" {{ ($params->show_bills_in_receipt ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->show_bills_in_receipt ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Apply Gst On Current Interest</label>
                <select name="gst_interest" class="form-control">
                    <option value="N" {{ ($params->gst_interest ?? 'N') == 'N' ? 'selected' : '' }}>No</option>
                    <option value="Y" {{ ($params->gst_interest ?? 'N') == 'Y' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Apply Gst On Interest Arrears</label>
                <select name="gst_interest_arreas" class="form-control">
                    <option value="N" {{ ($params->gst_interest_arreas ?? 'N') == 'N' ? 'selected' : '' }}>No</option>
                    <option value="Y" {{ ($params->gst_interest_arreas ?? 'N') == 'Y' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Scanner Image</label>
                @if(!empty($params->scanner_image_path))
                    <div><img src="{{ asset($params->scanner_image_path) }}" style="height:100px; width:100px; object-fit:contain; display:block; margin-bottom:6px;"></div>
                @endif
                <input type="file" name="scanner_image" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button type="button" class="btn btn-primary" onclick="submitSocietyParameter()">Add/Update Parameters</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function updateSettlementOrder() {
    var items = [
        {name: 'Principle', order: parseInt(document.getElementById('order_principal').value) || 0},
        {name: 'Interest', order: parseInt(document.getElementById('order_interest').value) || 0},
        {name: 'Tax', order: parseInt(document.getElementById('order_tax').value) || 0}
    ];
    items.sort(function(a, b) { return a.order - b.order; });
    document.getElementById('sorted_order').value = items.map(function(i) { return i.name; }).join(',');
}

function submitSocietyParameter() {
    var vals = [
        parseInt(document.getElementById('order_principal').value) || 0,
        parseInt(document.getElementById('order_interest').value) || 0,
        parseInt(document.getElementById('order_tax').value) || 0
    ];
    var sorted = vals.slice().sort();
    if (sorted[0] !== 1 || sorted[1] !== 2 || sorted[2] !== 3) {
        alert('Settlement order must use unique values 1, 2, and 3');
        return false;
    }
    updateSettlementOrder();
    document.getElementById('societyParameters').submit();
}
</script>
@endsection
