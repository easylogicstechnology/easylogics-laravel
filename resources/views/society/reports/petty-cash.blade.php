@extends('layouts.app')
@section('title', $title ?? 'Petty Cash Register')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Petty Cash</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

{{-- CakePHP's Petty Cash Register screen is only the "For The Month of" form: nothing runs behind it. --}}
<div class="card">
    <form method="post" action="{{ route('society.reports.pettyCash') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label for="petty_month">For The Month of</label>
                <select id="petty_month" required>
                    <option value="">Select</option>
                    @foreach(['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'] as $m)
                        <option value="">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Show</button>
                <a href="{{ route('society.reportAccounts') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
