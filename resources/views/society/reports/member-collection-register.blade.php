@extends('layouts.app')
@section('title', $title ?? 'Member Collection Register')
@section('content')
@include('society.reports._styles')
<?php
    use App\Support\ReportUtil;
    $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    $from = trim((string) ($input['payment_date'] ?? ''));
    $to = trim((string) ($input['payment_date_to'] ?? ''));
    $period = '';
    if ($from !== '' && $to !== '') {
        $period = date('d-M-Y', strtotime($from)) . ' to ' . date('d-M-Y', strtotime($to));
    } elseif ($from !== '') {
        $period = 'From ' . date('d-M-Y', strtotime($from));
    } elseif ($to !== '') {
        $period = 'Up to ' . date('d-M-Y', strtotime($to));
    }
?>
<div class="page-header">
    <h2>Member Collection Register</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.memberCollectionRegister') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>For the Period</label><input type="date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}"></div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_member_collection_register', 'file' => 'MemberCollectionRegister', 'sheet' => 'Member Collection Register', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.memberCollectionRegister') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_member_collection_register">
        <div class="print-member-collection-register ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">Member Collection Register</div>
            <div class="report-bill">{{ $period }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Month</th><th>Building</th><th>Wing</th><th>Unit No</th><th>Receipt Date</th><th>Receipt No.</th>
                        <th>Member Name</th><th>Amount</th><th>Chq/Txn No</th><th>Bank Name</th><th>Branch Name</th>
                    </tr>
                </thead>
                <tbody>
                @if(count($data) > 0)
                    <?php $totalAmt = 0; ?>
                    @foreach($data as $entry)
                        @foreach($entry['payments'] as $p)
                            <?php $totalAmt += isset($p['amount_paid']) ? (float) $p['amount_paid'] : 0; ?>
                            <tr>
                                <td class="text-center">{{ isset($p['bill_month']) ? ($months[$p['bill_month']] ?? '') : '' }}</td>
                                <td>{{ $entry['member']['building_name'] }}</td>
                                <td>-</td>
                                <td class="text-center">{{ $entry['member']['flat_no'] }}</td>
                                <td>{{ isset($p['payment_date']) ? ReportUtil::formatDate($p['payment_date'], 'd/m/Y') : '' }}</td>
                                <td class="text-center">{{ $p['receipt_id'] }}</td>
                                <td>{{ $entry['member']['member_prefix'] }}{{ $entry['member']['member_name'] }}</td>
                                <td class="text-right">{{ $p['amount_paid'] }}</td>
                                <td class="text-center">{{ isset($p['cheque_reference_number']) ? $p['cheque_reference_number'] : '-' }}</td>
                                <td>{{ isset($p['society_bank_id']) ? ((isset($p['payment_mode']) && $p['payment_mode'] == 1) ? ($cashHeads[$p['society_bank_id']] ?? '') : ($bankHeads[$p['society_bank_id']] ?? '')) : '' }}</td>
                                <td>{{ isset($p['member_bank_branch']) ? $p['member_bank_branch'] : '-' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="ar-total" style="background-color: #DFDFDF;">
                        <td colspan="7" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalAmt, 2, '.', ',') }}</strong></td>
                        <td></td><td></td><td></td>
                    </tr>
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
