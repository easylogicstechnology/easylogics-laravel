@extends('layouts.app')
@section('title', 'TDS Sections')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">TDS Sections</h6></div>
            <div class="pull-right"><a href="{{ route('society.tdsAddSection') }}" class="btn btn-primary">+ Add TDS Section</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table id="datable_1" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>#</th><th>Section</th><th>Nature of Payment</th><th>Deductee Type</th><th>Resident Type</th>
                        <th>Rate %</th><th>Threshold</th><th>Effective From</th><th>Effective To</th><th>TDS Payable Ledger</th><th>Status</th><th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sectionsList as $i => $s)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $s->section_code }}</td>
                            <td>{{ $s->nature_of_payment }}</td>
                            <td>{{ $s->deductee_type }}</td>
                            <td>{{ $s->resident_type }}</td>
                            <td class="text-right">{{ number_format($s->rate_percent, 2) }}</td>
                            <td class="text-right">{{ number_format($s->threshold_limit, 2) }} ({{ $s->threshold_basis }})</td>
                            <td>{{ $s->effective_from }}</td>
                            <td>{{ $s->effective_to }}</td>
                            <td>{{ $s->ledger_title }}</td>
                            <td>
                                @if($s->status == 1)
                                    <span class="label label-success" style="cursor:pointer;" onclick="toggleSectionStatus({{ $s->id }})">Active</span>
                                @else
                                    <span class="label label-default" style="cursor:pointer;" onclick="toggleSectionStatus({{ $s->id }})">Inactive</span>
                                @endif
                            </td>
                            <td><a href="{{ route('society.tdsAddSection', $s->id) }}" title="Edit">&#9998;</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleSectionStatus(id) {
    fetch('{{ url('/society/tds/toggle-section-status') }}/' + id, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    })
    .then(function (r) { return r.json(); })
    .then(function () { location.reload(); })
    .catch(function () { alert('Could not update status.'); });
}
</script>
@endsection
