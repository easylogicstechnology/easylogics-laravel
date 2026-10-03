@extends('layouts.app')
@section('title', $title ?? 'Journal Voucher Register')
@section('content')
@include('society.reports._styles')
<?php
    $safeNote = fn ($note) => str_replace('&lt;br /&gt;', '<br />', e(wordwrap((string) $note, 60, '<br />')));
    $filtered = isset($input['voucher_no']) && $input['voucher_no'] != '';
    $pad = $filtered ? ' style="padding-top:40px;padding-bottom:40px;"' : '';
?>
<div class="page-header">
    <h2>Journal Voucher Register</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.journalVoucherRegister') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>Type</label><select><option value="">Journal Voucher</option></select></div>
            <div class="ar-field"><label>Sub Type</label><select><option value="">General</option></select></div>
            <div class="ar-field"><label>Voucher Date</label><input type="date" value=""></div>
            <div class="ar-field"><label>V.No</label><input type="text" id="voucher_no" name="voucher_no" style="width:90px" value="{{ $input['voucher_no'] ?? '' }}"></div>
            <div class="ar-field"><label>Sr.</label><input type="text" style="width:60px" value=""></div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'society_journal_voucher', 'file' => 'JournalVoucherRegister', 'sheet' => 'Journal Voucher Register'])
                <a href="{{ route('society.reports.journalVoucherRegister') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="society_journal_voucher">
        <div class="print_journal_voucher ar-report">
            <div class="row">
                <h5>{{ \App\Support\ReportUtil::plain($society->society_name ?? '') }}</h5>
                <div class="report-address-heading">Registration No. {{ \App\Support\ReportUtil::plain($society->registration_no ?? '') }} Dated: {{ $society->registration_date ?? '' }}</div>
                <div class="report-address-heading">{{ \App\Support\ReportUtil::plain($society->address ?? '') }}</div>
            </div>
            <div class="report-bill">Journal Voucher Register</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:15%">Date</th>
                        <th>Particulars</th>
                        <th>Note</th>
                        <th style="width:8%">Dr.</th>
                        <th style="width:8%">Cr.</th>
                    </tr>
                </thead>
                <tbody>
                <?php $totalDebit = 0; $totalCredit = 0; ?>
                @foreach($data as $voucherNo => $details)
                    <?php $debits = $details['debit'] ?? null; $credits = $details['credit'] ?? null; ?>
                    <tr><td colspan="4" class="border-none">{{ date('d/m/Y', strtotime($details['date'])) }}</td></tr>
                    <tr><td colspan="5" class="border-none">Voucher No:&nbsp;&nbsp;{{ $voucherNo }}</td></tr>
                    @foreach($debits ?? [] as $d)
                        <?php
                            $totalDebit += $d['jv_amount_debited'];
                            if ($d['flag'] == 'ledger') {
                                $particular = $ledgers[$d['jv_debit_ledger_head_id']] ?? '';
                            } else {
                                $particular = isset($d['debit_member_title']) && $d['debit_member_title'] !== '' ? $d['debit_member_title'] : ($members[$d['jv_debit_member_head_id']] ?? '');
                            }
                        ?>
                        <tr>
                            <td></td>
                            <td{!! $pad !!}>{{ $particular }}</td>
                            <td>{!! $safeNote($d['note']) !!}</td>
                            <td class="text-right">{{ $d['jv_amount_debited'] }}</td>
                            <td class="text-right">0</td>
                        </tr>
                    @endforeach
                    @foreach($credits ?? [] as $c)
                        <?php
                            $totalCredit += $c['jv_amount_credited'];
                            if ($c['flag'] == 'ledger') {
                                $particular = $ledgers[$c['jv_credit_ledger_head_id']] ?? '';
                            } else {
                                $particular = isset($c['credit_member_title']) && $c['credit_member_title'] !== '' ? $c['credit_member_title'] : ($members[$c['jv_credit_member_head_id']] ?? '');
                            }
                        ?>
                        <tr>
                            <td></td>
                            <td{!! $pad !!}>{{ $particular }}</td>
                            <td>{!! $safeNote($c['note']) !!}</td>
                            <td class="text-right"></td>
                            <td class="text-right">{{ $c['jv_amount_credited'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="font-weight:bold;">Grand Total</td>
                        <td class="text-right" style="font-weight:bold;">{{ number_format($totalDebit) }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ number_format($totalCredit) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div style="display:flex;justify-content:space-around;margin-top:60px">
                <div>Cashier's Signature</div>
                <div>Authorised Signatory</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
