/*
 * "Print Friendly" for the account reports: prints just the report block. Same approach as the
 * CakePHP printAllReports(): the page body is swapped for the report, printed, then restored.
 * orientation is optional ('landscape' for wide reports).
 */
function printReport(elementId, orientation) {
    var el = document.getElementById(elementId);
    if (!el) { return; }
    var restore = document.body.innerHTML;
    document.body.innerHTML = '<div id="content">' + el.innerHTML + '</div>';
    var style = document.createElement('style');
    style.type = 'text/css';
    style.innerText = '@page { margin: 15mm 10mm 5mm 10mm;' + (orientation ? ' size: ' + orientation + ';' : '') + ' }'
        + ' body { background: #fff; } table { width: 100%; border-collapse: collapse; }'
        + ' th, td { border: 1px solid #999; padding: 3px 6px; font-size: 12px; } .text-right { text-align: right; }';
    document.head.appendChild(style);
    window.print();
    document.head.removeChild(style);
    document.body.innerHTML = restore;
}
