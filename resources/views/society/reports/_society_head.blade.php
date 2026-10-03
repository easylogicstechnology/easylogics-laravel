<div class="row">
    <h5>{{ \App\Support\ReportUtil::plain($society->society_name ?? '') }}</h5>
    <div class="report-address-heading">{{ \App\Support\ReportUtil::plain($society->registration_no ?? '') }}</div>
    <div class="report-address-heading">{{ \App\Support\ReportUtil::plain($society->address ?? '') }}</div>
</div>
