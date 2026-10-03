@extends('layouts.app')

@section('title', 'Month-wise Interest Rates - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Month-wise Interest Rates</h2>
</div>

<div class="card">
    @if (!$migrated)
        <div class="alert alert-error"><b>Not available yet:</b> this database has not been updated for month-wise interest rates (Config/Schema/society_month_interest_rates.sql).</div>
    @else
        <div class="alert alert-info">
            The <b>Update (Month-wise Rate)</b> button in the bill window and the recalculation after a payment charge each bill's interest at the rate set here for the <b>bill's month</b>.
            A month with no rate here uses the Interest Rate in Society Parameters{!! $parameterRate !== null ? ' (currently <b>' . e($parameterRate) . '%</b>)' : '' !!}, which this screen never changes.
        </div>

        <form method="post" action="{{ route('society.monthInterestRates') }}" id="monthRateForm" autocomplete="off" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-bottom:20px;">
            @csrf
            <div>
                <label for="from_month" style="display:block; font-size:13px; font-weight:600;">From</label>
                <select name="from_month" id="from_month" class="form-control" required style="width:130px; display:inline-block;">
                    @foreach ($months as $n => $label)<option value="{{ $n }}">{{ $label }}</option>@endforeach
                </select>
                <input type="number" name="from_year" id="from_year" class="form-control" value="{{ $thisYear }}" min="2000" max="2100" required style="width:90px; display:inline-block;">
            </div>
            <div>
                <label for="to_month" style="display:block; font-size:13px; font-weight:600;">To <small class="text-muted">(same as From for one month)</small></label>
                <select name="to_month" id="to_month" class="form-control" required style="width:130px; display:inline-block;">
                    @foreach ($months as $n => $label)<option value="{{ $n }}">{{ $label }}</option>@endforeach
                </select>
                <input type="number" name="to_year" id="to_year" class="form-control" value="{{ $thisYear }}" min="2000" max="2100" required style="width:90px; display:inline-block;">
            </div>
            <div>
                <label for="interest_rate" style="display:block; font-size:13px; font-weight:600;">Interest Rate (% yearly)</label>
                <input type="number" step="0.01" min="0" max="100" name="interest_rate" id="interest_rate" class="form-control" required style="width:130px;">
            </div>
            <div>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered" style="max-width:640px;">
                <thead>
                    <tr><th>Year</th><th>Month</th><th>Interest Rate (% yearly)</th><th style="width:140px;">Action</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ (int) $r->rate_year }}</td>
                            <td>{{ $months[(int) $r->rate_month] ?? $r->rate_month }}</td>
                            <td>{{ number_format((float) $r->interest_rate, 2) }}</td>
                            <td style="white-space:nowrap;">
                                <a href="javascript:void(0);" class="btn btn-sm btn-primary"
                                   onclick="editMonthRate({{ (int) $r->rate_year }}, {{ (int) $r->rate_month }}, '{{ number_format((float) $r->interest_rate, 2, '.', '') }}');">Edit</a>
                                <form method="post" action="{{ route('society.monthInterestRates') }}" style="display:inline;" onsubmit="return confirm('Remove this rate? That month will use the Society Parameter rate.');">
                                    @csrf
                                    <input type="hidden" name="delete_id" value="{{ (int) $r->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;" class="text-muted">No month-wise rates yet - every bill uses the Society Parameter rate.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@if ($migrated)
@section('scripts')
<script>
function editMonthRate(year, month, rate) {
    document.getElementById('from_year').value = year;
    document.getElementById('to_year').value = year;
    document.getElementById('from_month').value = month;
    document.getElementById('to_month').value = month;
    document.getElementById('interest_rate').value = rate;
    document.getElementById('interest_rate').focus();
}
</script>
@endsection
@endif
