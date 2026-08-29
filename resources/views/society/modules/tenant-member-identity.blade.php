@extends('layouts.app')
@section('title', 'Tenant Member Identity')
@section('content')
<div class="page-header">
    <h2>Tenant / Nominal Member Identity</h2>
    <a href="{{ route('society.addTenant') }}" class="btn btn-primary btn-sm">+ Add Tenant</a>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Lease Type</th>
                    <th>Agreement on</th>
                    <th>Contact no</th>
                    <th>Rent Per Month</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $t)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->tenant_name }}</td>
                    <td>{{ $t->lease_type }}</td>
                    <td>{{ ($t->agreement_on && $t->agreement_on != '0000-00-00') ? $t->agreement_on : '' }}</td>
                    <td>{{ $t->phone }}</td>
                    <td style="text-align:right;">{{ number_format($t->rent_per_month, 2) }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('society.addTenant', $t->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteTenant', $t->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this tenant?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; color:#999;">No tenants found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
