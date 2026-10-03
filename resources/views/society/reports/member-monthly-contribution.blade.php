@extends('layouts.app')
@section('title', $title ?? 'Member Monthly Contribution')
@section('content')
@include('society.reports._styles')
<?php
    // month order of the columns: Apr..Dec, Jan..Mar
    $order = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
    $society_name = \App\Support\ReportUtil::plain($society->society_name ?? '');
?>
<div class="page-header">
    <h2>Member Monthly Contribution</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.memberMonthlyContribution') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Building</label>
                <select name="building_id" id="ar_building_id" onchange="arLoadWings(this.value);">
                    <option value="">Select building</option>
                    @foreach($buildings as $bid => $bname)
                        <option value="{{ $bid }}" {{ (string) ($input['building_id'] ?? '') === (string) $bid ? 'selected' : '' }}>{{ $bname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Wing</label>
                <select name="wing_id" id="ar_wing_id" data-selected="{{ $input['wing_id'] ?? '' }}"><option value="">Select</option></select>
            </div>
            <div class="ar-field">
                <label>Member</label>
                <select name="member_id" id="member_monthly_contribution_member_name" class="ar-wide" onchange="document.getElementById('member_monthly_contribution_flat_no').value = this.value;">
                    <option value="">Select</option>
                    @foreach($members as $mid => $mname)
                        <option value="{{ $mid }}" {{ (string) ($input['member_name'] ?? '') === (string) $mid ? 'selected' : '' }}>{{ $mname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Unit No</label>
                {{-- CakePHP lists the member ids here (the value and the label are both the member id) --}}
                <select name="flat_no" id="member_monthly_contribution_flat_no" onchange="document.getElementById('member_monthly_contribution_member_name').value = this.value;">
                    <option value="">Select</option>
                    @foreach($members as $mid => $mname)
                        <option value="{{ $mid }}" {{ (string) ($input['flat_no'] ?? '') === (string) $mid ? 'selected' : '' }}>{{ $mid }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_member_monthly_cotribution', 'file' => 'MemberMonthlyContribution', 'sheet' => 'Member Monthly Contribution', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.memberMonthlyContribution') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
function arLoadWings(buildingId, selected) {
    var sel = document.getElementById('ar_wing_id');
    sel.innerHTML = '<option value="">Select</option>';
    if (!buildingId) { return; }
    fetch('{{ route('society.getWings') }}?building_id=' + encodeURIComponent(buildingId), {headers: {'Accept': 'application/json'}})
        .then(function (r) { return r.json(); })
        .then(function (wings) {
            Object.keys(wings).forEach(function (id) {
                var o = document.createElement('option');
                o.value = id; o.textContent = wings[id];
                if (selected && String(selected) === String(id)) { o.selected = true; }
                sel.appendChild(o);
            });
        });
}
document.addEventListener('DOMContentLoaded', function () {
    var b = document.getElementById('ar_building_id');
    if (b && b.value) { arLoadWings(b.value, document.getElementById('ar_wing_id').getAttribute('data-selected')); }
});
</script>

<div class="card">
    <div id="print_member_monthly_cotribution">
        <div class="print-member-monthly-cotribution ar-report">
            @include('society.reports._society_head')
            <table>
                <thead>
                    <tr>
                        <th>Particulars</th>
                        <th>Apr</th><th>May</th><th>Jun</th><th>Jul</th><th>Aug</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan</th><th>Feb</th><th>Mar</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                @if(!empty($contribution))
                    <?php $tot = array_fill_keys($order, null); ?>
                    @foreach($contribution as $headId => $b)
                        <tr>
                            <td>{{ $heads[$headId] ?? '' }}</td>
                            @foreach($order as $m)
                                <?php $tot[$m] += $b[$m] ?? 0; ?>
                                <td>{{ $b[$m] ?? '' }}</td>
                            @endforeach
                            <?php $rowTotal = ($b[4] ?? 0) + ($b[5] ?? 0) + ($b[6] ?? 0) + ($b[7] ?? 0) + ($b[8] ?? 0) + ($b[9] ?? 0) + ($b[10] ?? 0) + ($b[11] ?? 0) + ($b[12] ?? 0) + ($b[1] ?? 0) + ($b[2] ?? 0) + ($b[3] ?? 0); ?>
                            <td>{{ number_format($rowTotal, 2, '.', '') }}</td>
                        </tr>
                    @endforeach
                    <?php
                        $sumAll = fn () => ($tot[4] ?? 0) + ($tot[5] ?? 0) + ($tot[6] ?? 0) + ($tot[7] ?? 0) + ($tot[8] ?? 0) + ($tot[9] ?? 0) + ($tot[10] ?? 0) + ($tot[11] ?? 0) + ($tot[12] ?? 0) + ($tot[1] ?? 0) + ($tot[2] ?? 0) + ($tot[3] ?? 0);
                    ?>
                    <tr>
                        <td><b>Total</b></td>
                        @foreach($order as $m)<td>{{ !empty($tot[$m]) ? $tot[$m] : '' }}</td>@endforeach
                        <td>{{ number_format($sumAll(), 2, '.', '') }}</td>
                    </tr>
                    <tr>
                        <td>GST</td>
                        @foreach($order as $m)
                            <?php $tot[$m] += $tax[$m] ?? 0; ?>
                            <td>{{ $tax[$m] ?? '' }}</td>
                        @endforeach
                        <?php $taxTotal = ($tax[4] ?? 0) + ($tax[5] ?? 0) + ($tax[6] ?? 0) + ($tax[7] ?? 0) + ($tax[8] ?? 0) + ($tax[9] ?? 0) + ($tax[10] ?? 0) + ($tax[11] ?? 0) + ($tax[12] ?? 0) + ($tax[1] ?? 0) + ($tax[2] ?? 0) + ($tax[3] ?? 0); ?>
                        <td>{{ number_format($taxTotal, 2, '.', '') }}</td>
                    </tr>
                    <tr>
                        <td><b>Total With GST</b></td>
                        @foreach($order as $m)<td>{{ !empty($tot[$m]) ? $tot[$m] : '' }}</td>@endforeach
                        <td>{{ number_format($sumAll(), 2, '.', '') }}</td>
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
