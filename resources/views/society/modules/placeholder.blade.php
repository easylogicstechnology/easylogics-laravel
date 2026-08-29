@extends('layouts.app')
@section('title', $title ?? 'Module')
@section('content')
<div class="page-header">
    <h2>{{ $title ?? 'Module' }}</h2>
</div>

<div class="card">
    <h3>{{ $title ?? 'Module' }}</h3>
    <p style="color:#999;">This module is under development.</p>
</div>
@endsection
