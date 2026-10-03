@extends('layouts.app')
@section('title', $title ?? 'Member List')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Member List</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <div class="ar-actions">
        @include('society.reports._actions', ['printId' => 'print_member_list', 'file' => 'MemberList', 'sheet' => 'Member List'])
    </div>
</div>

<div class="card">
    <div id="print_member_list">
        <div class="ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">List of Member</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:2%">Sr.</th>
                        <th style="width:6%">Unit No.</th>
                        <th>Member Name</th>
                        <th>Associate Member</th>
                        <th>Occupant</th>
                        <th>Phone No.</th>
                        <th style="width:8%">Area</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $i => $m)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $m->flat_no }}</td>
                            <td>{{ $m->member_name }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ $m->member_phone }}</td>
                            <td class="text-center">{{ $m->area_text }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
