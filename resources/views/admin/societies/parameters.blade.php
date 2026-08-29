@extends('layouts.app')

@section('title', 'Society Parameters - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Society Parameters</h2>
</div>

<div class="grid-3">
    <div class="card">
        <h3>Billing Frequency</h3>
        <table>
            <thead><tr><th>Frequency Type</th></tr></thead>
            <tbody>
                @forelse($billingFrequencyData as $item)
                    <tr><td>{{ $item }}</td></tr>
                @empty
                    <tr><td class="text-muted">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Interest Type</h3>
        <table>
            <thead><tr><th>Interest Type</th></tr></thead>
            <tbody>
                @forelse($interestTypeData as $item)
                    <tr><td>{{ $item }}</td></tr>
                @empty
                    <tr><td class="text-muted">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Tariff Type</h3>
        <table>
            <thead><tr><th>Tariff Type</th></tr></thead>
            <tbody>
                @forelse($tariffTypeData as $item)
                    <tr><td>{{ $item }}</td></tr>
                @empty
                    <tr><td class="text-muted">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Account Category</h3>
        <table>
            <thead><tr><th>Title</th></tr></thead>
            <tbody>
                @forelse($accountCategoryData as $item)
                    <tr><td>{{ $item }}</td></tr>
                @empty
                    <tr><td class="text-muted">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Account Heads</h3>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Transaction Type</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountHeadData as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->transaction_type }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
