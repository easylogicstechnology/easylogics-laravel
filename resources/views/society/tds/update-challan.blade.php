@extends('layouts.app')
@section('title', 'Update Challan')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Update Challan #{{ $c['id'] }}</h6></div>
        <div class="panel-body">
            <div class="alert alert-warning">Enter the Challan Number, BSR Code, Challan Date and CIN only after actually paying this challan on the Income Tax e-filing / OLTAS portal. The application does not generate these values.</div>
            <p>TAN: <b>{{ $c['tan_no'] }}</b> &nbsp; FY: <b>{{ $c['financial_year_id'] }}</b> &nbsp; Month: <b>{{ date('F', mktime(0, 0, 0, $c['month'], 1)) }}</b> &nbsp; Total TDS: <b>{{ number_format($c['total_tds_amount'], 2) }}</b></p>
            <form method="post" action="{{ route('society.tdsUpdateChallan', $c['id']) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Challan Number</label>
                            <input type="text" class="form-control" name="challan_number" value="{{ $c['challan_number'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">BSR Code</label>
                            <input type="text" class="form-control" name="bsr_code" value="{{ $c['bsr_code'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Challan Date</label>
                            <input type="date" class="form-control" name="challan_date" value="{{ $c['challan_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">CIN</label>
                            <input type="text" class="form-control" name="cin" value="{{ $c['cin'] }}">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Status</label>
                            <select class="form-control" name="payment_status">
                                @foreach(['Pending', 'Challan Prepared', 'Payment Pending', 'Paid', 'Challan Verified', 'Filed'] as $st)
                                    <option value="{{ $st }}" {{ $c['payment_status'] == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $c['remarks'] }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.tdsChallans') }}" class="btn btn-default">Cancel</a>
            </form>

            <h6 style="margin-top:20px;">Included Transactions</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Deduction Date</th><th>Deductee</th><th>PAN</th><th>Section</th><th class="text-right">TDS Amount</th></tr></thead>
                    <tbody>
                    @foreach($challanTxns as $t)
                        <tr>
                            <td>{{ $t->deduction_date }}</td>
                            <td>{{ $t->contact_person_name }}</td>
                            <td>{{ $t->pan_no }}</td>
                            <td>{{ $t->section_code }}</td>
                            <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
