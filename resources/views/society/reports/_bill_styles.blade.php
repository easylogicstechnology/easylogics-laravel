{{-- Look of the printed bills (CakePHP custom_society.css: .bill-*, .print-gst-bill ..., and its @media print rules). Also copied into the print frame by printBills(). --}}
<style data-bill-css>
    .bill-report { font-family: Roboto, Helvetica, Arial, sans-serif; font-size: 13px; color: #333; line-height: 1.43; }
    .bill-report .row { margin-left: -15px; margin-right: -15px; }
    .bill-report .row:after, .bill-report .clearfix:after { content: " "; display: table; clear: both; }
    .bill-report [class*="col-md-"], .bill-report [class*="col-sm-"], .bill-report [class*="col-xs-"] { float: left; position: relative; min-height: 1px; padding-left: 15px; padding-right: 15px; }
    .bill-report .col-xs-3 { width: 25%; } .bill-report .col-xs-4 { width: 33.333%; } .bill-report .col-xs-5 { width: 41.666%; }
    .bill-report .col-xs-7 { width: 58.333%; } .bill-report .col-xs-8 { width: 66.666%; } .bill-report .col-xs-9 { width: 75%; } .bill-report .col-xs-12 { width: 100%; }
    .bill-report .col-md-3 { width: 25%; } .bill-report .col-md-4 { width: 33.333%; } .bill-report .col-md-5 { width: 41.666%; } .bill-report .col-md-6 { width: 50%; }
    .bill-report .col-md-7 { width: 58.333%; } .bill-report .col-md-8 { width: 66.666%; } .bill-report .col-md-9 { width: 75%; } .bill-report .col-md-12 { width: 100%; }
    .bill-report .col-sm-3 { width: 25%; } .bill-report .col-sm-4 { width: 33.333%; } .bill-report .col-sm-5 { width: 41.666%; }
    .bill-report h5 { font-size: 16px; font-weight: 600; margin: 4px 0; text-align: center; }
    .bill-report .text-center { text-align: center; }
    .bill-report .text-right { text-align: right; }
    .bill-report .pull-right { float: right; }
    .bill-report .font-weight-bold { font-weight: bold; }
    .bill-report .font-weight-600 { font-weight: 600; color: #333; }
    .bill-report .address-heading { text-align: center; font-size: 13px; }
    .bill-report .bill { text-align: center; font-size: 16px; color: #000; }
    .bill-report .simple-border { border: 1px solid #cfcfcf; }
    .bill-report .simple-border-dotted { border: 1px dashed #cfcfcf; }
    .bill-report .bill-outer-border { border: 1px solid #c8c8c8; display: block; overflow: hidden; height: auto; }
    .bill-report .bill-member-details-section { padding: 10px; }
    .bill-report .bill-padding-10 { padding: 10px; }
    .bill-report .receipt-title { text-align: center; font-size: 16px; margin: 15px; color: #000; }
    .bill-report .special-field-text { font-style: italic; font-weight: bold; text-align: center; }
    .bill-report table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .bill-report .table-bordered { border: 1px solid #ddd; }
    .bill-report .table-bordered th, .bill-report .table-bordered td { border: 1px solid #ddd; }
    .bill-report .padding-th-none-zero > thead > tr > th { padding: 0 !important; }
    .bill-report .padding-td-none-zero > tbody > tr > td { padding: 0 !important; color: #333; }
    .bill-report .table-wrap, .bill-report .table-responsive1, .bill-report .table-responsive { overflow: visible; }
    .bill-report .bill-half-page, .bill-report .bill-full-page, .bill-report .print-gst-bill, .bill-report .print-int-with-gst-bill { margin-bottom: 16px; }
    .bill-report ul { margin: 0 0 0 20px; padding: 0; }

    @page { margin: 0 25px; }
    @media print {
        .bill-report .bill-half-page { page-break-inside: avoid; font-size: 10px; line-height: 1.15; padding: 8px !important; margin: 0; }
        .bill-report .bill-half-page .bill-outer-border { overflow: visible; }
        .bill-report .bill-half-page:nth-of-type(2n):not(:last-child) { page-break-after: always; }
        .bill-report .bill-half-page h5 { margin: 2px 0; font-size: 12px; }
        .bill-report .bill-half-page .address-heading { font-size: 9px; line-height: 1.2; }
        .bill-report .bill-half-page .bill { font-size: 12px; margin: 3px 0; line-height: 1.2; }
        .bill-report .bill-half-page .bill-member-details-section { padding: 3px 6px; line-height: 1.3; }
        .bill-report .bill-half-page table td, .bill-report .bill-half-page table th { line-height: 1.15 !important; font-size: 10px !important; }
        .bill-report .bill-half-page .special-field-text { font-size: 9px; }
        .bill-report .bill-half-page br { line-height: 1; }
        .bill-report .bill-full-page { page-break-after: always; font-size: 12px; padding: 20px !important; margin: 0; }
        .bill-report .print-gst-bill { page-break-after: always; padding: 20px !important; font-size: 12px !important; margin: 0; }
        .bill-report .print-int-with-gst-bill { page-break-after: always; font-size: 12px; padding: 20px !important; margin: 0; }
        .bill-report .hide-in-print { display: none; }
        .bill-report .print-custom-table-border td, .bill-report .print-custom-table-border th {
            border-top: 1px solid #515151 !important; border-right: 1px solid #515151 !important;
            border-left: 1px solid #515151 !important; border-bottom: 1px solid #cfcfcf !important;
        }
        .bill-report .bill-outer-border { border: 1px solid #515151 !important; display: block; overflow: hidden; height: auto; }
        .bill-report .padding-th-none-zero > thead > tr > th { border-bottom: 1px solid #515151 !important; }
        .bill-report .print-custom-table-border tfoot td { border-bottom: 1px solid #515151 !important; }
        tr { page-break-inside: avoid; }
    }
</style>
<style data-bill-css>
    /* member bill print (society_bills/print_member_bills): .society-print-bill / .society-print-bill-half of custom_society.css */
    .bill-report .society-print-bill, .bill-report .society-print-bill-half { margin-bottom: 16px; }
    .bill-report .text-right { text-align: right; }
    @media print {
        .bill-report .society-print-bill { page-break-after: always; font-size: 12px; padding: 20px !important; margin: 0; }
        .bill-report .society-print-bill-half { page-break-inside: avoid; font-size: 12px; padding: 20px !important; margin: 0; }
        .bill-report .society-print-bill-half .bill-outer-border { overflow: visible; }
        .bill-report .society-print-bill-half:nth-of-type(2n):not(:last-child) { page-break-after: always; }
        .bill-report .bill-padding-5 { padding: 5px; }
    }
    .bill-report .bill-padding-5 { padding: 5px; }
</style>
