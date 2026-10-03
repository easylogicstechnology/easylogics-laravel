@extends('layouts.app')
@section('title', $title ?? 'Income Expenditure Statement Details')
@section('content')
@include('society.reports._styles')
<style>.number-right { text-align: right; }</style>
<?php
    $postData = $post ?? [];
    $sessionFrom = $session->read('Auth.year_start_date');
    $sessionTo = $session->read('Auth.year_end_date');
?>
<div class="page-header">
    <h2>Income Expenditure Statement Details</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.incomeExpDetails') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>From</label><input type="date" name="from_date" value="{{ !empty($postData['from_date']) ? $postData['from_date'] : $sessionFrom }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="to_date" value="{{ !empty($postData['to_date']) ? $postData['to_date'] : $sessionTo }}"></div>
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Show</button>
                @include('society.reports._actions', ['printId' => 'print_income_exp_statement', 'file' => 'IncomeExpenditure', 'sheet' => 'Income & Expenditure'])
                <a href="{{ route('society.reports.incomeExpDetails') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card ar-report">
    @include('society.reports._income_exp_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
<script>
// same as CakePHP: the Total on the expenditure side shows the income total, and the excess of income over expenditure is the difference
document.addEventListener('DOMContentLoaded', function () {
    var inc = document.getElementById('totalamountincome');
    var sub = document.getElementById('subtotal');
    var exp = document.getElementById('totalamountexpenditure');
    var over = document.getElementById('over_expenditure');
    if (!inc || !sub || !exp || !over) { return; }
    sub.textContent = inc.textContent;
    var total = Number(sub.textContent.split(',').join('')) - Number(exp.textContent.split(',').join(''));
    over.textContent = total.toLocaleString();
});
</script>
@endsection
