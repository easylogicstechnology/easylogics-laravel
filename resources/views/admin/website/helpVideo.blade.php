@extends('layouts.app')

@section('title', 'Manage Help Video - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Manage Help Video (Reseller Help Page)</h2>
</div>

<div class="card" style="max-width: 700px;">
    <div class="form-group">
        <label>Current Video</label>
        @if($video && $video->video_path)
            <video src="{{ asset($video->video_path) }}" controls style="width:100%; max-width:480px; display:block; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;"></video>
            <form method="POST" action="{{ route('admin.website.helpVideo.destroy') }}" onsubmit="return confirm('Remove this video?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Remove Video</button>
            </form>
        @else
            <p class="text-muted">No video uploaded yet.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.website.helpVideo') }}" enctype="multipart/form-data" style="margin-top:16px;">
        @csrf

        <div class="form-group">
            <label>Upload New Video</label>
            <input type="file" name="video" accept="video/mp4,video/webm,video/ogg">
            <p class="text-muted" style="font-size:12px; margin-top:4px;">MP4, WebM or OGG. Max 50 MB. Uploading a new video replaces the old one.</p>
        </div>

        <div class="form-group">
            <label>Title (optional)</label>
            <input type="text" class="form-control" name="title" maxlength="150" placeholder="e.g. How to use EasyLogics" value="{{ old('title', $video->title ?? '') }}">
        </div>

        <div class="form-group">
            <label style="font-weight:normal">
                <input type="checkbox" name="display_status" value="1" {{ (!$video || (int)$video->display_status === 1) ? 'checked' : '' }}>
                Show this video on the Reseller Help page
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
