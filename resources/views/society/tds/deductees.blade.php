@extends('layouts.app')
@section('title', 'TDS Deductees')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">TDS Deductees (Vendor Master)</h6></div>
        <div class="panel-body">
            <div class="table-responsive">
                <table id="datable_1" class="table table-striped table-bordered">
                    <thead>
                    <tr><th>#</th><th>Vendor / Deductee</th><th>PAN</th><th>Deductee Type</th><th>Resident Status</th><th>Lower Deduction Cert</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($vendors as $i => $vd)
                        @php $extra = $tdsDeducteeMap[$vd->id] ?? []; @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $vd->contact_person_name }}</td>
                            <td>{{ $vd->pan_no }}</td>
                            <td>{{ $extra['deductee_type'] ?? '-' }}</td>
                            <td>{{ $extra['resident_status'] ?? '-' }}</td>
                            <td>{{ $extra['lower_deduction_cert_no'] ?? '-' }}</td>
                            <td><a href="{{ route('society.tdsAddDeductee', $vd->id) }}" class="btn btn-xs btn-info">&#9998; Configure TDS</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
