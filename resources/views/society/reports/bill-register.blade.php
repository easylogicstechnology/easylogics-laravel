@extends('layouts.app')
@section('title', $title ?? 'Bill Register')
@section('content')
@include('society.reports._styles')
<?php use App\Support\ReportUtil; ?>
<div class="page-header">
    <h2>Bill Register</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.billRegister') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>From Month</label>
                <select name="from_month">
                    <option value="">Select Month</option>
                    @foreach($billMonths as $no => $label)
                        <option value="{{ $no }}" {{ (string) ($input['from_month'] ?? '') === (string) $no ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>To Month</label>
                <select name="to_month">
                    <option value="">Select Month</option>
                    @foreach($billMonths as $no => $label)
                        <option value="{{ $no }}" {{ (string) ($input['to_month'] ?? '') === (string) $no ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Bill Type</label>
                <select name="bill_type">
                    <option value="reg" {{ ($input['bill_type'] ?? 'reg') === 'reg' ? 'selected' : '' }}>Regular</option>
                    <option value="sup" {{ ($input['bill_type'] ?? '') === 'sup' ? 'selected' : '' }}>Supplementary</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Member Record</label>
                <select name="member_record">
                    <option value="Current" {{ !isset($input['member_record']) || $input['member_record'] === 'Current' ? 'selected' : '' }}>Current Member</option>
                    <option value="Old" {{ ($input['member_record'] ?? '') === 'Old' ? 'selected' : '' }}>Old Member</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Member Record Range</label>
                <select name="member_record_range">
                    <option value="-1">Member Record Range</option>
                    @foreach([1 => '1 to 100', 2 => '101 to 200', 3 => '201 to 300', 4 => '301 to 400', 5 => '401 to 500', 6 => '501 to 600', 7 => '601 to 700'] as $n => $t)
                        <option value="{{ $n }}" {{ (string) ($input['member_record_range'] ?? '') === (string) $n ? 'selected' : '' }}>{{ $t }} Records</option>
                    @endforeach
                </select>
            </div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_bill_register', 'file' => 'BillRegister', 'sheet' => 'Bill_Register', 'orientation' => 'landscape'])
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_bill_register">
        <div class="print-bill-register ar-report" id="export_to_excel_bill_register">
            <div class="report-bill"></div>
            <table>
                <thead>
                    <tr><th colspan="10" style="background:#f5f5f5;text-align:left;"><div>Bill Register</div></th></tr>
                    <tr><th colspan="10" style="background:#f5f5f5;"><h5>{{ ReportUtil::plain($society->society_name ?? '') }}</h5></th></tr>
                    <tr><th colspan="10" style="background:#f5f5f5;">{{ ReportUtil::plain($society->registration_no ?? '') }}</th></tr>
                    <tr><th colspan="10" style="background:#f5f5f5;">{{ ReportUtil::plain($society->address ?? '') }}</th></tr>
                    <tr><th colspan="10" style="background:#f5f5f5;">{{ $input['bill_generated_date'] ?? '' }} To {{ $input['bill_generated_date_to'] ?? '' }}</th></tr>
                    <tr>
                        <th>#</th><th>Unit No</th><th>Member</th><th>For</th><th style="width:5%">Bill No</th><th>Bill Date</th>
                        @foreach($tariffs as $title)<th>{{ substr((string) $title, 0, 5) }}</th>@endforeach
                        <th>Amount</th><th>Int</th><th>Bill Amt</th><th>P_Arrear</th><th>I_Arrear</th><th>T_Arrear</th>
                        <th>Payable</th><th>Paid</th><th>Adjust</th><th>Discount</th><th>Bal.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $vertical = [];
                        $monthlyAmtTotal = $inteAmtTotal = $billAmtTotal = $prinArrTotal = $inteArrTotal = $taxArrTotal = $payAmtTotal = $paidAmtTotal = $adjuAmtTotal = $discountAmtTotal = $balanceAmtTotal = 0.00;
                        $sr = 1;
                    ?>
                    @foreach($data as $billId => $bill)
                        <?php
                            $s = $bill['MemberBillSummary'];
                            $monthlyAmtTotal += $s['monthly_amount'];
                            $inteAmtTotal += $s['interest_on_due_amount'];
                            $billAmtTotal += $s['monthly_bill_amount'];
                            $prinArrTotal += $s['op_principal_arrears'];
                            $inteArrTotal += $s['op_interest_arrears'];
                            $taxArrTotal += $s['op_tax_arrears'];
                            $payAmtTotal += $s['amount_payable'];
                            $lines = $bill['MemberBillGenerate'];
                            $paidAmtTotal += $bill['amountPaid'];
                            $rebate = ($s['interest_adjusted'] + $s['tax_adjusted'] + $s['principal_adjusted']);
                            $discount = $s['discount'];
                            $adjuAmtTotal += $rebate;
                            $discountAmtTotal += $discount;
                            $balanceAmtTotal += $s['amount_payable'] - $bill['amountPaid'] - ($rebate + $discount);
                            $m = $members[$s['member_id']] ?? [];
                        ?>
                        <tr>
                            <td>{{ $sr }}</td>
                            <td>{{ $m['flat_no'] ?? '' }}</td>
                            <td>{{ ($m['member_prefix'] ?? '') . ($m['member_name'] ?? '') }}</td>
                            <td>{{ $labelOf($s['month']) ?: '' }}</td>
                            <td>{{ $s['bill_no'] }}</td>
                            <td>{{ ReportUtil::formatDate($s['bill_generated_date'], 'd/m/Y') }}</td>
                            @foreach($tariffs as $ledgerId => $title)
                                <?php
                                    $amount = 0.00;
                                    if (isset($lines[$ledgerId])) {
                                        $amount = $lines[$ledgerId];
                                        $vertical[$ledgerId] = isset($vertical[$ledgerId]) ? $vertical[$ledgerId] + $amount : $amount;
                                    }
                                ?>
                                <td class="text-right">{{ $amount }}</td>
                            @endforeach
                            <td class="text-right">{{ $s['monthly_amount'] }}</td>
                            <td class="text-right">{{ $s['interest_on_due_amount'] }}</td>
                            <td class="text-right">{{ $s['monthly_bill_amount'] }}</td>
                            <td class="text-right">{{ $s['op_principal_arrears'] }}</td>
                            <td class="text-right">{{ $s['op_interest_arrears'] }}</td>
                            <td class="text-right">{{ $s['op_tax_arrears'] }}</td>
                            <td class="text-right">{{ $s['amount_payable'] }}</td>
                            <td class="text-right">{{ $bill['amountPaid'] }}</td>
                            <td class="text-right">{{ $rebate > 0 ? $rebate : '0.00' }}</td>
                            <td class="text-right">{{ $discount }}</td>
                            <td class="text-right">{{ $s['amount_payable'] - $bill['amountPaid'] - $rebate }}</td>
                        </tr>
                        <?php $sr++; ?>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right" style="font-weight:bold;">Grand Total:</td>
                        @foreach($vertical as $amt)<td class="text-right" style="font-weight:bold;">{{ number_format((float) $amt, 2, '.', '') }}</td>@endforeach
                        <td class="text-right" style="font-weight:bold;">{{ $monthlyAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $inteAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $billAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $prinArrTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $inteArrTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $taxArrTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $payAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $paidAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $adjuAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $discountAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $balanceAmtTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
