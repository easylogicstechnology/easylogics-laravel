@extends('layouts.app')
@section('title', 'Statutory Audit Report')
@section('content')
@include('society.reports._styles')
<?php
    $r = $report ?? [];
    $val = fn ($k, $default = '') => old($k, $r[$k] ?? $default);
    $field = function ($name, $label, $type = 'text', $default = '') use ($val) {
        return '<div class="ar-field"><label>' . e($label) . '</label><input type="' . $type . '" name="' . $name . '" value="' . e($val($name, $default)) . '" style="min-width:220px"></div>';
    };
?>
<div class="page-header">
    <h2>Statutory Audit Report <small>&mdash; {{ \App\Support\ReportUtil::plain($society->society_name ?? '') }} ({{ $financialYear->year ?? '' }})</small></h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

@if(session('audit_flash'))
    <div class="card"><div class="ar-note" style="font-size:14px;color:#222">{{ session('audit_flash') }}</div></div>
@endif

<div class="card">
    @if(empty($report) || $report['status'] == 'draft')
        <div style="padding:10px;background:#fff8e1;border:1px solid #f0d78c;border-radius:4px;margin-bottom:12px">
            <strong>Auditor Review Pending.</strong>
            Fields below marked "auto-suggested" are pre-filled with the most common answer or the ledger's
            opening-balance figures. They are a starting point only &mdash; verify every one against this
            society's actual books and current Balance Sheet, then click <em>Finalize</em> once confirmed.
            A PDF cannot be treated as a submittable statutory report until it is finalized.
        </div>
    @else
        <div style="padding:10px;background:#e8f5e9;border:1px solid #b7dfb9;border-radius:4px;margin-bottom:12px">This report has been marked <strong>Finalized</strong> by an auditor.</div>
    @endif
    @if(!empty($report))
        <p>
            <a class="btn btn-default" target="_blank" href="{{ route('society.reports.auditReportPdf', [$report['id'], 'mr']) }}">Download PDF (Marathi)</a>
            <a class="btn btn-default" target="_blank" href="{{ route('society.reports.auditReportPdf', [$report['id'], 'en']) }}">Download PDF (English)</a>
        </p>
    @endif

    <form method="post" action="{{ route('society.reports.auditReportYear', $financialYear->id) }}" class="ar-form" autocomplete="off">
        @csrf
        <h4>Auditor Details</h4>
        <div class="ar-row">
            {!! $field('auditor_name', 'Auditor Name') !!}
            {!! $field('auditor_qualification', 'Qualification') !!}
            {!! $field('auditor_panel_no', 'Panel No.') !!}
            {!! $field('auditor_address', 'Auditor Address') !!}
        </div>
        <div class="ar-row">
            {!! $field('registrar_address', 'Deputy Registrar Address (letter of submission)') !!}
            {!! $field('audit_memo_sr_no', 'Audit Memo Sr. No.') !!}
        </div>

        <h4>Audit Period &amp; Report Dates</h4>
        <div class="ar-row">
            {!! $field('audit_start_date', 'Audit Start Date', 'date') !!}
            {!! $field('audit_end_date', 'Audit End Date', 'date') !!}
            {!! $field('audit_report_date', 'Report Submission Date', 'date') !!}
            {!! $field('report_place', 'Place') !!}
        </div>
        <div class="ar-row">
            <div class="ar-field">
                <label>Inspection Class</label>
                <select name="inspection_class">
                    <option value=""></option>
                    @foreach(['मागील तीन वर्षांचा' => 'Previous 3 years', 'चालू वर्षाचा' => 'Current year'] as $k => $t)
                        <option value="{{ $k }}" {{ $val('inspection_class') == $k ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Audit Grade</label>
                <select name="audit_grade">
                    <option value=""></option>
                    @foreach(['अ' => 'A', 'ब' => 'B', 'क' => 'C', 'ड' => 'D'] as $k => $t)
                        <option value="{{ $k }}" {{ $val('audit_grade') == $k ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            {!! $field('previous_auditor_name', 'Previous Auditor') !!}
            {!! $field('previous_audit_report_date', 'Previous Audit Report Date', 'date') !!}
        </div>

        <h4>Audit Fee</h4>
        <div class="ar-row">
            {!! $field('audit_fee_amount', 'Audit Fee (Rs.)', 'text', $memberCount * 100) !!}
            {!! $field('audit_fee_period', 'Fee Period', 'text', $financialYear->year ?? '') !!}
            {!! $field('tds_amount', 'TDS (Rs.)') !!}
            {!! $field('other_charges_amount', 'Other Charges (Rs.)') !!}
        </div>

        <h4>Financial Summary &mdash; auto-suggested from ledger opening balances, verify against Balance Sheet</h4>
        <div class="ar-row">
            @foreach($financialFields as $f)
                <div class="ar-field">
                    <label>{{ $f['en'] }} / {{ $f['mr'] }}</label>
                    <input type="text" name="financial_override[{{ $f['code'] }}]" value="{{ $financialSnapshot[$f['code']] ?? 0 }}">
                </div>
            @endforeach
        </div>
        <p class="ar-note">These figures are informational (recomputed fresh from opening balances on every save). To correct a figure, adjust the ledger head itself; this form does not override the ledger.</p>
        <p class="ar-note">In every checklist section below, leave a box empty to use the standard default shown as its placeholder text in the downloaded PDF (in whichever language you download) - type here only to override it with this society's actual finding.</p>

        <h4>Part A &mdash; Specific Report (Notes on Accounts)</h4>
        @include('society.reports._audit_question_loop', ['questions' => $partAQuestions, 'fieldPrefix' => 'part_a_answers', 'savedAnswers' => $partAAnswers])
        <h4>Part B &mdash; Management Details</h4>
        @include('society.reports._audit_question_loop', ['questions' => $partBFields, 'fieldPrefix' => 'part_b_extra', 'savedAnswers' => $partBExtra])
        <p class="ar-note">Membership count, committee meeting count, AGM date and the financial figures for Part B come from the app's own data (shown above / elsewhere on this form) and don't need re-entering here.</p>
        <h4>Part C &mdash; Audit Objections &amp; General Remarks</h4>
        @include('society.reports._audit_question_loop', ['questions' => $partCQuestions, 'fieldPrefix' => 'part_c_compliance', 'savedAnswers' => $partCCompliance])
        <h4>Nine-Point Statement (Section 81(2))</h4>
        @include('society.reports._audit_question_loop', ['questions' => $ninePoints, 'fieldPrefix' => 'nine_point_remarks', 'savedAnswers' => $ninePointRemarks])
        <h4>Statutory Report u/s 81(2) &mdash; Schedules I to V</h4>
        @include('society.reports._audit_question_loop', ['questions' => $scheduleQuestions, 'fieldPrefix' => 'schedule_remarks', 'savedAnswers' => $scheduleRemarks])
        <h4>Form No. 1 (Part I) &mdash; Statutory Checklist</h4>
        @include('society.reports._audit_question_loop', ['questions' => $part1Questions, 'fieldPrefix' => 'part1_answers', 'savedAnswers' => $part1Answers])
        <h4>Form No. 28 (Part II)</h4>
        @include('society.reports._audit_question_loop', ['questions' => $form28Questions, 'fieldPrefix' => 'form28_answers', 'savedAnswers' => $form28Answers])

        <h4>General Instructions &amp; Remarks</h4>
        <textarea name="general_remarks" rows="6" style="width:100%;padding:6px;border:1px solid #ccd;border-radius:4px">{{ $val('general_remarks') }}</textarea>
        <h4>Law / Bye-law Violation Notes</h4>
        <textarea name="law_violation_notes" rows="4" style="width:100%;padding:6px;border:1px solid #ccd;border-radius:4px">{{ $val('law_violation_notes') }}</textarea>

        <div class="ar-actions" style="margin-top:20px">
            <button type="submit" name="save_draft" class="btn btn-default">Save Draft</button>
            <button type="submit" name="finalize" value="1" class="btn btn-success">Save &amp; Finalize</button>
        </div>
    </form>
</div>
@endsection
