@extends('layouts.app')

@section('title', 'Society Complaints - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Society Complaints</h2>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('add-complaint-modal').style.display='flex'">+ Log New Complaint</button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
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
                <td>{{ $c->society_id }}</td>
                <td>{{ $c->society_name }}</td>
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
                        <span style="background:#d1ecf1; color:#0c5460; padding:2px 8px; border-radius:10px; font-size:12px;">Admin says fixed</span>
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
                        <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('resolve-modal-{{ $c->id }}').style.display='flex'">I fixed it</button>
                    @elseif((int)$c->status === 1)
                        <form method="POST" action="{{ route('reseller.complaints.confirm', $c->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Confirm Fixed</button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('reject-modal-{{ $c->id }}').style.display='flex'">Not Fixed</button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-muted">No complaints logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Log New Complaint modal -->
<div class="modal-overlay" id="add-complaint-modal" style="display:none;">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header-bar">
            <span>Log New Complaint</span>
            <span class="modal-close" onclick="document.getElementById('add-complaint-modal').style.display='none'">&times;</span>
        </div>
        <form method="POST" action="{{ route('reseller.complaints') }}">
            @csrf
            <div class="modal-body-content">
                <div class="form-group">
                    <label>Society <span class="required">*</span></label>
                    <select class="form-control" name="society_id" required>
                        <option value="">-- Select Society --</option>
                        @foreach($societyList as $sid => $sname)
                            <option value="{{ $sid }}">{{ $sname }} (ID: {{ $sid }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Complaint Type <span class="required">*</span></label>
                    <input type="text" class="form-control" name="complaint_type" maxlength="150" placeholder="e.g. Billing issue, Login problem" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" name="description" rows="3" maxlength="500"></textarea>
                </div>
                <div class="form-group">
                    <label>Complaint Date</label>
                    <input type="date" class="form-control" name="complaint_date" value="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div style="padding:0 15px 15px; text-align:right;">
                <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('add-complaint-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Log Complaint</button>
            </div>
        </form>
    </div>
</div>

@foreach($complaints as $c)
    @if((int)$c->status === 0)
    <div class="modal-overlay" id="resolve-modal-{{ $c->id }}" style="display:none;">
        <div class="modal-box" style="max-width:420px;">
            <div class="modal-header-bar">
                <span>Resolve Complaint</span>
                <span class="modal-close" onclick="document.getElementById('resolve-modal-{{ $c->id }}').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="{{ route('reseller.complaints.resolve', $c->id) }}">
                @csrf
                <div class="modal-body-content">
                    <div class="form-group">
                        <label>Resolution Notes</label>
                        <textarea class="form-control" name="resolution" rows="3" maxlength="500" placeholder="What was done to resolve this?"></textarea>
                    </div>
                </div>
                <div style="padding:0 15px 15px; text-align:right;">
                    <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('resolve-modal-{{ $c->id }}').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark Resolved</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @if((int)$c->status === 1)
    <div class="modal-overlay" id="reject-modal-{{ $c->id }}" style="display:none;">
        <div class="modal-box" style="max-width:420px;">
            <div class="modal-header-bar">
                <span>Issue Not Actually Fixed</span>
                <span class="modal-close" onclick="document.getElementById('reject-modal-{{ $c->id }}').style.display='none'">&times;</span>
            </div>
            <form method="POST" action="{{ route('reseller.complaints.reject', $c->id) }}">
                @csrf
                <div class="modal-body-content">
                    <div class="form-group">
                        <label>What's still wrong?</label>
                        <textarea class="form-control" name="reject_reason" rows="3" maxlength="500" placeholder="Tell Admin what's still not working"></textarea>
                    </div>
                </div>
                <div style="padding:0 15px 15px; text-align:right;">
                    <button type="button" class="btn" style="background:#eee;" onclick="document.getElementById('reject-modal-{{ $c->id }}').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reopen Complaint</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endsection
