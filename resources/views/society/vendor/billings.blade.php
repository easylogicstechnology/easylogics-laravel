@extends('layouts.app')

@section('title', 'Vendor Billing - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Vendor Billing</h2>
    <a class="btn btn-primary btn-sm" href="javascript:void(0);" onclick="openVendorBillingModal(0);">+ Add Bill</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="datable_1">
            <thead>
                <tr>
                    <th>#</th><th>Bill No</th><th>Bill Date</th><th>Vendor</th><th>Bill Type</th>
                    <th style="text-align:right;">Total Amount</th><th style="text-align:right;">TDS Amount</th>
                    <th style="text-align:right;">Total Bill Amount</th><th>Status</th><th style="white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendorBillsList as $i => $b)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $b->bill_no }}</td>
                        <td>{{ $b->bill_date }}</td>
                        <td>{{ $b->vendor_title }}</td>
                        <td>{{ $b->bill_type }}</td>
                        <td style="text-align:right;">{{ number_format($b->total_amount, 2) }}</td>
                        <td style="text-align:right;">{{ number_format($b->tds_amount, 2) }}</td>
                        <td style="text-align:right;">{{ number_format($b->total_bill_amount, 2) }}</td>
                        <td>
                            @if ($b->status == 1)
                                <span style="background:#27ae60;color:#fff;font-size:11px;padding:2px 8px;border-radius:3px;">Active</span>
                            @else
                                <span style="background:#999;color:#fff;font-size:11px;padding:2px 8px;border-radius:3px;">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="javascript:void(0);" title="Edit" onclick="openVendorBillingModal({{ (int) $b->id }});"><i class="fa fa-pencil"></i></a>
                            <a href="javascript:void(0);" title="Print" onclick="printVendorBill({{ (int) $b->id }});" style="margin-left:8px;"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align:center;">No vendor bills yet.</td></tr>
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
