@extends('layouts.app')
@section('title', $title ?? 'Payment Register')
@section('content')
@include('society.reports._styles')
<?php
    use App\Support\ReportUtil;
    $type = $input['payment_type'] ?? '';
    $subType = $input['transaction_type'] ?? '';
?>
<div class="page-header">
    <h2>Payment Register</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.paymentRegister') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label for="payment_date">From</label>
                <input type="date" id="payment_date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}">
            </div>
            <div class="ar-field">
                <label for="payment_date_to">To</label>
                <input type="date" id="payment_date_to" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}">
            </div>
            <div class="ar-field">
                <label for="payment_type">Type</label>
                <select id="payment_type" name="payment_type" onchange="hidePaymentRegisterInputFields(this.value);" required>
                    <option value="">Select</option>
                    @foreach(['Bank', 'Cash', 'Both'] as $t)
                        <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label for="transaction_type">Transaction Sub-Type</label>
                <select id="transaction_type" name="transaction_type">
                    <option value="">Select</option>
                    @foreach(['Bank', 'Cash'] as $t)
                        <option value="{{ $t }}" {{ $subType === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Show</button>
                @include('society.reports._actions', ['printId' => 'print_account_payment_register', 'file' => 'PaymentRegister', 'sheet' => 'Payment Register'])
                <a href="{{ route('society.reports.paymentRegister') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_account_payment_register">
        <div class="print-account-payment-register ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">Payment Register</div>
            <table>
                <thead>
                    <tr>
                        <th>VNo</th>
                        <th>VDate</th>
                        <th class="so-check">By</th>
                        <th class="so-check">Cheque No</th>
                        <th class="so-check">Cheque Date</th>
                        <th>Amount</th>
                        <th>Account Name</th>
                        <th>Rs.</th>
                        <th>Particular</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                @if(count($rows) > 0)
                    <?php $total = 0; ?>
                    <tbody>
                        @foreach($rows as $r)
                            <?php $total += !empty($r['amount']) ? $r['amount'] * 1 : 0.00; ?>
                            <tr>
                                <td>{{ $r['bill_voucher_number'] }}</td>
                                <td>{{ ReportUtil::mysqlToDate($r['payment_date'], '/') }}</td>
                                <td>{{ $banks[$r['payment_by_ledger_id']] ?? '' }}</td>
                                <td>{{ $r['cheque_reference_number'] }}</td>
                                <td>{{ ReportUtil::mysqlToDate($r['cheque_date'], '/') }}</td>
                                <td class="text-right">{{ $r['amount_text'] }}</td>
                                <td>{{ $r['ledger_title'] }}</td>
                                <td class="text-right">{{ $r['amount_text'] }}</td>
                                <td>{{ $r['particulars'] }}</td>
                                <td>{{ $r['notes'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Rs.{{ round($total, 2) }}</td>
                            <td colspan="7">{{ ReportUtil::amountInRupees($total) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
<script>
// Cash payments have no bank / cheque columns (same as CakePHP: only the headings are hidden).
function hidePaymentRegisterInputFields(paymentType) {
    document.querySelectorAll('.so-check').forEach(function (el) { el.style.display = paymentType === 'Cash' ? 'none' : ''; });
}
</script>
@endsection
