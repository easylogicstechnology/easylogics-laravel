@include('society.reports._scripts')
<script>
/*
 * Print of the bill sheets (CakePHP printAllReports on the bill block): the bills go into a print frame together with
 * the bill styles, so the page menus stay out of the print and the half-page / full-page breaks apply.
 */
function printBills(elementId) {
    var el = document.getElementById(elementId);
    if (!el) { return; }
    var css = '';
    document.querySelectorAll('style[data-bill-css]').forEach(function (s) { css += s.innerHTML; });
    var frame = document.createElement('iframe');
    frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(frame);
    var doc = frame.contentWindow.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Bills</title><style>' + css + '</style></head><body><div class="bill-report">' + el.innerHTML + '</div></body></html>');
    doc.close();
    setTimeout(function () {
        frame.contentWindow.focus();
        frame.contentWindow.print();
        setTimeout(function () { document.body.removeChild(frame); }, 1000);
    }, 300);
}
</script>
