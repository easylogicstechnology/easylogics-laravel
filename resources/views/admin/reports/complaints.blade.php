@extends('layouts.app')

@section('title', 'Complaint Register - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Complaint Register (All Resellers)</h2>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Reseller</th>
                <th>Society ID</th>
                <th>Society</th>
                <th>Type</th>
                <th>Complaint Date</th>
                <th>Status</th>
                <th>Resolution</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($complaints as $c)
            <tr>
                <td>{{ $c->reseller->username ?? '-' }}</td>
                <td>{{ $c->society_id }}</td>
                <td>{{ $c->society->society_name ?? '-' }}</td>
                <td>
                    {{ $c->complaint_type }}
                    @if($c->description)
                        <div style="font-size:11px;color:#999;">{{ $c->description }}</div>
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($c->complaint_date)->format('d-M-Y') }}</td>
                <td>
                    @if((int)$c->status === 2)
                        <span style="background:#d4edda; color:#155724; padding:2px 8px; border-radius:10px; font-size:12px;">Solved</span>
                    @elseif((int)$c->status === 1)
                        <span style="background:#d1ecf1; color:#0c5460; padding:2px 8px; border-radius:10px; font-size:12px;">Awaiting reseller confirm</span>
                    @else
                        <span style="background:#fff3cd; color:#856404; padding:2px 8px; border-radius:10px; font-size:12px;">Pending</span>
                    @endif
                </td>
                <td>
                    @if($c->resolution)
                        {{ $c->resolution }}
                        @if($c->resolved_date)
                            <div style="font-size:11px;color:#999;">on {{ \Carbon\Carbon::parse($c->resolved_date)->format('d-M-Y') }}</div>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if((int)$c->status === 0)
                        <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('solve-modal-{{ $c->id }}').style.display='flex'">Solve</button>
                    @elseif((int)$c->status === 1)
                        <div style="font-size:11px;color:#999;margin-bottom:4px;">Waiting for reseller to confirm/reject</div>
                        <form method="POST" action="{{ route('admin.reports.complaints.close', $c->id) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Solve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.reports.complaints.reopen', $c->id) }}" style="display:inline" onsubmit="return confirm('Send this complaint back to Pending?');">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">Pending</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.reports.complaints.reopen', $c->id) }}" style="display:inline" onsubmit="return confirm('Send this solved complaint back to Pending?');">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">Pending</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-muted">No complaints logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@foreach($complaints as $c)
    @if((int)$c->status === 0)
    <div class="modal-overlay" id="solve-modal-{{ $c->id }}" style="display:none;">
        <div class="modal-box" style="max-width:420px;">
            <div class="modal-header-bar">
                <span>Solve Complaint</span>
                <span class="modal-close" onclick="document.getElementById('solve-modal-{{ $c->id }}').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="{{ route('admin.reports.complaints.solve', $c->id) }}">
                @csrf
                <div class="modal-body-content">
                    <div class="form-group">
                        <label>Resolution Notes</label>
                        <textarea class="form-control" name="resolution" rows="3" maxlength="500" placeholder="What was done to solve this?"></textarea>
                    </div>
                </div>
                <div style="padding:0 15px 15px; text-align:right;">
                    <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('solve-modal-{{ $c->id }}').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-success">Solve</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endsection
