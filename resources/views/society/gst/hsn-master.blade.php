@extends('layouts.app')
@section('title', 'HSN/SAC Master')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">HSN/SAC + Rate Master</h6></div>
            <div class="pull-right"><a href="{{ route('society.gstAddHsnMaster') }}" class="btn btn-primary">+ Add HSN/SAC</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table id="datable_1" class="table table-striped table-bordered">
                    <thead>
                    <tr><th>#</th><th>Code</th><th>Type</th><th>Description</th><th>GST %</th><th>CGST %</th><th>SGST %</th><th>IGST %</th><th>Cess %</th><th>Taxability</th><th>RCM</th><th>Effective From</th><th>Effective To</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($hsnList as $i => $h)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $h->code }}</td>
                            <td>{{ $h->code_type }}</td>
                            <td>{{ $h->description }}</td>
                            <td class="text-right">{{ number_format($h->gst_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($h->cgst_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($h->sgst_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($h->igst_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($h->cess_rate, 2) }}</td>
                            <td>{{ $h->taxability_type }}</td>
                            <td>{{ $h->reverse_charge_applicable ? 'Yes' : 'No' }}</td>
                            <td>{{ $h->effective_from }}</td>
                            <td>{{ $h->effective_to }}</td>
                            <td>
                                @if($h->status == 1)
                                    <span class="label label-success" style="cursor:pointer;" onclick="toggleHsnStatus({{ $h->id }})">Active</span>
                                @else
                                    <span class="label label-default" style="cursor:pointer;" onclick="toggleHsnStatus({{ $h->id }})">Inactive</span>
                                @endif
                            </td>
                            <td><a href="{{ route('society.gstAddHsnMaster', $h->id) }}" title="Edit">&#9998;</a></td>
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
function toggleHsnStatus(id) {
    fetch('{{ url('/society/gst/toggle-hsn-status') }}/' + id, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    })
    .then(function (r) { return r.json(); })
    .then(function () { location.reload(); })
    .catch(function () { alert('Could not update status.'); });
}
</script>
@endsection
