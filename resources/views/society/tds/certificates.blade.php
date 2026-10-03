@extends('layouts.app')
@section('title', 'TDS Certificates')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">TDS Certificates</h6></div>
        <div class="panel-body">
            <form method="post" action="{{ route('society.tdsGenerateCertificate') }}" class="row" style="margin-bottom:15px;">
                @csrf
                <div class="col-md-4">
                    <select name="vendor_detail_id" class="form-control" required>
                        <option value="">Select Deductee</option>
                        @foreach($vendorList as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="financial_year_id" class="form-control" required>
                        @foreach($financialYearsList as $id => $y)
                            <option value="{{ $id }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary">Generate Certificate (PDF)</button></div>
            </form>
            <div class="clearfix"></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>#</th><th>Deductee</th><th>Financial Year</th><th class="text-right">Total TDS</th><th>Generated At</th></tr></thead>
                    <tbody>
                    @foreach($certList as $i => $c)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $c->contact_person_name }}</td>
                            <td>{{ $c->financial_year }}</td>
                            <td class="text-right">{{ number_format($c->total_tds_amount, 2) }}</td>
                            <td>{{ $c->generated_at }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
