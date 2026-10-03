@extends('layouts.app')
@section('title', 'Bulk Opening Balance Update')
@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
    <h2>Bulk Opening Balance Update</h2>
    <a href="{{ route('society.downloadMemberDetails') }}" class="btn btn-success" title="Download Member Details Sample CSV">Download Member Details</a>
</div>

@if(session('ob_result'))
    @php($r = session('ob_result'))
    <div class="alert alert-success">
        <strong>Member Closing Balance Update:</strong> {{ $r['closing'] }}<br>
        <strong>Bill Summary Update:</strong> {{ $r['bill'] }}
        @if(!empty($r['errors']))
            <br><small>@foreach($r['errors'] as $e){{ $e }}<br>@endforeach</small>
        @endif
    </div>
@endif
@if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('society.uploadMemberDetails') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Member Details Update</label>
            <input type="file" name="member_csv" class="form-control" accept=".csv" required>
        </div>
        <p style="color:#c00; margin-bottom:12px;"><strong>Note:</strong> Please update only the Principal Balance, Interest Balance, and Tax Balance fields.</p>
        <button type="submit" class="btn btn-success">Upload CSV</button>
    </form>
</div>
@endsection
