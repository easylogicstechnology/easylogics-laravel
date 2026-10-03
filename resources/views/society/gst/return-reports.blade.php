@extends('layouts.app')
@section('title', 'GST Return-Ready Reports')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Return-Ready Reports</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-3"><select name="financial_year_id" class="form-control">@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-3"><input type="text" name="period_value" class="form-control" placeholder="Month (blank = whole FY)" value="{{ $periodValue }}"></div>
                <div class="col-md-3"><button type="submit" class="btn btn-info">Show</button></div>
            </form>
            <div class="clearfix"></div>

            <ul class="nav nav-tabs" id="gstTabs">
                <li class="active"><a href="#b2b" data-tab="b2b">A. B2B</a></li>
                <li><a href="#b2c" data-tab="b2c">B. B2C</a></li>
                <li><a href="#cdn" data-tab="cdn">C/D. Notes</a></li>
                <li><a href="#adv" data-tab="adv">E/F. Advances</a></li>
                <li><a href="#exempt" data-tab="exempt">G. Exempt/Nil/Non-GST</a></li>
                <li><a href="#hsn" data-tab="hsn">H. HSN Summary</a></li>
                <li><a href="#liab" data-tab="liab">I. Liability</a></li>
                <li><a href="#pay" data-tab="pay">J. Payment</a></li>
                <li><a href="#summary" data-tab="summary">K. Return Summary</a></li>
            </ul>
            <div class="tab-content" style="padding-top:15px;">

                <div class="tab-pane active" id="b2b">
                    <h6>A. Outward Supplies - B2B</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Bill No</th><th>Date</th><th>Member</th><th>GSTIN</th><th>HSN</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th></tr></thead>
                            <tbody>
                            @foreach($b2bRows as $m)
                                <tr><td>{{ $m->bill_no }}</td><td>{{ $m->bill_generated_date }}</td><td>{{ $m->member_name }}</td><td>{{ $m->GSTIN ?? '' }}</td><td>{{ $m->hsn_code ?? '' }}</td><td class="text-right">{{ number_format($m->computed['taxable_value'], 2) }}</td><td class="text-right">{{ number_format($m->cgst_total, 2) }}</td><td class="text-right">{{ number_format($m->sgst_total, 2) }}</td><td class="text-right">{{ number_format($m->igst_total, 2) }}</td></tr>
                            @endforeach
                            @if($b2bRows->isEmpty())<tr><td colspan="9" class="text-center">No B2B supplies.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="b2c">
                    <h6>B. Outward Supplies - B2C</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Bill No</th><th>Date</th><th>Member</th><th>HSN</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th></tr></thead>
                            <tbody>
                            @foreach($b2cRows as $m)
                                <tr><td>{{ $m->bill_no }}</td><td>{{ $m->bill_generated_date }}</td><td>{{ $m->member_name }}</td><td>{{ $m->hsn_code ?? '' }}</td><td class="text-right">{{ number_format($m->computed['taxable_value'], 2) }}</td><td class="text-right">{{ number_format($m->cgst_total, 2) }}</td><td class="text-right">{{ number_format($m->sgst_total, 2) }}</td><td class="text-right">{{ number_format($m->igst_total, 2) }}</td></tr>
                            @endforeach
                            @if($b2cRows->isEmpty())<tr><td colspan="8" class="text-center">No B2C supplies.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="cdn">
                    <h6>C. Credit Notes</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Note No</th><th>Date</th><th>Party</th><th>Original Invoice</th><th class="text-right">Taxable</th><th class="text-right">GST</th></tr></thead>
                            <tbody>
                            @foreach($creditNotes as $n)
                                <tr><td>{{ $n->note_no }}</td><td>{{ $n->note_date }}</td><td>{{ $n->party_name }}</td><td>{{ $n->original_invoice_no }}</td><td class="text-right">{{ number_format($n->taxable_amount, 2) }}</td><td class="text-right">{{ number_format($n->total_gst, 2) }}</td></tr>
                            @endforeach
                            @if($creditNotes->isEmpty())<tr><td colspan="6" class="text-center">No credit notes.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                    <h6 style="margin-top:20px;">D. Debit Notes</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Note No</th><th>Date</th><th>Party</th><th>Original Invoice</th><th class="text-right">Taxable</th><th class="text-right">GST</th></tr></thead>
                            <tbody>
                            @foreach($debitNotes as $n)
                                <tr><td>{{ $n->note_no }}</td><td>{{ $n->note_date }}</td><td>{{ $n->party_name }}</td><td>{{ $n->original_invoice_no }}</td><td class="text-right">{{ number_format($n->taxable_amount, 2) }}</td><td class="text-right">{{ number_format($n->total_gst, 2) }}</td></tr>
                            @endforeach
                            @if($debitNotes->isEmpty())<tr><td colspan="6" class="text-center">No debit notes.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="adv">
                    <h6>E. Advances Received (Unadjusted/Partial)</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Receipt No</th><th>Date</th><th>Party</th><th class="text-right">Amount</th><th class="text-right">GST</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($advancesReceived as $r)
                                <tr><td>{{ $r->receipt_no }}</td><td>{{ $r->receipt_date }}</td><td>{{ $r->party_name }}</td><td class="text-right">{{ number_format($r->amount_received, 2) }}</td><td class="text-right">{{ number_format($r->total_gst, 2) }}</td><td>{{ $r->adjustment_status }}</td></tr>
                            @endforeach
                            @if($advancesReceived->isEmpty())<tr><td colspan="6" class="text-center">No outstanding advances.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                    <h6 style="margin-top:20px;">F. Advance Adjustments</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Date</th><th>Against</th><th>Invoice No</th><th class="text-right">Adjusted Amount</th><th class="text-right">GST</th></tr></thead>
                            <tbody>
                            @foreach($advanceAdjustments as $a)
                                <tr><td>{{ $a->adjustment_date }}</td><td>{{ $a->adjusted_against_type }}</td><td>{{ $a->adjusted_against_invoice_no }}</td><td class="text-right">{{ number_format($a->adjusted_amount, 2) }}</td><td class="text-right">{{ number_format($a->adjusted_gst_amount, 2) }}</td></tr>
                            @endforeach
                            @if($advanceAdjustments->isEmpty())<tr><td colspan="5" class="text-center">No adjustments.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="exempt">
                    <h6>G. Exempt / Nil Rated / Non-GST HSN Codes in Use</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Code</th><th>Description</th><th>Taxability</th></tr></thead>
                            <tbody>
                            @foreach($exemptHsn as $h)
                                <tr><td>{{ $h->code }}</td><td>{{ $h->description }}</td><td>{{ $h->taxability_type }}</td></tr>
                            @endforeach
                            @if($exemptHsn->isEmpty())<tr><td colspan="3" class="text-center">No exempt/nil/non-GST HSN codes configured.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="hsn">
                    <h6>H. HSN/SAC Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>HSN/SAC</th><th>Description</th><th class="text-right">Invoice Count</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total GST</th></tr></thead>
                            <tbody>
                            @foreach($hsnSummary as $row)
                                <tr><td>{{ $row->code ?? 'Unclassified' }}</td><td>{{ $row->description ?? '' }}</td><td class="text-right">{{ $row->invoice_count }}</td><td class="text-right">{{ number_format($row->cgst, 2) }}</td><td class="text-right">{{ number_format($row->sgst, 2) }}</td><td class="text-right">{{ number_format($row->igst, 2) }}</td><td class="text-right">{{ number_format($row->total_gst, 2) }}</td></tr>
                            @endforeach
                            @if($hsnSummary->isEmpty())<tr><td colspan="7" class="text-center">No data.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="liab">
                    <h6>I. GST Liability</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead><tr><th></th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th></tr></thead>
                            <tbody>
                            <tr><td>Output GST</td><td class="text-right">{{ number_format($liability['outputCgst'], 2) }}</td><td class="text-right">{{ number_format($liability['outputSgst'], 2) }}</td><td class="text-right">{{ number_format($liability['outputIgst'], 2) }}</td></tr>
                            <tr><td>Eligible ITC</td><td class="text-right">{{ number_format($liability['eligibleCgst'], 2) }}</td><td class="text-right">{{ number_format($liability['eligibleSgst'], 2) }}</td><td class="text-right">{{ number_format($liability['eligibleIgst'], 2) }}</td></tr>
                            <tr class="text-bold"><td>Net Payable</td><td class="text-right">{{ number_format($liability['netCgst'], 2) }}</td><td class="text-right">{{ number_format($liability['netSgst'], 2) }}</td><td class="text-right">{{ number_format($liability['netIgst'], 2) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Net GST Payable (incl. interest/late fee/adjustments): <b>Rs. {{ number_format($liability['netGstPayable'], 2) }}</b></p>
                </div>

                <div class="tab-pane" id="pay">
                    <h6>J. GST Payment</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Period</th><th class="text-right">Total Payable</th><th>Challan No</th><th>Payment Date</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($payments as $p)
                                <tr><td>{{ $p->period_type }}: {{ $p->period_value }}</td><td class="text-right">{{ number_format($p->total_payable, 2) }}</td><td>{{ $p->challan_number }}</td><td>{{ $p->payment_date }}</td><td>{{ $p->payment_status }}</td></tr>
                            @endforeach
                            @if($payments->isEmpty())<tr><td colspan="5" class="text-center">No payments for this period.</td></tr>@endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane" id="summary">
                    <h6>K. Return Summary</h6>
                    <table class="table table-bordered">
                        <tbody>
                        <tr><td>B2B Invoices</td><td class="text-right">{{ count($b2bRows) }}</td></tr>
                        <tr><td>B2C Invoices</td><td class="text-right">{{ count($b2cRows) }}</td></tr>
                        <tr><td>Credit Notes</td><td class="text-right">{{ count($creditNotes) }}</td></tr>
                        <tr><td>Debit Notes</td><td class="text-right">{{ count($debitNotes) }}</td></tr>
                        <tr><td>Outstanding Advances</td><td class="text-right">{{ count($advancesReceived) }}</td></tr>
                        <tr class="text-bold"><td>Net GST Payable</td><td class="text-right">Rs. {{ number_format($liability['netGstPayable'], 2) }}</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('#gstTabs a[data-tab]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
        ev.preventDefault();
        document.querySelectorAll('#gstTabs li').forEach(function (li) { li.classList.remove('active'); });
        document.querySelectorAll('.gst-page .tab-content > .tab-pane').forEach(function (p) { p.classList.remove('active'); });
        a.parentNode.classList.add('active');
        document.getElementById(a.getAttribute('data-tab')).classList.add('active');
    });
});
</script>
@endsection
