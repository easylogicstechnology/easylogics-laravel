@extends('layouts.app')
@section('title', 'Member Tariff Upload')
@section('content')
<div class="page-header">
    <h2>Member Tariff Upload / Download</h2>
</div>

<div class="card" style="max-width:600px;">
    <h3 style="font-size:15px; margin-bottom:12px;">Download Tariff CSV</h3>
    <form method="POST" action="{{ route('society.downloadMemberTariffData') }}">
        @csrf
        <div class="form-group">
            <label>Building <span class="required">*</span></label>
            <select name="building_id" class="form-control" required>
                <option value="">Select building</option>
                @foreach($buildings as $bId => $bName)
                <option value="{{ $bId }}">{{ $bName }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Wing Name</label>
            <select name="wing_id" class="form-control">
                <option value="">All Wings</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm" style="background:#5bc0de; color:#fff;">Download CSV</button>
    </form>
</div>

<div class="card" style="max-width:600px; margin-top:16px;">
    <h3 style="font-size:15px; margin-bottom:12px;">Upload Tariff CSV</h3>
    <form method="POST" action="{{ route('society.memberTariffUpload') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Client CSV</label>
            <input type="file" name="member_tariff_csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Upload CSV</button>
    </form>
</div>
@endsection
