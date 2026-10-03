@extends('layouts.app')
@section('title', 'Import Society Payments')
@section('content')
<div class="page-header">
    <h2>Import Society Payments</h2>
    <div>
        <a href="{{ route('society.downloadSampleSocietyPaymentTemplate') }}" class="btn btn-success btn-sm" style="margin-right:6px;">Download Sample Template file</a>
    </div>
</div>

<div class="card" style="max-width:600px;">
    <form method="POST" action="{{ route('society.importSocietyPayments') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Society Bank</label>
            <select name="society_bank" class="form-control">
                <option value="">Select Bank</option>
                @foreach($bankList as $bId => $bName)
                <option value="{{ $bId }}">{{ $bName }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>File</label>
            <input type="file" name="file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-sm" style="background:#5bc0de; color:#fff;">Upload File</button>
    </form>
</div>
@endsection
