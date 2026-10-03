@extends('layouts.app')

@section('title', 'Edit Partner - EasyLogics')

@section('content')
@php
    $isKnownReseller = $resellersList->contains($partner->name);
@endphp
<div class="page-header">
    <h2>Edit Partner</h2>
</div>

<div class="card" style="max-width: 700px;">
    <form method="POST" action="{{ route('admin.website.partners.update', $partner->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Current Photo</label>
            @if($partner->image_path)
                <img src="{{ asset($partner->image_path) }}" alt="Partner" style="max-width:120px;border-radius:50%;border:1px solid #ddd;display:block;margin-bottom:10px;">
            @else
                <p class="text-muted">No photo uploaded yet.</p>
            @endif
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <p class="text-muted" style="font-size:12px; margin-top:4px;">JPG, PNG or WebP. Max 2 MB. Uploading a new photo replaces the old one.</p>
        </div>

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <select class="form-control" name="name" id="partnerNameSelect" onchange="document.getElementById('partnerNameCustomWrap').style.display=(this.value==='__custom__')?'block':'none';" required>
                <option value="">-- Select active Reseller --</option>
                @foreach($resellersList as $uname)
                    <option value="{{ $uname }}" {{ $uname === $partner->name ? 'selected' : '' }}>{{ $uname }}</option>
                @endforeach
                <option value="__custom__" {{ (!$isKnownReseller && $partner->name !== '') ? 'selected' : '' }}>Other (type manually)</option>
            </select>
            <div id="partnerNameCustomWrap" style="{{ (!$isKnownReseller && $partner->name !== '') ? '' : 'display:none;' }}margin-top:8px">
                <input type="text" class="form-control" name="name_custom" maxlength="150" placeholder="Enter name" value="{{ !$isKnownReseller ? $partner->name : '' }}">
            </div>
        </div>

        <div class="form-group">
            <label>Location</label>
            <input type="text" class="form-control" name="location" maxlength="100" value="{{ old('location', $partner->location) }}">
        </div>

        <div class="form-group">
            <label>Company</label>
            <input type="text" class="form-control" name="company" maxlength="150" value="{{ old('company', $partner->company) }}">
        </div>

        <div class="form-group">
            <label>Short Description</label>
            <textarea class="form-control" name="description" rows="3" maxlength="400">{{ old('description', $partner->description) }}</textarea>
        </div>

        <div class="form-group">
            <label style="font-weight:normal">
                <input type="checkbox" name="display_status" value="1" {{ (int)$partner->display_status === 1 ? 'checked' : '' }}>
                Display this card on the homepage
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('admin.website.partners') }}" class="btn" style="background:#eee;">Cancel</a>
    </form>
</div>
@endsection
