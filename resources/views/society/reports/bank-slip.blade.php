@extends('layouts.app')
@section('title', $title ?? 'Bank Slip')
@section('content')
@include('society.reports._styles')
<?php use App\Support\ReportUtil; ?>
<div class="page-header">
    <h2>Bank Slip</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.bankSlip') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Bank Name</label>
                <select name="society_bank_id" class="ar-wide" required>
                    <option value="">Select Bank</option>
                    @foreach($banks as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($input['society_bank_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field"><label>From Date</label><input type="date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}"></div>
            <div class="ar-field"><label>Slip No</label><input type="text" name="slip_no" style="width:100px" value="{{ $input['slip_no'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="text" name="slip_no_to" style="width:100px" value="{{ $input['slip_no_to'] ?? '' }}"></div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'society_bank_slip', 'file' => 'BankSlip', 'sheet' => 'Bank Slip'])
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="society_bank_slip">
        <div class="print-bank-slip ar-report">
            <div class="row">
                <h5>{{ ReportUtil::plain($society->society_name ?? '') }}</h5>
                <div class="report-address-heading">Registration No. {{ ReportUtil::plain($society->registration_no ?? '') }} Dated: {{ isset($society->registration_date) ? ReportUtil::formatDate($society->registration_date, 'd/m/Y') : '' }}</div>
                <div class="report-address-heading">{{ ReportUtil::plain($society->address ?? '') }}</div>
            </div>
            @if(!empty($input['society_bank_id']))
                @if(count($rows))
                    <div class="report-bill">Bank Slip</div>
                    <div style="display:flex;justify-content:space-between;margin:8px 0">
                        <div>
                            <div>Bank Name : {{ $banks[$input['society_bank_id']] ?? '' }}</div>
                            <div>Branch : {{ $bankRow->branch ?? '' }}</div>
                            <div>A/c No. : {{ $bankRow->account_no ?? '' }}</div>
                        </div>
                        <div>
                            <div>Slip No : {{ $input['slip_no'] ?? '' }}</div>
                            <div>Date : {{ date('d/m/Y') }}</div>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Sr.</th><th>Unit No</th><th>Voucher No</th><th>Cheque No.</th><th>Receive Date</th>
                                <th>Cheque Date</th><th>Bank Slip</th><th>Drawee Bank</th><th>Branch</th><th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $totBal = 0; ?>
                            @foreach($rows as $i => $r)
                                <?php $totBal += $r['amount_text']; ?>
                                <tr>
                                    <td style="text-align:center;">{{ $i + 1 }}</td>
                                    <td>{{ $r['flat_no'] }}</td>
                                    <td>{{ $r['receipt_id'] }}</td>
                                    <td>{{ $r['cheque_reference_number'] }}</td>
                                    <td>{{ isset($r['payment_date']) ? ReportUtil::formatDate($r['payment_date'], 'd/m/Y') : '' }}</td>
                                    <td>{{ isset($r['entry_date']) ? ReportUtil::formatDate($r['entry_date'], 'd/m/Y') : '' }}</td>
                                    <td>{{ $r['bank_slip_no'] }}</td>
                                    <td>{{ $r['bank_name'] }}</td>
                                    <td>{{ $r['member_bank_branch'] }}</td>
                                    <td class="text-right">{{ number_format($r['amount_text'], 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="6">{{ $totBal > 0 ? ReportUtil::amountInRupees(number_format($totBal, 2, '.', ',')) : '' }}</td>
                                <td class="text-center" colspan="3">Total (Rs.)</td>
                                <td class="text-right">{{ number_format($totBal, 2, '.', ',') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="display:flex;justify-content:space-around;margin-top:60px">
                        <div>Cashier's Signature</div>
                        <div>Authorised Signatory</div>
                    </div>
                @else
                    <div class="text-center"><br>No result found. </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
