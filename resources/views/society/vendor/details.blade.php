@extends('layouts.app')

@section('title', 'Vendor Detail - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Vendor Detail</h2>
    <a class="btn btn-primary btn-sm" href="javascript:void(0);" onclick="openVendorDetailModal(0);">+ Add Vendor</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="datable_1">
            <thead>
                <tr>
                    <th>#</th><th>Vendor Name</th><th>Contact Person</th><th>Phone</th><th>PAN</th><th>GST No</th>
                    <th>Email</th><th>Cr/Dr</th><th>Status</th><th style="white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendorDetailsList as $i => $v)
                    @php $ledgerHeadId = $v->head_id ?? $v->ledger_head_id; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $v->head_title }}</td>
                        <td>{{ $v->contact_person_name }}</td>
                        <td>{{ $v->phone_number }}</td>
                        <td>{{ $v->pan_no }}</td>
                        <td>{{ $v->gst_no }}</td>
                        <td>{{ $v->company_email }}</td>
                        <td>{{ $v->cr_dr }}</td>
                        <td>
                            @if ($v->status == 1)
                                <span style="background:#27ae60;color:#fff;font-size:11px;padding:2px 8px;border-radius:3px;">Active</span>
                            @else
                                <span style="background:#999;color:#fff;font-size:11px;padding:2px 8px;border-radius:3px;">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="javascript:void(0);" title="Edit" onclick="openVendorDetailModal({{ (int) $ledgerHeadId }});"><i class="fa fa-pencil"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align:center;">No vendors added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('society.vendor._modals')
@endsection

@section('scripts')
@include('society.vendor._scripts')
@endsection
