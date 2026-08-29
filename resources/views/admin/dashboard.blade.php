@extends('layouts.app')

@section('title', 'Admin Dashboard - EasyLogics')

@section('content')
<h2 style="font-size: 20px; margin-bottom: 20px; color: #2c3e50;">Admin Dashboard</h2>

<div class="grid-4">
    <div class="card">
        <h3>Total Societies</h3>
        <div class="value">{{ number_format($countSocieties) }}</div>
    </div>
    <div class="card">
        <h3>Active Members</h3>
        <div class="value">{{ number_format($allSocietysMemberCount) }}</div>
    </div>
    <div class="card">
        <h3>Total Resellers</h3>
        <div class="value">{{ number_format($countReseller) }}</div>
    </div>
    <div class="card">
        <h3>Reseller-Societies</h3>
        <div class="value">{{ number_format($resellerSocietiesCount) }}</div>
    </div>
    <div class="card">
        <h3>Current Year Societies</h3>
        <div class="value">{{ number_format($currentYearSocietiesLists) }}</div>
    </div>
    <div class="card">
        <h3>Dropped Societies</h3>
        <div class="value">{{ number_format($droppedSocietiesLists) }}</div>
    </div>
</div>

<div class="grid-2" style="margin-top: 16px;">
    <div class="card">
        <h3>Recent Societies</h3>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Society Name</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allSocietiesLists as $user)
                    @foreach($user->societies as $society)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td>{{ $society->society_name }}</td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="2">No societies found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Reseller Assignments</h3>
        <table>
            <thead>
                <tr>
                    <th>Reseller</th>
                    <th>Assigned Societies</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resellerAssignSocietys as $reseller)
                <tr>
                    <td>{{ $reseller['resellerData']['username'] ?? '' }}</td>
                    <td>
                        @if(isset($reseller['assignSocietiesData']))
                            @foreach($reseller['assignSocietiesData'] as $s)
                                {{ $s['society_name'] }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                    <tr><td colspan="2">No resellers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
