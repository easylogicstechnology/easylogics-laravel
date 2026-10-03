@extends('layouts.app')
@section('title', $title ?? 'Balance Sheet')
@section('content')
@include('society.reports._styles')
<style>.clsHead { font-weight: 600; }</style>
<?php
    $sessionFrom = $session->read('Auth.year_start_date');
    $sessionTo = $session->read('Auth.year_end_date');
    $orderLiab = $postData['order_liabilities'] ?? '';
    $orderAsset = $postData['order_assets'] ?? '';
?>
<div class="page-header">
    <h2>Balance Sheet</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.balanceSheet') }}" class="ar-form" autocomplete="off" id="balanceSheetFrm">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>From</label><input type="date" name="from_date" value="{{ !empty($postData['from_date']) ? $postData['from_date'] : $sessionFrom }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="to_date" value="{{ !empty($postData['to_date']) ? $postData['to_date'] : $sessionTo }}"></div>
            <div class="ar-field">
                <label><input type="checkbox" name="set_order" id="set_order" value="1" {{ (isset($postData['set_order']) && $postData['set_order'] == '1') ? 'checked' : '' }}> Set Order of Ledger Heads</label>
                <input type="hidden" name="order_liabilities" id="order_liabilities" value="{{ $orderLiab }}">
                <input type="hidden" name="order_assets" id="order_assets" value="{{ $orderAsset }}">
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Show</button>
                @include('society.reports._actions', ['printId' => 'print_account_balancesheet', 'file' => 'BalanceSheet', 'sheet' => 'Balance Sheet', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.balanceSheet') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>

    {{-- "Sort Ledger Heads": move the heads up / down, Done puts the order into the form --}}
    <div id="sort_heads_panel" style="display:none;border:1px solid #ccd;padding:12px;margin-top:10px;border-radius:4px">
        <h6 style="font-weight:600">Sort Ledger Heads</h6>
        <div style="display:flex;gap:30px;flex-wrap:wrap">
            <div>
                <h5>Sort Liability Heads</h5>
                <table class="table table-bordered"><tbody id="sorted_table_Liability">
                    @foreach($societyHeads['Liability'] ?? [] as $title)
                        <tr><td>{{ $title }}</td><td><a href="javascript:void(0)" onclick="moveRow(this,-1)">&#9650;</a> <a href="javascript:void(0)" onclick="moveRow(this,1)">&#9660;</a></td></tr>
                    @endforeach
                    <tr><td>Reserve Fund</td><td><a href="javascript:void(0)" onclick="moveRow(this,-1)">&#9650;</a> <a href="javascript:void(0)" onclick="moveRow(this,1)">&#9660;</a></td></tr>
                </tbody></table>
            </div>
            <div>
                <h5>Sort Asset Heads</h5>
                <table class="table table-bordered"><tbody id="sorted_table_Asset">
                    @foreach($societyHeads['Asset'] ?? [] as $title)
                        <tr><td>{{ $title }}</td><td><a href="javascript:void(0)" onclick="moveRow(this,-1)">&#9650;</a> <a href="javascript:void(0)" onclick="moveRow(this,1)">&#9660;</a></td></tr>
                    @endforeach
                    <tr><td>Loans &amp; Advances</td><td><a href="javascript:void(0)" onclick="moveRow(this,-1)">&#9650;</a> <a href="javascript:void(0)" onclick="moveRow(this,1)">&#9660;</a></td></tr>
                </tbody></table>
            </div>
        </div>
        <button type="button" class="btn btn-success btn-sm" onclick="submitSort();">Done</button>
    </div>
</div>

<div class="card ar-report">
    @include('society.reports._balance_sheet_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
<script>
reportExport.alignOnReady('print_account_balancesheet');
document.getElementById('set_order').addEventListener('change', function () {
    document.getElementById('sort_heads_panel').style.display = this.checked ? 'block' : 'none';
});
function moveRow(link, dir) {
    var tr = link.closest('tr');
    var sib = dir < 0 ? tr.previousElementSibling : tr.nextElementSibling;
    if (!sib) { return; }
    if (dir < 0) { tr.parentNode.insertBefore(tr, sib); } else { tr.parentNode.insertBefore(sib, tr); }
}
// the order is sent as the head-group titles, comma separated (same as CakePHP)
function submitSort() {
    function titles(id) {
        return Array.prototype.map.call(document.querySelectorAll('#' + id + ' tr td:first-child'), function (td) { return td.textContent; }).join(',') + ',';
    }
    document.getElementById('order_liabilities').value = titles('sorted_table_Liability');
    document.getElementById('order_assets').value = titles('sorted_table_Asset');
    document.getElementById('sort_heads_panel').style.display = 'none';
}
</script>
@endsection
