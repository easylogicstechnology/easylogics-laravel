@extends('layouts.app')
@section('title', 'Form J')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Form J</h2>
</div>

<div class="card">
    <div class="ar-actions">
        <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_form_j');">Print Friendly</a>
    </div>
</div>

<div class="card" style="overflow-x:auto;">
    <div id="print_form_j">
        <div class="ar-report">
            @include('society.registers._head', ['heading' => 'J FORM'])
            <table>
                <thead>
                    <tr>
                        <th style="width:5%">Sr. No.</th>
                        <th>Member Name</th>
                        <th style="width:8%">Member Reg. No.</th>
                        <th style="width:6%">Age</th>
                        <th style="width:8%">Gender</th>
                        <th>Address</th>
                        <th style="width:8%">Class</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $i => $m)
                    @php
                        $ident = $latest[$m->id] ?? null;
                        $address = trim($m->unit_type) . ', ' . $m->flat_no . (!empty($society['address']) ? ', ' . $society['address'] : '');
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ trim($m->member_prefix . ' ' . $m->member_name) }}</td>
                        <td>{{ $m->id }}</td>
                        <td>{{ $ident ? (int) $ident->age : 0 }}</td>
                        <td>{{ $ident->gender ?? '' }}</td>
                        <td>{{ $address }}</td>
                        <td>{{ !empty($ident->class) ? $ident->class : 'Member' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No members found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/report_print.js') }}"></script>
@endsection
