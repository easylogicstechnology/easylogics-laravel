@extends('layouts.app')

@section('title', 'Society Finance Year Mapping - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Society Finance Year Mapping</h2>
</div>

@if(session('info'))
<div class="alert alert-success">{{ session('info') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom:20px;">
    <h3>Map Society to Financial Year</h3>
    <form method="POST" action="{{ route('reseller.financeYearMapping') }}">
        @csrf
        <div class="grid-3" style="gap:16px; align-items:end;">
            <div class="form-group">
                <label>Society <span style="color:red;">*</span></label>
                <select name="society_id" class="form-control" required>
                    <option value="">-- Select Society --</option>
                    @foreach($societies as $s)
                        <option value="{{ $s->id }}">{{ $s->society_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Financial Year <span style="color:red;">*</span></label>
                <select name="financial_year_id" class="form-control" required>
                    <option value="">-- Select Financial Year --</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}">{{ $fy->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Save Mapping</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3>Existing Mappings</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Society</th>
                <th>Financial Year</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mappings as $i => $m)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $m->society->society_name ?? 'N/A' }}</td>
                <td>{{ $yearMap[$m->year_id]->year ?? 'N/A' }}</td>
                <td>
                    @if($m->is_active)
                        <span style="color:green; font-weight:600;">Active</span>
                    @else
                        <span style="color:#999;">Inactive</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="color:#999; text-align:center;">No mappings found. Assign a financial year to your societies above.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
