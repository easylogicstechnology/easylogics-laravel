{{-- Report heading of the registers (Cake: society name, reg. no + date, financial year, report title) --}}
<div class="row">
    <h5><b>{{ isset($society['society_name']) ? ucwords($society['society_name']) : '' }}</b></h5>
    <div class="report-address-heading">
        {{ $society['registration_no'] ?? '' }}{{ !empty($society['registration_date']) ? ', ' . date('d/m/Y', strtotime($society['registration_date'])) : '' }}
    </div>
    <div class="report-address-heading">{{ $yearFrom }} To {{ $yearTo }}</div>
    <br>
    <div class="report-bill"><b>{{ $heading }}</b></div>
</div>
