@extends('layouts.app')
@section('title', 'Ledger Heads')
@section('content')
<div class="page-header">
    <h2>Ledger Heads</h2>
    <a href="{{ route('society.addLedgerHead') }}" class="btn btn-primary btn-sm">+ Add Ledger</a>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account Category</th>
                    <th>Account Head</th>
                    <th>Society Head Category</th>
                    <th>Society Heads</th>
                    <th>Opening Balance</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $lh)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $lh->accountCategory->title ?? '' }}</td>
                    <td>{{ $lh->accountHead->title ?? '' }}</td>
                    <td>{{ $lh->headSubCategory->title ?? '' }}</td>
                    <td>{{ $lh->title }}</td>
                    <td style="text-align:right;">{{ number_format($lh->opening_amount ?? 0, 2) }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('society.addLedgerHead', $lh->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteLedgerHead', $lh->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this ledger head?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; color:#999;">No ledger heads found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
