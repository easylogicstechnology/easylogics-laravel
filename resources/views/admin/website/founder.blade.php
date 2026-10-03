@extends('layouts.app')

@section('title', 'Manage Founder - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Manage Founder Section (Homepage)</h2>
</div>

<div class="card" style="max-width: 700px;">
    <form method="POST" action="{{ route('admin.website.founder') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Current Photo</label>
            @if($founder && $founder->image_path)
                <img src="{{ asset($founder->image_path) }}" alt="Founder" style="max-width:140px;border-radius:8px;border:1px solid #ddd;display:block;margin-bottom:10px;">
            @else
                <p class="text-muted">No photo uploaded yet.</p>
            @endif
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <p class="text-muted" style="font-size:12px; margin-top:4px;">JPG, PNG or WebP. Max 2 MB. Uploading a new photo replaces the old one.</p>
        </div>

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" class="form-control" name="name" maxlength="150" required value="{{ old('name', $founder->name ?? '') }}">
        </div>

        <div class="form-group">
            <label>Designation</label>
            <input type="text" class="form-control" name="designation" maxlength="150" placeholder="e.g. Founder & CEO" value="{{ old('designation', $founder->designation ?? '') }}">
        </div>

        <div class="form-group">
            <label>Short Description</label>
            <textarea class="form-control" name="description" rows="4" maxlength="600">{{ old('description', $founder->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label style="font-weight:normal">
                <input type="checkbox" name="display_status" value="1" {{ (!$founder || (int)$founder->display_status === 1) ? 'checked' : '' }}>
                Display this section on the homepage
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
