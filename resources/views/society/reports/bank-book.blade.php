@extends('layouts.app')
@section('title', $title ?? 'Bank Book')
@section('content')
<?php
    // Particulars carry a <br> the report adds itself; everything else is user data and is escaped.
    $safe = fn ($text) => str_replace('&lt;br&gt;', '<br>', e((string) $text));
    $selectedBank = $input['society_bank_id'] ?? '';
    $reportType = $input['report_type'] ?? '';
    $operator = $input['operator'] ?? '';
?>
<style>
    .bb-form .bb-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 12px; }
    .bb-form .bb-field { display: flex; flex-direction: column; gap: 4px; }
    .bb-form label { font-size: 12px; color: #555; font-weight: 600; }
    .bb-form select, .bb-form input { padding: 7px 8px; border: 1px solid #ccd; border-radius: 4px; font-size: 13px; }
    .bb-form .bb-bank { min-width: 260px; }
    .bb-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .bank-book-report { margin-top: 18px; }
    .bank-book-report h5 { text-align: center; font-size: 16px; margin: 0 0 4px; }
    .bank-book-report .report-address-heading { text-align: center; font-size: 12px; color: #444; }
    .bank-book-report .report-bill { text-align: center; font-weight: 600; margin-top: 10px; }
    .bank-book-report table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
    .bank-book-report th, .bank-book-report td { border: 1px solid #999; padding: 3px 6px; vertical-align: top; }
    .bank-book-report th { background: #f0f0f0; text-align: center; }
    .bank-book-report .text-right { text-align: right; }
    .bank-book-report .text-center { text-align: center; }
    .bank-book-report .bb-total td { background: #DFDFDF; }
    .bank-book-report .db-part-link { cursor: pointer; }
</style>

<div class="page-header">
    <h2>Bank Book</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.bankBook') }}" id="bankBookFrm" class="bb-form" autocomplete="off">
        @csrf
        <div class="bb-row">
            <div class="bb-field">
                <label for="society_bank_id">Bank Name</label>
                <select id="society_bank_id" name="society_bank_id" class="bb-bank" required>
                    <option value="">Select Bank</option>
                    @foreach($banks as $bankId => $bankName)
                        <option value="{{ $bankId }}" {{ (string) $selectedBank === (string) $bankId ? 'selected' : '' }}>{{ $bankName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bb-field">
                <label for="payment_date">For the Period</label>
                <input type="date" id="payment_date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}">
            </div>
            <div class="bb-field">
                <label for="payment_date_to">To</label>
                <input type="date" id="payment_date_to" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}">
            </div>
        </div>
        <div class="bb-row">
            <div class="bb-field">
                <label for="report_type">Type</label>
                <select id="report_type" name="report_type">
                    <option value="">select type</option>
                    @foreach(['Deposit', 'Withdrawal', 'Contra'] as $t)
                        <option value="{{ $t }}" {{ $reportType === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bb-field">
                <label for="operator">Operator</label>
                <select id="operator" name="operator">
                    <option value="">Select Operator</option>
                    @foreach(['>', '<', '>=', '<=', '=', '<>'] as $op)
                        <option value="{{ $op }}" {{ $operator === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bb-field">
                <label for="amount">Amount</label>
                <input type="text" id="amount" name="amount" class="text-right" value="{{ $input['amount'] ?? '' }}">
            </div>
            <div class="bb-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_account_bank_book');">Print Friendly</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.pdf('print_account_bank_book', 'BankBook.pdf');">Export to PDF</a>
                <a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.excel('print_account_bank_book', 'Bank Book', 'BankBook.xls');">Export to Excel</a>
                <a href="{{ route('society.reports.bankBook') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_account_bank_book">
        <div class="print-bank-book bank-book-report">
            <div class="row">
                <h5>{{ strip_tags((string) ($society->society_name ?? '')) }}</h5>
                <div class="report-address-heading">{{ strip_tags((string) ($society->registration_no ?? '')) }}</div>
                <div class="report-address-heading">{{ strip_tags((string) ($society->address ?? '')) }}</div>
            </div>
            <div class="row1">
                <div class="report-bill">Bank Book of</div>
                <div class="report-bill">{{ $banks[$selectedBank] ?? '' }}</div>
                <div class="report-bill-outer-section">
                    <div class="table-wrap1">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="3" class="text-center">Voucher</th>
                                    <th rowspan="2" class="text-center">Particular</th>
                                    <th style="width:5%;" rowspan="2" class="text-center">Cheque No</th>
                                    <th style="width:10%;" rowspan="2" class="text-center">Deposit</th>
                                    <th style="width:10%;" rowspan="2" class="text-center">Withdrawal</th>
                                    <th style="width:10%;" rowspan="2" class="text-center">Balance </th>
                                </tr>
                                <tr>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">No</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $totalDeposit = 0;
                                $totalWithdraw = 0;
                                // The opening balance sits in the running balance but not in the Total Deposit column.
                                $openingBal = 0;
                                $idCount = 1;
                            ?>
                                @if(!empty($openingBalance))
                                    <?php $openingBal = $openingBalance; ?>
                                    <tr>
                                        <td>{{ !empty($openingBalanceAsOfDate) ? date('d/m/Y', strtotime($openingBalanceAsOfDate)) : (!empty($financialYearStart) ? date('d/m/Y', strtotime($financialYearStart)) : '') }}</td>
                                        <td></td>
                                        <td></td>
                                        <td>Opening Balance</td>
                                        <td></td>
                                        <td class="text-right">0.00</td>
                                        <td class="text-right">0.00</td>
                                        <td class="text-right">{{ number_format($openingBalance, 2) }}{{ $openingBalance < 0 ? 'Cr' : 'Dr' }}</td>
                                    </tr>
                                @endif
                            @if(count($bankBookData) > 0)
                                @foreach($bankBookData as $paymentDate => $bankBookInfo)
                                    @foreach($bankBookInfo as $flagType => $bookData)
                                        @foreach($bookData as $finalData)
                                            <?php
                                                $totalDeposit += $finalData['deposit'] ?? 0;
                                                $totalWithdraw += $finalData['withdrawal'] ?? 0;
                                                $totalBalancePerRow = $openingBal + $totalDeposit - abs($totalWithdraw);
                                                $posNeg = $totalBalancePerRow < 0 ? 'Cr' : 'Dr';
                                                $title = $finalData['title'] ?? '';
                                                $particular = $finalData['particulars'] ?? '';
                                                $href = $finalData['link'] ?? '';
                                            ?>
                                            <tr>
                                                <td>{{ isset($finalData['payment_date']) ? date('d/m/Y', strtotime($finalData['payment_date'])) : '' }}</td>
                                                <td>{{ $finalData['payment_flag'] ?? '' }}</td>
                                                <td>{{ $idCount }}</td>
                                                <td class="db-part-link" data-href="{{ $href }}">{!! $safe($title) !!} <br> {!! $safe($particular) !!}</td>
                                                <td style="width:5%;" class="text-right">{{ $finalData['cheque_number'] ?? '' }}</td>
                                                <td style="width:10%;" class="text-right">{{ $finalData['deposit'] ?? '' }}</td>
                                                <td style="width:10%;" class="text-right">{{ $finalData['withdrawal'] ?? '' }}</td>
                                                <td style="width:10%;" class="text-right">{{ number_format((float) abs($totalBalancePerRow), 2, '.', ',') . ' ' . $posNeg }}</td>
                                            </tr>
                                            <?php $idCount++; ?>
                                        @endforeach
                                    @endforeach
                                @endforeach
                                <?php
                                    $totalBal = $openingBal + $totalDeposit - abs($totalWithdraw);
                                    $posNegTotal = $totalBal < 0 ? 'Cr' : 'Dr';
                                ?>
                                <tr class="bb-total">
                                    <td></td><td></td><td></td><td></td>
                                    <td class="text-right">Total</td>
                                    <td style="width:10%;" class="text-right">{{ number_format($totalDeposit, 2, '.', ',') }}</td>
                                    <td style="width:10%;" class="text-right">{{ number_format($totalWithdraw, 2, '.', ',') }}</td>
                                    <td style="width:10%;" class="text-right">{{ number_format((float) abs($totalBal), 2, '.', ',') . ' ' . $posNegTotal }}</td>
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
