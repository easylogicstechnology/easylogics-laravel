@extends('layouts.app')
@section('title', 'Form I')
@section('content')
@include('society.reports._styles')
@php
    $d = fn ($v) => !empty($v) ? date('d/m/Y', strtotime($v)) : '';
@endphp
<div class="page-header">
    <h2>Form I</h2>
</div>

<div class="card ar-form">
    <form method="POST" action="{{ route('society.formI') }}">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Society Flat/Shop *</label>
                <select name="member_id" class="ar-wide">
                    <option value="">Select Flat No</option>
                    @foreach($memberFlatLists as $memberId => $label)
                    <option value="{{ $memberId }}" {{ $selectedMemberId == $memberId ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">View</button>
                @if(!empty($selectedMemberId))
                <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_form_i');">Print Friendly</a>
                @endif
            </div>
        </div>
    </form>
</div>

@if(!empty($selectedMemberId) && !empty($selectedMember))
@php
    $latestEntry = $history->isNotEmpty() ? $history->last() : null;
    $firstEntry = $history->isNotEmpty() ? $history->first() : null;
    $count = $history->count();

    $nominationRows = [];
    $associateRows = [];
    $cessationRows = [];
    $sharesHeldRows = [];
    $sharesTransferredRows = [];
    $lastMemberHistoryRows = [];

    foreach ($history as $index => $entry) {
        if (!empty($entry->nominee_name)) {
            $nominationRows[] = $entry;
        }
        if (!empty($entry->associate_member)) {
            $associateRows[] = $entry;
        }
        if (!empty($entry->date_of_cessation_membership)) {
            $cessationRows[] = $entry;
        }
        if (!empty($entry->no_of_shares)) {
            if (!empty($entry->shares_status) && (stripos($entry->shares_status, 'transfer') !== false || stripos($entry->shares_status, 'surrender') !== false)) {
                $sharesTransferredRows[] = $entry;
            } else {
                $sharesHeldRows[] = $entry;
            }
        }
        if ($index < $count - 1) {
            $lastMemberHistoryRows[] = $entry;
        }
    }
@endphp
<div class="card" style="overflow-x:auto;">
    <div id="print_form_i">
        <div class="ar-report">
            @include('society.registers._head', ['heading' => 'I FORM'])

            <table>
                <tr>
                    <td style="width:20%"><b>Mem.Reg.No.</b></td>
                    <td style="width:30%">{{ $selectedMember->id }}</td>
                    <td style="width:20%"><b>Ledge Code No.</b></td>
                    <td style="width:30%"></td>
                </tr>
                <tr>
                    <td><b>Date of Admission</b></td>
                    <td>{{ $d($firstEntry->date_of_admission_1 ?? null) }}</td>
                    <td><b>Date of Entrance Fee</b></td>
                    <td></td>
                </tr>
                <tr>
                    <td><b>Name</b></td>
                    <td>{{ trim($selectedMember->member_prefix . ' ' . $selectedMember->member_name) }}</td>
                    <td><b>Class</b></td>
                    <td>{{ !empty($latestEntry->class) ? $latestEntry->class : 'Member' }}</td>
                </tr>
                <tr>
                    <td><b>Floor</b></td>
                    <td>{{ $selectedMember->floor_no }}</td>
                    <td><b>Wing</b></td>
                    <td>{{ $wingName }}</td>
                </tr>
                <tr>
                    <td><b>Unit Type</b></td>
                    <td>{{ $selectedMember->unit_type }}</td>
                    <td><b>Unit No.</b></td>
                    <td>{{ $selectedMember->flat_no }}</td>
                </tr>
                <tr>
                    <td><b>Date of Birth</b></td>
                    <td>{{ $d($latestEntry->date_of_birth ?? null) }}</td>
                    <td><b>Age</b></td>
                    <td>{{ isset($latestEntry->age) ? (int) $latestEntry->age : 0 }}</td>
                </tr>
                <tr>
                    <td><b>Gender</b></td>
                    <td>{{ $latestEntry->gender ?? '' }}</td>
                    <td><b>Occupation</b></td>
                    <td>{{ $latestEntry->occupation ?? '' }}</td>
                </tr>
                <tr>
                    <td><b>Pan No.</b></td>
                    <td>{{ $latestEntry->pan_no ?? '' }}</td>
                    <td><b>Aadhar No.</b></td>
                    <td></td>
                </tr>
                <tr>
                    <td><b>Mobile No.</b></td>
                    <td>{{ $selectedMember->member_phone }}</td>
                    <td><b>Email ID</b></td>
                    <td>{{ $selectedMember->member_email }}</td>
                </tr>
            </table>

            <div class="report-bill"><b>NOMINATION</b></div>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%">Sr. No.</th>
                        <th>Name</th>
                        <th>Date of Application</th>
                        <th>Date of Approval</th>
                        <th>Address</th>
                        <th>Relationship</th>
                        <th>Percentage</th>
                        <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nominationRows as $i => $entry)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $entry->nominee_name }}</td>
                        <td>{{ $d($entry->nomination_date) }}</td>
                        <td>{{ $d($entry->nomination_date) }}</td>
                        <td>{{ $entry->nominee_address }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No nomination recorded</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="report-bill"><b>ASSOCIATE MEMBER</b></div>
            <table>
                <thead>
                    <tr>
                        <th style="width:5%">Sr. No.</th>
                        <th>Name</th>
                        <th>Date of Application</th>
                        <th>Date of Approval</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($associateRows as $i => $entry)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $entry->associate_member }}</td>
                        <td>{{ $d($entry->date_of_admission_1) }}</td>
                        <td>{{ $d($entry->date_of_admission_2) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No associate member recorded</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="report-bill"><b>CESSATION/TRANSFER</b></div>
            <table>
                <thead>
                    <tr>
                        <th>Date of Application</th>
                        <th>Date of Approval</th>
                        <th>Transferee Name</th>
                        <th>Reason for Sale/Transfer</th>
                        <th>Remarks</th>
                        <th>New Reg. No.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cessationRows as $entry)
                    <tr>
                        <td></td>
                        <td>{{ $d($entry->date_of_cessation_membership) }}</td>
                        <td>{{ $entry->second_member }}</td>
                        <td>{{ $entry->reason_for_cessation }}</td>
                        <td>{{ $entry->remarks }}</td>
                        <td></td>
                        <td>{{ $entry->shares_status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No cessation/transfer recorded</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="report-bill"><b>PARTICULARS OF SHARES HELD</b></div>
            <table>
                <thead>
                    <tr>
                        <th>Certificate Date</th>
                        <th>Certificate No.</th>
                        <th>Shares From</th>
                        <th>Shares To</th>
                        <th>No of Shares</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sharesHeldRows as $entry)
                    <tr>
                        <td>{{ $d($entry->date_of_allotment) }}</td>
                        <td>{{ $entry->certification_no }}</td>
                        <td>{{ $entry->from_share_no }}</td>
                        <td>{{ $entry->to_share_no }}</td>
                        <td>{{ $entry->no_of_shares }}</td>
                        <td>{{ $entry->shares_value }}</td>
                        <td>{{ $entry->shares_status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No shares held recorded</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="report-bill"><b>PARTICULARS OF SHARES TRANSFERRED/SURRENDERED</b></div>
            <table>
                <thead>
                    <tr>
                        <th>Certificate Date</th>
                        <th>Certificate No.</th>
                        <th>Shares From</th>
                        <th>Shares To</th>
                        <th>No of Shares</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sharesTransferredRows as $entry)
                    <tr>
                        <td>{{ $d($entry->date_of_allotment) }}</td>
                        <td>{{ $entry->certification_no }}</td>
                        <td>{{ $entry->from_share_no }}</td>
                        <td>{{ $entry->to_share_no }}</td>
                        <td>{{ $entry->no_of_shares }}</td>
                        <td>{{ $entry->shares_value }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">No shares transferred/surrendered recorded</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="report-bill"><b>LAST MEMBER HISTORY</b></div>
            <table>
                <thead>
                    <tr>
                        <th>Mem. Reg. No.</th>
                        <th>Date of Approval</th>
                        <th>Member Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lastMemberHistoryRows as $entry)
                    <tr>
                        <td>{{ $entry->member_id }}</td>
                        <td>{{ $d($entry->date_of_admission_2) }}</td>
                        <td>{{ !empty($entry->full_name) ? $entry->full_name : $entry->second_member }}</td>
                        <td>{{ $entry->shares_status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">No previous member history</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/report_print.js') }}"></script>
@endsection
