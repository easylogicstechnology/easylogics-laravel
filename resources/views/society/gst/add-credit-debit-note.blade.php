@extends('layouts.app')
@section('title', 'Credit / Debit Note')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">{{ empty($d['id']) ? 'Add' : 'Edit' }} Credit / Debit Note</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" id="noteForm" action="{{ route('society.gstAddCreditDebitNote') }}">
                @csrf
                @if(!empty($d['id']))<input type="hidden" name="id" value="{{ $d['id'] }}">@endif
                <div class="row">
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Note Type</label>
                            <select class="form-control" name="note_type">
                                <option value="Credit Note" {{ (!isset($d['note_type']) || $d['note_type'] == 'Credit Note') ? 'selected' : '' }}>Credit Note</option>
                                <option value="Debit Note" {{ (isset($d['note_type']) && $d['note_type'] == 'Debit Note') ? 'selected' : '' }}>Debit Note</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Note Date<span class="required">*</span></label>
                            <input type="date" class="form-control" name="note_date" value="{{ $d['note_date'] ?? date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Original Invoice Type</label>
                            <select class="form-control" name="original_invoice_type">
                                <option value="MemberBill" {{ (!isset($d['original_invoice_type']) || $d['original_invoice_type'] == 'MemberBill') ? 'selected' : '' }}>Member Bill</option>
                                <option value="VendorBill" {{ (isset($d['original_invoice_type']) && $d['original_invoice_type'] == 'VendorBill') ? 'selected' : '' }}>Vendor Bill</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Original Invoice No</label>
                            <input type="text" class="form-control" name="original_invoice_no" value="{{ $d['original_invoice_no'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Original Invoice ID (internal)<span class="required">*</span></label>
                            <input type="number" class="form-control" name="original_invoice_id" value="{{ $d['original_invoice_id'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Original Invoice Date</label>
                            <input type="date" class="form-control" name="original_invoice_date" value="{{ $d['original_invoice_date'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Party Type</label>
                            <select class="form-control" name="party_type">
                                <option value="Member" {{ (!isset($d['party_type']) || $d['party_type'] == 'Member') ? 'selected' : '' }}>Member</option>
                                <option value="Vendor" {{ (isset($d['party_type']) && $d['party_type'] == 'Vendor') ? 'selected' : '' }}>Vendor</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Member</label>
                            <select class="form-control" name="member_id">
                                <option value="">Select</option>
                                @foreach($memberList as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($d['member_id']) && $d['member_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Vendor</label>
                            <select class="form-control" name="vendor_detail_id">
                                <option value="">Select</option>
                                @foreach($vendorList as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($d['vendor_detail_id']) && $d['vendor_detail_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Party Name / GSTIN</label>
                            <div class="row">
                                <div class="col-xs-6" style="float:left;width:50%;padding:0 5px;"><input type="text" class="form-control" name="party_name" value="{{ $d['party_name'] ?? '' }}" placeholder="Party name"></div>
                                <div class="col-xs-6" style="float:left;width:50%;padding:0 5px;"><input type="text" class="form-control" name="gstin" value="{{ $d['gstin'] ?? '' }}" placeholder="GSTIN"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">HSN/SAC</label>
                            <select class="form-control" id="hsnSelect" name="hsn_sac_id">
                                <option value="">Select</option>
                                @foreach($hsnList as $id => $code)
                                    <option value="{{ $id }}" {{ (isset($d['hsn_sac_id']) && $d['hsn_sac_id'] == $id) ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Taxable Amount<span class="required">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="taxableAmount" name="taxable_amount" value="{{ $d['taxable_amount'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">CGST</label>
                            <input type="number" step="0.01" class="form-control" id="cgstAmount" name="cgst_amount" value="{{ $d['cgst_amount'] ?? '0' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">SGST</label>
                            <input type="number" step="0.01" class="form-control" id="sgstAmount" name="sgst_amount" value="{{ $d['sgst_amount'] ?? '0' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">IGST</label>
                            <input type="number" step="0.01" class="form-control" id="igstAmount" name="igst_amount" value="{{ $d['igst_amount'] ?? '0' }}">
                        </div>
                    </div>
                    <div class="col-md-6 padding-1">
                        <div class="form-group">
                            <label class="control-label">Reason</label>
                            <input type="text" class="form-control" name="reason" value="{{ $d['reason'] ?? '' }}" placeholder="e.g. Sales return, rate difference, deficiency in service">
                        </div>
                    </div>
                    <div class="col-md-6 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $d['remarks'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.gstCreditDebitNotes') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Convenience auto-fill only - the server always recomputes total_gst/total_amount
// from whatever CGST/SGST/IGST values are actually submitted, so this never needs
// to be authoritative.
var gstHsnRates = {!! json_encode($hsnRateMap) !!};
function recalcNoteGstFromHsn() {
    var hsnId = document.getElementById('hsnSelect').value;
    var taxable = parseFloat(document.getElementById('taxableAmount').value) || 0;
    if (!hsnId || !gstHsnRates[hsnId] || taxable <= 0) { return; }
    var rates = gstHsnRates[hsnId];
    document.getElementById('cgstAmount').value = (taxable * rates.cgst / 100).toFixed(2);
    document.getElementById('sgstAmount').value = (taxable * rates.sgst / 100).toFixed(2);
    document.getElementById('igstAmount').value = (taxable * rates.igst / 100).toFixed(2);
}
document.getElementById('hsnSelect').addEventListener('change', recalcNoteGstFromHsn);
document.getElementById('taxableAmount').addEventListener('blur', recalcNoteGstFromHsn);
</script>
@endsection
