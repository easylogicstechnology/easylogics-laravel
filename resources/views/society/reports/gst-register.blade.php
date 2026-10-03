@extends('layouts.app')
@section('title', $title ?? 'GST Register')
@section('content')
@include('society.reports._styles')
<?php use App\Support\ReportUtil; ?>
<div class="page-header">
    <h2>GST Register</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.gstRegister') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>Bill Date From</label><input type="date" name="bill_generated_date" value="{{ $input['bill_generated_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="bill_generated_date_to" value="{{ $input['bill_generated_date_to'] ?? '' }}"></div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_gst_register', 'file' => 'GstRegister', 'sheet' => 'GST Register', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.gstRegister') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_gst_register">
        <div class="print-gst-register ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">GST Register</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Unit No</th>
                        <th>Bill No</th>
                        <th>Member Name</th>
                        <th>Bill Date</th>
                        @foreach($tariffs as $title)<th>{{ substr((string) $title, 0, 5) }}</th>@endforeach
                        <th>Bill Amount</th>
                        <th>SGST {!! '@' !!}{{ $params->sgst_tax_per ?? '' }}%</th>
                        <th>CGST {!! '@' !!}{{ $params->cgst_tax_per ?? '' }}%</th>
                        <th>Net Tax Amount</th>
                        <th>GST NO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $vertical = [];
                        $billAmtTotal = $cgstAmtTotal = $sgstAmtTotal = $totalTaxAmtTotal = 0.00;
                        $sr = 1;
                    ?>
                    @foreach($data as $billId => $bill)
                        <?php
                            $summary = $bill['MemberBillSummary'];
                            $tariffLines = $bill['MemberBillGenerate'] ?? [];
                            $member = $members[$summary['member_id']] ?? [];
                            $billAmount = $sgstTotal = $cgstTotal = 0.00;
                        ?>
                        <tr>
                            <td class="text-center">{{ $sr }}</td>
                            <td class="text-center">{{ $member['flat_no'] ?? '' }}</td>
                            <td class="text-center">{{ $summary['bill_no'] }}</td>
                            <td>{{ ($member['member_prefix'] ?? '') . ($member['member_name'] ?? '') }}</td>
                            <td>{{ ReportUtil::formatDate($summary['bill_date'], 'd/m/Y') }}</td>
                            @foreach($tariffs as $ledgerId => $title)
                                <?php
                                    $amount = 0.00;
                                    if (isset($tariffLines[$ledgerId])) {
                                        $amount = $tariffLines[$ledgerId]['amount'];
                                        $sgstTotal += $tariffLines[$ledgerId]['sgst'];
                                        $cgstTotal += $tariffLines[$ledgerId]['cgst'];
                                        $billAmount += $amount;
                                        $vertical[$ledgerId] = isset($vertical[$ledgerId]) ? $vertical[$ledgerId] + $amount : $amount;
                                    }
                                ?>
                                <td class="text-right">{{ $amount }}</td>
                            @endforeach
                            <?php
                                $billAmtTotal += $billAmount;
                                $cgstAmtTotal += $cgstTotal;
                                $sgstAmtTotal += $sgstTotal;
                                $totalTaxAmtTotal = $sgstAmtTotal + $cgstAmtTotal;
                            ?>
                            <td class="text-right">{{ $billAmount }}</td>
                            <td class="text-right">{{ $sgstTotal }}</td>
                            <td class="text-right">{{ $cgstTotal }}</td>
                            <td class="text-right">{{ $sgstTotal + $cgstTotal }}</td>
                            <td class="text-right">{{ $member['gstin_no'] ?? '' }}</td>
                        </tr>
                        <?php $sr++; ?>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right" style="font-weight:bold;">Grand Total:</td>
                        @foreach($vertical as $amt)<td class="text-right" style="font-weight:bold;">{{ number_format((float) $amt, 2, '.', '') }}</td>@endforeach
                        <td class="text-right" style="font-weight:bold;">{{ $billAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $sgstAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $cgstAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;">{{ $totalTaxAmtTotal }}</td>
                        <td class="text-right" style="font-weight:bold;"></td>
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
