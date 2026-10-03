@extends('layouts.app')
@section('title', $title ?? 'Dues From Member')
@section('content')
@include('society.reports._styles')
<?php
    $reportType = $input['report_type'] ?? '';
    $isDetail = $reportType === 'Detail';
    $showAdvanceColumn = in_array($reportType, ['Dues-Advance', 'Current Dues-Advance']);
    $colspan = $isDetail ? 6 : 4;
    $headCols = $isDetail ? 8 : 5;
    $society_name = \App\Support\ReportUtil::plain($society->society_name ?? '');
?>
<div class="page-header">
    <h2>Dues From Member</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.duesFromMembers') }}" class="ar-form" autocomplete="off">
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
                    @foreach(['Summary', 'Detail', 'Dues-Advance', 'Current Dues-Advance', 'With Transaction', 'Regular', 'Supplementary', 'Reminder Letter'] as $t)
                        <option value="{{ $t }}" {{ $reportType === $t ? 'selected' : '' }}>{{ $t }}</option>
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
            <div class="ar-field">
                <label>Old Record</label>
                <select name="member_record">
                    <option value="Current" {{ ($input['member_record'] ?? '') === 'Current' ? 'selected' : '' }}>Current Member</option>
                    <option value="Old" {{ ($input['member_record'] ?? '') === 'Old' ? 'selected' : '' }}>Old Member</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Clear Dues By</label>
                <input type="date" name="clear_dues_by_date" value="{{ $input['clear_dues_by_date'] ?? '' }}">
                <small class="ar-note">Used only for the Reminder Letter</small>
            </div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_dues_from_member');">Print Friendly</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.pdf('print_dues_from_member', 'DuesFromMember.pdf');">Export to PDF</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.excel('print_dues_from_member', 'Dues From member', 'dues_from_member.xls');">Export to Excel</a>
                <a href="{{ route('society.reports.duesFromMembers') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_dues_from_member">
        <div class="print-dues-from-member ar-report">
            @include('society.reports._society_head')
            <div class="report-bill"><h5>Dues From Member As {{ date('d/M/Y', strtotime((string) ($input['payment_date'] ?? ''))) }}</h5></div>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%;">Sr.</th>
                        <th style="width:5%;">Date</th>
                        <th style="width:5%;">Unit No.</th>
                        <th>Member Name</th>
                        @if($isDetail)
                            <th style="width:10%;">Principal</th>
                            <th style="width:10%;">Interest</th>
                            <th style="width:10%;">Tax</th>
                        @endif
                        <th style="width:10%;">Dues</th>
                        @if($showAdvanceColumn)
                            <th style="width:10%;">Advance</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="{{ $headCols }}" class="border-none">Building : {{ $society_name }}</td></tr>
                    <tr><td colspan="{{ $headCols }}" class="border-none"><span style="display: block;">Wing : </span></td></tr>
                    @if(count($summary) > 0)
                        <?php $idCount = 1; $duesTotal = 0; $advanceTotal = 0; $pTotal = $iTotal = $tTotal = 0; ?>
                        @foreach($summary as $info)
                            <?php
                                // Detail: CakePHP's Detail figures never reach the view, every row is null and skipped
                                $dueAmount = $isDetail ? null : $info['total_dues_amount'];
                                if (!$showAdvanceColumn && $dueAmount < 0) { continue; }
                                if ($dueAmount == 0) { continue; }
                                $advanceTotal += !empty($dueAmount) && $dueAmount < 0 ? abs($dueAmount) * 1 : 0.00;
                                $duesTotal += !empty($dueAmount) && $dueAmount > 0 ? $dueAmount * 1 : 0.00;
                            ?>
                            <tr>
                                <td>{{ $idCount }}</td>
                                <td>{{ $info['date'] }}</td>
                                <td>{{ $info['flat_no'] }}</td>
                                <td>{{ $info['member_prefix'] . $info['member_name'] }} </td>
                                <td class="text-right">{{ $dueAmount > 0 ? number_format($dueAmount, 2, '.', ',') : '' }}</td>
                                @if($showAdvanceColumn)
                                    <td class="text-right">{{ $dueAmount < 0 ? number_format(abs($dueAmount), 2, '.', ',') : '' }}</td>
                                @endif
                            </tr>
                            <?php $idCount++; ?>
                        @endforeach
                        @if($isDetail)
                            <tr>
                                <td class="text-right" colspan="3">Total</td>
                                <td class="text-right">{{ number_format(abs($pTotal), 2, '.', ',') . ($pTotal >= 0 ? ' Dr' : ' Cr') }}</td>
                                <td class="text-right">{{ number_format(abs($iTotal), 2, '.', ',') . ($iTotal >= 0 ? ' Dr' : ' Cr') }}</td>
                                <td class="text-right">{{ number_format(abs($tTotal), 2, '.', ',') . ($tTotal >= 0 ? ' Dr' : ' Cr') }}</td>
                                <td class="text-right">{{ number_format($duesTotal, 2, '.', ',') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-right" colspan="{{ $colspan }}">Total</td>
                                <td class="text-right">{{ number_format($duesTotal, 2, '.', ',') }}</td>
                                @if($showAdvanceColumn)<td class="text-right">{{ number_format($advanceTotal, 2, '.', ',') }}</td>@endif
                            </tr>
                        @endif
                        <tr>
                            <td class="text-right" colspan="{{ $colspan }}">{{ $society_name }} Total</td>
                            <td class="text-right">{{ number_format($duesTotal, 2, '.', ',') }}</td>
                            @if($showAdvanceColumn)<td class="text-right">{{ number_format($advanceTotal, 2, '.', ',') }}</td>@endif
                        </tr>
                        <tr>
                            <td class="text-right" colspan="{{ $colspan }}">Grand Total</td>
                            <td class="text-right">{{ number_format($duesTotal, 2, '.', ',') }}</td>
                            @if($showAdvanceColumn)<td class="text-right">{{ number_format($advanceTotal, 2, '.', ',') }}</td>@endif
                        </tr>
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
