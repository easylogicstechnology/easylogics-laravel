@extends('layouts.app')
@section('title', 'TDS Transaction')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">{{ empty($d['id']) ? 'Add' : 'Edit' }} TDS Transaction</h6></div>
        <div class="panel-body">
            <form method="post" id="tdsTransactionForm" autocomplete="off" action="{{ route('society.tdsAddTransaction') }}">
                @csrf
                @if(!empty($d['id']))<input type="hidden" name="id" value="{{ $d['id'] }}">@endif
                @if(!empty($d['vendor_bill_id']))<input type="hidden" name="vendor_bill_id" value="{{ $d['vendor_bill_id'] }}">@endif
                <div class="row">
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Deductee (Vendor)<span class="required">*</span></label>
                            <select class="form-control" id="vendor_detail_id" name="vendor_detail_id" required>
                                <option value="">Select Deductee</option>
                                @foreach($vendorList as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($d['vendor_detail_id']) && $d['vendor_detail_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">TDS Section<span class="required">*</span></label>
                            <select class="form-control" id="tds_section_id" name="tds_section_id" required>
                                <option value="">Select Section</option>
                                @foreach($sectionsList as $id => $code)
                                    <option value="{{ $id }}" {{ (isset($d['tds_section_id']) && $d['tds_section_id'] == $id) ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Deduction Date<span class="required">*</span></label>
                            <input type="date" class="form-control" id="deduction_date" name="deduction_date" value="{{ $d['deduction_date'] ?? date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ $d['payment_date'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Invoice / Bill No</label>
                            <input type="text" class="form-control" name="invoice_no" value="{{ $d['invoice_no'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Gross Amount<span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="gross_amount" name="gross_amount" value="{{ $d['gross_amount'] ?? '' }}" required>
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
                <div class="row" id="calcPreview" style="display:none;">
                    <div class="col-sm-12">
                        <div class="alert alert-info">
                            Applicable Rate: <b id="calcRate">-</b>% &nbsp;|&nbsp;
                            TDS Amount: <b id="calcTds">-</b> &nbsp;|&nbsp;
                            Net Amount: <b id="calcNet">-</b> &nbsp;|&nbsp;
                            Threshold Met: <b id="calcThreshold">-</b>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save TDS Transaction</button>
                <a href="{{ route('society.tdsTransactions') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function tdsRecalculate() {
    var vendorId = document.getElementById('vendor_detail_id').value;
    var sectionId = document.getElementById('tds_section_id').value;
    var gross = document.getElementById('gross_amount').value;
    var ddate = document.getElementById('deduction_date').value;
    var excludeId = '{{ $d['id'] ?? '' }}';
    if (!vendorId || !sectionId || !gross || !ddate) { return; }
    var qs = new URLSearchParams({vendor_detail_id: vendorId, tds_section_id: sectionId, gross_amount: gross, deduction_date: ddate, exclude_txn_id: excludeId});
    fetch('{{ route('society.tdsCalculateAjax') }}?' + qs.toString(), {headers: {'Accept': 'application/json'}})
    .then(function (r) { return r.json(); })
    .then(function (resp) {
        if (resp.ok) {
            document.getElementById('calcRate').textContent = resp.rate_percent;
            document.getElementById('calcTds').textContent = parseFloat(resp.tds_amount).toFixed(2);
            document.getElementById('calcNet').textContent = parseFloat(resp.net_amount).toFixed(2);
            document.getElementById('calcThreshold').textContent = resp.threshold_met ? 'Yes' : 'No (aggregate: ' + parseFloat(resp.aggregate_so_far).toFixed(2) + ')';
            document.getElementById('calcPreview').style.display = 'block';
        } else {
            document.getElementById('calcPreview').style.display = 'none';
        }
    });
}
['vendor_detail_id', 'tds_section_id', 'deduction_date'].forEach(function (id) { document.getElementById(id).addEventListener('change', tdsRecalculate); });
document.getElementById('gross_amount').addEventListener('keyup', tdsRecalculate);
document.addEventListener('DOMContentLoaded', tdsRecalculate);
</script>
@endsection
