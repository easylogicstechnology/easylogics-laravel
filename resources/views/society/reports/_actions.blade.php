{{-- Print / PDF / Excel buttons of a report. $printId = id of the block to print, $file = download name --}}
<a href="javascript:void(0);" class="btn btn-success" onclick="printReport('{{ $printId }}'{{ !empty($orientation) ? ", '" . $orientation . "'" : '' }});">Print Friendly</a>
<a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.pdf('{{ $printId }}', '{{ $file }}.pdf');">Export to PDF</a>
<a href="javascript:void(0);" class="btn btn-success" onclick="reportExport.excel('{{ $printId }}', '{{ $sheet ?? $file }}', '{{ $file }}.xls');">Export to Excel</a>
