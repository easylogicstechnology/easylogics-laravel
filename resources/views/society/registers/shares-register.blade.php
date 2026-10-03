@extends('layouts.app')
@section('title', 'Shares Register')
@section('content')
@include('society.reports._styles')
@php
    $d = fn ($v) => !empty($v) ? date('d/m/Y', strtotime($v)) : '';
@endphp
<div class="page-header">
    <h2>Shares Register</h2>
</div>

<div class="card">
    <div class="ar-actions">
        <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_shares_register');">Print Friendly</a>
    </div>
</div>

<div class="card" style="overflow-x:auto;">
    <div id="print_shares_register">
        <div class="ar-report">
            @include('society.registers._head', ['heading' => 'SHARE REGISTER'])
            <table>
                <thead>
                    <tr>
                        <th style="width:6%">Mem Reg. No.</th>
                        <th>Member Name</th>
                        <th style="width:8%">Approval Date</th>
                        <th style="width:7%">Certificate No.</th>
                        <th style="width:8%">From - To</th>
                        <th style="width:6%">No. of Shares</th>
                        <th style="width:6%">Amount</th>
                        <th style="width:8%">Transfer Date</th>
                        <th>Transferee Name</th>
                        <th style="width:6%">New Reg. No.</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->member_id }}</td>
                        <td>{{ $memberNames[$e->member_id] ?? $e->full_name }}</td>
                        <td>{{ $d($e->date_of_allotment) }}</td>
                        <td>{{ $e->certification_no }}</td>
                        <td>{{ $e->from_share_no }} - {{ $e->to_share_no }}</td>
                        <td>{{ $e->no_of_shares }}</td>
                        <td>{{ $e->shares_value }}</td>
                        <td>{{ $d($e->disposed_on) }}</td>
                        <td>{{ $e->second_member }}</td>
                        <td></td>
                        <td>{{ $e->remarks }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No share entries found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/report_print.js') }}"></script>
@endsection
