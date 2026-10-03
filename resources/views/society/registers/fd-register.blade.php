@extends('layouts.app')
@section('title', 'FD Register')
@section('content')
@include('society.reports._styles')
@php
    $d = fn ($v) => !empty($v) ? date('d/m/Y', strtotime($v)) : '';
    $n = fn ($v) => !empty($v) ? number_format($v, 2) : '';
@endphp
<div class="page-header">
    <h2>FD Register</h2>
</div>

<div class="card">
    <div class="ar-actions">
        <a href="{{ route('society.addFdRegister') }}" class="btn btn-success">Add FD</a>
        <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_fd_register', 'landscape');">Print Friendly</a>
    </div>
</div>

<div class="card" style="overflow-x:auto;">
    <div id="print_fd_register">
        <div class="ar-report">
            @include('society.registers._head', ['heading' => 'FD REGISTER'])
            <table>
                <thead>
                    <tr>
                        <th style="width:4%">Action</th>
                        <th style="width:3%">Sr. No.</th>
                        <th>FD / Investment Date</th>
                        <th>Bank Name</th>
                        <th>Branch</th>
                        <th>FD No.</th>
                        <th>Investment Type</th>
                        <th>Principal Amount</th>
                        <th>Interest Rate %</th>
                        <th>Period</th>
                        <th>Maturity Date</th>
                        <th>Maturity Amount</th>
                        <th>Interest Amount</th>
                        <th>TDS</th>
                        <th>Renewal Date</th>
                        <th>Bank Account No.</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $i => $e)
                    <tr>
                        <td><a href="{{ route('society.addFdRegister', $e->id) }}" title="Edit">&#9998;</a></td>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $d($e->investment_date) }}</td>
                        <td>{{ $e->bank_name }}</td>
                        <td>{{ $e->branch }}</td>
                        <td>{{ $e->fd_no }}</td>
                        <td>{{ $e->fd_type }}</td>
                        <td class="text-right">{{ $n($e->principal_amount) }}</td>
                        <td class="text-right">{{ $e->interest_rate }}</td>
                        <td>{{ $e->period_tenure }}</td>
                        <td>{{ $d($e->maturity_date) }}</td>
                        <td class="text-right">{{ $n($e->maturity_amount) }}</td>
                        <td class="text-right">{{ $n($e->interest_earned) }}</td>
                        <td class="text-right">{{ $n($e->tds_deducted) }}</td>
                        <td>{{ $d($e->renewal_date) }}</td>
                        <td>{{ $e->bank_account_no }}</td>
                        <td>{{ $e->fd_status }}</td>
                        <td>{{ $e->remarks }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="18" class="text-center">No FD entries found</td>
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
