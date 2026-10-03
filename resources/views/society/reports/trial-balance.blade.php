@extends('layouts.app')
@section('title', $title ?? 'Trial Balance')
@section('content')
@include('society.reports._styles')
<?php
    use App\Support\ReportUtil;
    // an empty value counted as 0 in the PHP 7 code; number_format() of it is 0.00
    // PHP 7 printed nothing for number_format('') and 0.00 for number_format(null)
    $nf = fn ($v, $d = 2) => $v === '' ? '' : number_format((float) $v, $d);
    $num = fn ($v) => is_numeric($v) ? $v + 0 : 0;
    $post = $post ?? [];
    $from = $post['payment_date'] ?? '';
    $to = $post['payment_date_to'] ?? '';
?>
<div class="page-header">
    <h2>Trial Balance</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.trialBalance') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>For the Period</label><input type="date" name="payment_date" value="{{ $from }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $to }}"></div>
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_trial_balance', 'file' => 'TrialBalance', 'sheet' => 'Trial Balance'])
                <a href="{{ route('society.reports.trialBalance') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_trial_balance">
        <div class="print-trial-balance ar-report">
            {{-- CakePHP never passes the society to this report, so its heading lines stay empty --}}
            <div class="row"><h5></h5><div class="report-address-heading"></div><div class="report-address-heading"></div></div>
            <div class="report-bill">Trial Balance</div>
            @if($from != '' && $to != '')
                <div class="report-bill">For the period {{ ReportUtil::formatDate($from, 'd/m/Y') }} to {{ ReportUtil::formatDate($to, 'd/m/Y') }}</div>
            @elseif($from != '')
                <div class="report-bill">For Date {{ ReportUtil::formatDate($from, 'd/m/Y') }}</div>
            @elseif($to != '')
                <div class="report-bill">For Date {{ ReportUtil::formatDate($to, 'd/m/Y') }}</div>
            @else
                <div class="report-bill">As on Date {{ date('d/m/Y') }}</div>
                <div class="report-bill">Financial Year {{ $startYear }} - {{ $endYear }}</div>
            @endif
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Particulars</th>
                        <th colspan="2" style="width:20%">Opening Balance</th>
                        <th colspan="2" style="width:20%">Transaction</th>
                        <th colspan="2" style="width:20%">Closing Balance</th>
                    </tr>
                    <tr><th>Dr</th><th>Cr</th><th>Dr</th><th>Cr</th><th>Dr</th><th>Cr</th></tr>
                </thead>
                <tbody>
                <?php $grand = ['opening' => ['credit' => 0, 'debit' => 0], 'transactions' => ['credit' => 0, 'debit' => 0], 'closing' => ['credit' => 0, 'debit' => 0]]; ?>
                @foreach($subCats as $id => $cat)
                    @continue(empty($heads[$id]))
                    <tr><td style="font-weight:bold;" colspan="7">{{ $cat['title'] }}</td></tr>
                    <?php
                        $catId = $cat['category_id'] ?? null;
                        $tot = ['opening' => ['credit' => 0, 'debit' => 0], 'transactions' => ['credit' => 0, 'debit' => 0], 'closing' => ['credit' => 0, 'debit' => 0]];
                    ?>
                    @foreach($heads[$id]['ledgers'] as $ledger)
                        <?php
                            $op = $ledger['opening'];
                            $tx = $ledger['transactions'];
                            $cl = $ledger['closing'];
                            if ($societyId == 22 && $ledger['details']['title'] == 'Interest Collection') {
                                $tx['credit'] = $num($tx['credit']) + 97.63;
                                $cl['credit'] = $num($cl['credit']) + 97.63;
                            }
                            // income / expense heads carry no opening on their own side
                            if ($catId == 4) { $op['debit'] = 0; }
                            if ($catId == 3) { $op['credit'] = 0; }
                            $tot['opening']['credit'] += $num($op['credit']);
                            $tot['opening']['debit'] += $num($op['debit']);
                            $tot['transactions']['credit'] += $num($tx['credit']);
                            $tot['transactions']['debit'] += $num($tx['debit']);
                            $tot['closing']['credit'] += $num($cl['credit']);
                            $tot['closing']['debit'] += $num($cl['debit']);
                            $lhId = isset($ledger['details']['id']) ? (int) $ledger['details']['id'] : 0;
                            $isBank = !empty($bankIds[$lhId]);
                            $isCash = (!$isBank && !empty($cashIds[$lhId]));
                            $rowStyle = $isBank ? 'background-color:#e7f1ff;' : ($isCash ? 'background-color:#e9f7ef;' : '');
                            if ($catId == 4) { $tot['opening']['debit'] = 0.00; }
                        ?>
                        <tr style="{{ $rowStyle }}">
                            <td>{{ $ledger['details']['title'] }}@if($isBank) <span style="color:#2e6da4;font-weight:bold;font-size:11px;">[Bank]</span>@elseif($isCash) <span style="color:#1e7e34;font-weight:bold;font-size:11px;">[Cash]</span>@endif</td>
                            <td id="{{ $catId }}" class="text-center">{{ $catId == 4 ? 0 : $nf($op['debit']) }}</td>
                            <td class="text-center">{{ $catId == 3 ? 0 : $nf($op['credit']) }}</td>
                            <td class="text-center">{{ $tot['transactions']['debit'] != 0 ? $nf($tx['debit']) : 0 }}</td>
                            <td class="text-center">{{ $tot['transactions']['credit'] != 0 ? $nf($tx['credit']) : 0 }}</td>
                            <td class="text-center">{{ $tot['closing']['debit'] != 0 ? $nf($cl['debit']) : 0 }}</td>
                            <td class="text-center">{{ $tot['closing']['credit'] != 0 ? $nf($cl['credit']) : 0 }}</td>
                        </tr>
                    @endforeach
                    <?php
                        foreach (['opening', 'transactions', 'closing'] as $k) {
                            $grand[$k]['credit'] += $tot[$k]['credit'];
                            $grand[$k]['debit'] += $tot[$k]['debit'];
                        }
                    ?>
                    <tr>
                        <td style="font-weight:bold;">&nbsp;</td>
                        @foreach(['opening', 'transactions', 'closing'] as $k)
                            <td style="font-weight:bold;" class="text-center">{{ !empty($tot[$k]['debit']) ? $nf($tot[$k]['debit']) : 0 }}</td>
                            <td style="font-weight:bold;" class="text-center">{{ !empty($tot[$k]['credit']) ? $nf($tot[$k]['credit']) : 0 }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <?php
                        $mis = 'color:#d9534f;background-color:#ffdddd;';
                        $opMis = abs($grand['opening']['debit'] - $grand['opening']['credit']) >= 0.01;
                        $trMis = abs($grand['transactions']['debit'] - $grand['transactions']['credit']) >= 0.01;
                        $clMis = abs($grand['closing']['debit'] - $grand['closing']['credit']) >= 0.01;
                        $flags = ['opening' => $opMis, 'transactions' => $trMis, 'closing' => $clMis];
                    ?>
                    <tr>
                        <td style="font-weight:bold;">Grand Total:</td>
                        @foreach(['opening', 'transactions', 'closing'] as $k)
                            <td style="font-weight:bold;{{ $flags[$k] ? $mis : '' }}" class="text-center">{{ !empty($grand[$k]['debit']) ? $nf($grand[$k]['debit']) : '' }}</td>
                            <td style="font-weight:bold;{{ $flags[$k] ? $mis : '' }}" class="text-center">{{ !empty($grand[$k]['credit']) ? $nf($grand[$k]['credit']) : '' }}</td>
                        @endforeach
                    </tr>
                    @if($opMis || $trMis || $clMis)
                        <tr>
                            <td style="font-weight:bold;color:#d9534f;">Difference (Dr - Cr):</td>
                            @foreach(['opening', 'transactions', 'closing'] as $k)
                                <td colspan="2" style="font-weight:bold;{{ $flags[$k] ? $mis : '' }}" class="text-center">{{ $flags[$k] ? $nf($grand[$k]['debit'] - $grand[$k]['credit']) : '' }}</td>
                            @endforeach
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
    @if($opMis || $trMis || $clMis)
        <div style="margin-top:10px;padding:10px 14px;border:1px solid #f0c0c0;background:#fff6f6;color:#a94442;font-size:13px;line-height:1.6;">
            <b>Difference ({{ $nf($trMis ? ($grand['transactions']['debit'] - $grand['transactions']['credit']) : ($grand['closing']['debit'] - $grand['closing']['credit'])) }}) kaise locate karein:</b><br>
            1. Upar <b>For the Period</b> aur <b>To</b> me ek-ek month ki dates daal kar Submit karein.<br>
            2. Jis month me Grand Total <span style="background:#ffdddd;padding:0 3px;">red</span> (Dr &ne; Cr) aaye, difference usi month ki kisi entry me hai.<br>
            3. Us month ke andar din-wise (ya bank/cash-wise, upar tagged rows) narrow karke us din ki <b>Payment Entry / Journal Voucher / Receipt</b> me ek-tarfa ya galat-amount wali entry theek karein &mdash; balance apne aap tie ho jayega.
        </div>
    @endif
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
