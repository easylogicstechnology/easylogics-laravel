@extends('layouts.app')

@section('title', 'Admin Dashboard - EasyLogics')

@section('content')
<h2 style="font-size: 20px; margin-bottom: 20px; color: #2c3e50;">Admin Dashboard</h2>

<div class="stat-cards-row">
    <div class="stat-card stat-card-red">
        <div class="stat-card-top">
            <div>
                <div class="stat-card-value">{{ number_format($countReseller) }}</div>
                <div class="stat-card-label">No. Of Resellers</div>
            </div>
            <i class="fa fa-users stat-card-icon"></i>
        </div>
        <div class="stat-card-sub">Societies assign to Reseller -&gt; <strong>{{ number_format($resellerSocietiesCount) }}</strong></div>
    </div>
    <div class="stat-card stat-card-yellow">
        <div class="stat-card-top">
            <div>
                <div class="stat-card-value">{{ number_format($countSocieties) }}</div>
                <div class="stat-card-label">No. of Societies</div>
            </div>
            <i class="fa fa-building stat-card-icon"></i>
        </div>
        <div class="stat-card-sub">Members -&gt; <strong>{{ number_format($allSocietysMemberCount) }}</strong></div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-top">
            <div>
                <div class="stat-card-value">{{ number_format($currentYearSocietiesLists) }}</div>
                <div class="stat-card-label">New Societies This Year</div>
            </div>
            <i class="fa fa-institution stat-card-icon"></i>
        </div>
    </div>
    <div class="stat-card stat-card-blue">
        <div class="stat-card-top">
            <div>
                <div class="stat-card-value">{{ number_format($droppedSocietiesLists) }}</div>
                <div class="stat-card-label">Societies Dropped</div>
            </div>
            <i class="fa fa-times-circle stat-card-icon"></i>
        </div>
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

@include('admin._bill_monitor')
@endsection
