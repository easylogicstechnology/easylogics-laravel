@extends('layouts.app')
@section('title', $title ?? 'Dues-Advance From Members')
@section('content')
@include('society.reports._styles')
<?php $society_name = \App\Support\ReportUtil::plain($society->society_name ?? ''); ?>
<div class="page-header">
    <h2>Dues-Advance From Members</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.duesAdvanceFromMembers') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>As on Date</label>
                <input type="date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}">
            </div>
            <div class="ar-field">
                <label>Type</label>
                <select name="report_type">
                    <option value="">Select</option>
                    @foreach(['Summary', 'Detail', 'Dues-Advance', 'With Transaction', 'Regular', 'Supplementary'] as $t)
                        <option value="{{ $t }}" {{ ($input['report_type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Operator</label>
                <select name="operator">
                    <option value="">Select Operator</option>
                    @foreach(['>', '<', '>=', '<=', '=', '<>'] as $op)
                        <option value="{{ $op }}" {{ ($input['operator'] ?? '') === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Amount</label>
                <input type="text" name="amount" class="text-right" style="width:110px" value="{{ $input['amount'] ?? '' }}">
            </div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_dues_advance_from_member', 'file' => 'DuesAdvanceFromMember', 'sheet' => 'Dues Advance'])
                <a href="{{ route('society.reports.duesAdvanceFromMembers') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_dues_advance_from_member">
        <div class="print-dues-advance-from-member ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">Dues-Advance From Member</div>
            <div class="report-bill">As On Date {{ !empty($input['payment_date']) ? date('d/m/Y', strtotime($input['payment_date'])) : '' }}</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%;">Sr.</th>
                        <th style="width:5%;">Unit No.</th>
                        <th>Member Name</th>
                        <th style="width:10%;">Advance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="border-none">Building : {{ $society_name }}</td></tr>
                    <tr><td colspan="5" class="border-none"><span style="display: block;">Wing : </span></td></tr>
                    @if(count($summary) > 0)
                        <?php $idCount = 1; $total = 0; ?>
                        @foreach($summary as $info)
                            <?php
                                if ($info['total_dues_amount'] == 0) { continue; }
                                if (round(abs($info['total_dues_amount'])) <= 1) { continue; }
                                $total += !empty($info['total_dues_amount']) ? $info['total_dues_amount'] : 0.00;
                            ?>
                            <tr>
                                <td>{{ $idCount }}</td>
                                <td>{{ $info['flat_no'] }}</td>
                                <td>{{ $info['member_prefix'] . $info['member_name'] }} </td>
                                <td class="text-right">{{ round(abs($info['total_dues_amount'])) . ' Cr' }}</td>
                            </tr>
                            <?php $idCount++; ?>
                        @endforeach
                        <?php $total = abs($total); ?>
                        <tr><td class="text-right" colspan="3">Total</td><td class="text-right">{{ number_format($total, 2, '.', ',') . ' Cr' }}</td></tr>
                        <tr><td class="text-right" colspan="3">{{ $society_name }} Total</td><td class="text-right">{{ number_format($total, 2, '.', ',') . ' Cr' }}</td></tr>
                        <tr><td class="text-right" colspan="3">Grand Total</td><td class="text-right">{{ number_format($total, 2, '.', ',') . ' Cr' }}</td></tr>
                    @else
                        <tr><td colspan="11"><center>Record not found.</center></td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
