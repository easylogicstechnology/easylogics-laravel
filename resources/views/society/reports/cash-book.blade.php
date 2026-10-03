@extends('layouts.app')
@section('title', $title ?? 'Cash Book')
@section('content')
<?php
    // Particulars carry a <br> the report adds itself; everything else is user data and is escaped.
    $safe = fn ($text) => str_replace('&lt;br&gt;', '<br>', e((string) $text));
    $selectedHead = $input['cash_ledger_head_id'] ?? '';
    $reportType = $input['report_type'] ?? '';
    $operator = $input['operator'] ?? '';
?>
<style>
    .cb-form .cb-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 12px; }
    .cb-form .cb-field { display: flex; flex-direction: column; gap: 4px; }
    .cb-form label { font-size: 12px; color: #555; font-weight: 600; }
    .cb-form select, .cb-form input { padding: 7px 8px; border: 1px solid #ccd; border-radius: 4px; font-size: 13px; }
    .cb-form .cb-head { min-width: 260px; }
    .cb-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .cash-book-report { margin-top: 18px; }
    .cash-book-report h5 { text-align: center; font-size: 16px; margin: 0 0 4px; }
    .cash-book-report .report-address-heading { text-align: center; font-size: 12px; color: #444; }
    .cash-book-report .report-bill { text-align: center; font-weight: 600; margin-top: 10px; }
    .cash-book-report table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
    .cash-book-report th, .cash-book-report td { border: 1px solid #999; padding: 3px 6px; vertical-align: top; }
    .cash-book-report th { background: #f0f0f0; text-align: center; }
    .cash-book-report .text-right { text-align: right; }
    .cash-book-report .text-center { text-align: center; }
    .cash-book-report .cb-total td { background: #DFDFDF; }
    .cash-book-report .db-part-link { cursor: pointer; }
</style>

<div class="page-header">
    <h2>Cash Book</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.cashBook') }}" id="cashBookFrm" class="cb-form" autocomplete="off">
        @csrf
        <div class="cb-row">
            <div class="cb-field">
                <label for="cash_ledger_head_id">Cash Name</label>
                <select id="cash_ledger_head_id" name="cash_ledger_head_id" class="cb-head" required>
                    <option value="">Select Cash Type</option>
                    @foreach($cashHeads as $headId => $headTitle)
                        <option value="{{ $headId }}" {{ (string) $selectedHead === (string) $headId ? 'selected' : '' }}>{{ $headTitle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cb-field">
                <label for="payment_date">For the Period</label>
                <input type="date" id="payment_date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}">
            </div>
            <div class="cb-field">
                <label for="payment_date_to">To</label>
                <input type="date" id="payment_date_to" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}">
            </div>
        </div>
        <div class="cb-row">
            <div class="cb-field">
                <label for="report_type">Type</label>
                <select id="report_type" name="report_type">
                    <option value="">select type</option>
                    @foreach(['Receipt', 'Payment'] as $t)
                        <option value="{{ $t }}" {{ $reportType === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cb-field">
                <label for="operator">Operator</label>
                <select id="operator" name="operator">
                    <option value="">Select Operator</option>
                    @foreach(['>', '<', '>=', '<=', '=', '<>'] as $op)
                        <option value="{{ $op }}" {{ $operator === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cb-field">
                <label for="amount">Amount</label>
                <input type="text" id="amount" name="amount" class="text-right" value="{{ $input['amount'] ?? '' }}">
            </div>
            <div class="cb-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_account_cash_book');">Print Friendly</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.pdf('print_account_cash_book', 'CashBook.pdf');">Export to PDF</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.excel('print_account_cash_book', 'Cash Book', 'CashBook.xls');">Export to Excel</a>
                <a href="{{ route('society.reports.cashBook') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_account_cash_book">
        <div class="print-cash-book cash-book-report">
            <div class="row">
                <h5>{{ strip_tags((string) ($society->society_name ?? '')) }}</h5>
                <div class="report-address-heading">{{ strip_tags((string) ($society->registration_no ?? '')) }}</div>
                <div class="report-address-heading">{{ strip_tags((string) ($society->address ?? '')) }}</div>
            </div>
            <div class="row1">
                <div class="report-bill">Book Name Cash in Hand</div>
                <div class="report-bill"></div>
                <div class="report-bill-outer-section">
                    <div class="table-wrap1">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="3" class="text-center">Voucher</th>
                                    <th rowspan="2" class="text-center">Particular</th>
                                    <th rowspan="2" class="text-center">Receipt</th>
                                    <th style="width:15%;" rowspan="2" class="text-center">Payment</th>
                                    <th style="width:15%;" rowspan="2" class="text-center">Balance</th>
                                </tr>
                                <tr>
                                    <th style="width:8%;" class="text-center">Date</th>
                                    <th style="width:8%;" class="text-center">Type</th>
                                    <th style="width:5%;" class="text-center">No</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $totalDeposit = 0;
                                $totalWithdraw = 0;
                                $idCount = 1;
                            ?>
                                @if($ledgerHeadSelected && !empty($openingBalance))
                                    <?php $totalDeposit = $openingBalance; ?>
                                    <tr>
                                        <td>{{ !empty($financialYearStart) ? date('d/m/Y', strtotime($financialYearStart)) : '' }}</td>
                                        <td></td>
                                        <td></td>
                                        <td>Opening Balance</td>
                                        <td class="text-right">{{ number_format($openingBalance, 2, '.', ',') }}</td>
                                        <td class="text-right">0.00</td>
                                        <td class="text-right">{{ number_format($openingBalance, 2, '.', ',') }}</td>
                                    </tr>
                                @endif
                            @if(count($cashBookData) > 0)
                                @foreach($cashBookData as $paymentDate => $cashBookInfo)
                                    @foreach($cashBookInfo as $flagType => $bookData)
                                        @foreach($bookData as $finalData)
                                            <?php
                                                $deposit = $finalData['deposit'] ?? 0;
                                                $withdrawal = $finalData['withdrawal'] ?? 0;
                                                $totalDeposit += $deposit;
                                                $totalWithdraw += $withdrawal;
                                                $totalBal = $totalDeposit - abs($totalWithdraw);
                                                $posNeg = $totalBal < 0 ? 'Cr' : 'Dr';
                                                $voucher = (isset($finalData['voucher_no']) && $finalData['voucher_no'] !== '' && $finalData['voucher_no'] !== null) ? $finalData['voucher_no'] : $idCount;
                                            ?>
                                            <tr>
                                                <td>{{ isset($finalData['payment_date']) ? date('d/m/Y', strtotime($finalData['payment_date'])) : '' }}</td>
                                                <td class="text-center">{{ $finalData['payment_flag'] ?? '' }}</td>
                                                <td class="text-center">{{ $voucher }}</td>
                                                <td class="db-part-link" data-href="{{ $finalData['link'] ?? '' }}">{!! $safe($finalData['title'] ?? '') !!} <br> {!! $safe($finalData['particulars'] ?? '') !!}</td>
                                                <td class="text-right">{{ $deposit }}</td>
                                                <td class="text-right">{{ $withdrawal }}</td>
                                                <td class="text-right">{{ number_format((float) abs($totalBal), 2, '.', ',') . ' ' . $posNeg }}</td>
                                            </tr>
                                            <?php $idCount++; ?>
                                        @endforeach
                                    @endforeach
                                @endforeach
                                <?php
                                    $totalBal = $totalDeposit - abs($totalWithdraw);
                                    $posNegTotal = $totalBal < 0 ? 'Cr' : 'Dr';
                                ?>
                                <tr class="cb-total">
                                    <td></td><td></td><td></td>
                                    <td class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($totalDeposit, 2, '.', ',') }}</td>
                                    <td class="text-right">{{ number_format($totalWithdraw, 2, '.', ',') }}</td>
                                    <td class="text-right">{{ number_format((float) abs($totalBal), 2, '.', ',') . ' ' . $posNegTotal }}</td>
                                </tr>
                            @else
                                <tr><td colspan="8"><center>Payment has not made for selected bank. </center></td></tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
<script src="https://unpkg.com/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script src="{{ asset('js/report_export.js') }}"></script>
<script src="{{ asset('js/report_print.js') }}"></script>
<script>
// A row opens the voucher it came from in a new tab (delegated, so it also works after Print Friendly
// has swapped the page markup out and back).
document.addEventListener('click', function (e) {
    var cell = e.target.closest ? e.target.closest('.db-part-link') : null;
    if (!cell) { return; }
    var url = (cell.getAttribute('data-href') || '').trim();
    if (/^https?:\/\//i.test(url)) { window.open(url, '_blank', 'noopener,noreferrer'); }
});
</script>
@endsection
