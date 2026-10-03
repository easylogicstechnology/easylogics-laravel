/*
 * Excel and PDF export for the two-column accounting reports.
 *
 * Balance Sheet (Liabilities|Assets) and Income & Expenditure
 * (Expenditure|Income) are built the same way: an outer table whose single row
 * holds two cells, each containing the real table. Everything here works off
 * that shape, so both reports share one implementation - the alternative is the
 * hand-copied fork this codebase already suffers from elsewhere, where a fix to
 * one copy silently misses the other.
 *
 * Reading the rendered DOM rather than re-querying on the server is deliberate:
 * some totals are filled in by each page's own jQuery after load, and it means
 * the file can never disagree with what the operator is looking at.
 *
 * Living in a .js file also removes a trap: written inline in a .ctp, the xml
 * declaration below opens PHP mode on servers with short_open_tag on, killing
 * the whole view - and only once deployed, since local PHP has it off.
 */
(function (window, document) {
   'use strict';

   var isAmount = function (v) {
      return /^-?[\d,]+(\.\d+)?\s*(Cr|Dr)?$/i.test(String(v).trim()) && /\d/.test(String(v));
   };

   var INNER_TABLES = '.table-wrap1 > table > tbody > tr > td > table';
   // Bank book and cash book are one plain ledger table with no inner tables,
   // so the wrapper itself is the report.
   var SINGLE_TABLE = '.table-wrap1 > table';

   /*
    * The two halves are separate tables, each ending in its own Total row, and
    * they almost never hold the same number of ledger heads - so the two Totals
    * came out at different heights and the sheet read as though one side had
    * been cut short.
    *
    * Padded in the DOM rather than in the PHP because the left table is already
    * written out by the time the right rows exist, so neither side can know the
    * other's length while rendering. Print, the PDF and the spreadsheet all read
    * this same rendered table, so doing it once here covers the screen and all
    * three exports.
    */
   function alignReportTotals(containerId) {
      var source = document.getElementById(containerId);
      if (!source) { return; }
      var tables = source.querySelectorAll(INNER_TABLES);
      if (tables.length !== 2) { return; }

      var bodyRows = function (table) {
         var rows = [], all = table.rows, i;
         for (i = 0; i < all.length; i++) {
            // thead holds the column headings - only ledger rows line up.
            if (all[i].parentNode.tagName.toLowerCase() !== 'thead') { rows.push(all[i]); }
         }
         return rows;
      };

      var left = bodyRows(tables[0]), right = bodyRows(tables[1]);
      var shorter = left.length < right.length ? left : right;
      var gap = Math.abs(left.length - right.length);
      if (!gap || !shorter.length) { return; }

      // Above the Total, not below it, so the Total stays the last line on both
      // sides however many rows get added.
      var totalRow = shorter[shorter.length - 1];
      var columns = totalRow.cells.length, n, c;
      for (n = 0; n < gap; n++) {
         var blank = document.createElement('tr');
         blank.className = 'bs-pad';
         for (c = 0; c < columns; c++) {
            var cell = document.createElement('td');
            cell.innerHTML = '&nbsp;';
            blank.appendChild(cell);
         }
         totalRow.parentNode.insertBefore(blank, totalRow);
      }
   }

   /*
    * Both exports need the same reading of the report - the two halves as plain
    * grids, which rows are headings, where each half starts. Worked out once
    * here so the spreadsheet and the PDF can never drift apart.
    * Returns null and explains itself if the page has nothing to export yet.
    */
   function reportLayout(containerId) {
      var source = document.getElementById(containerId);
      if (!source) {
         alert('Report not loaded yet - press Show first.');
         return null;
      }
      /*
       * The first table on the page is only a layout shell. Passing that shell to
       * table_to_book flattened each nested table into one spreadsheet cell on
       * row 1, then repeated both further down, stacked rather than side by side.
       * So read the two inner tables and lay them out as the page shows them.
       */
      var inner = source.querySelectorAll(INNER_TABLES);
      if (!inner.length) { inner = source.querySelectorAll(SINGLE_TABLE); }
      if (!inner.length) {
         alert('Nothing to export - press Show to load the report.');
         return null;
      }

      // How many rows the table gives to column headings. The two-column reports
      // use two; a plain ledger uses one. Assuming two would embolden the first
      // line of data on the bank book and push it into the PDF's header.
      var headRows = 0;
      for (var t = 0; t < inner.length; t++) {
         var found = inner[t].querySelectorAll('thead tr').length;
         if (found > headRows) { headRows = found; }
      }
      if (!headRows) { headRows = 1; }

      var grids = [], widths = [], height = 0, i, r, c, w;
      for (i = 0; i < inner.length; i++) {
         // header:1 keeps it as plain rows; colspan/rowspan are already expanded.
         // raw:true stops SheetJS re-reading cell text - without it "01/04/2025"
         // was parsed as a date and rewritten "1/4/25" (and day/month could swap
         // for days <= 12), and "500.00" lost its ".00". Every cell is written as
         // a string anyway, so keeping the text verbatim is what the report needs.
         var grid = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(inner[i], {raw: true}),
                                             {header: 1, raw: false, defval: ''});
         for (w = 0, r = 0; r < grid.length; r++) {
            if (grid[r].length > w) { w = grid[r].length; }
         }
         grids.push(grid);
         widths.push(w);
         if (grid.length > height) { height = grid.length; }
      }

      // sheet_to_json leaves a hole where a cell was empty, and String(undefined)
      // is "undefined" - which would read as content in the tests below.
      var cellAt = function (half, row, col) {
         var cells = grids[half][row];
         var value = cells ? cells[col] : '';
         if (value === undefined || value === null) { return ''; }
         // The spacer rows are &nbsp;, which arrives here as U+00A0 and would
         // land in the spreadsheet as a cell holding an invisible character.
         // Escaped, not typed literally - it is unreadable in a diff.
         value = String(value).replace(/\u00a0/g, ' ');
         return value.trim() === '' ? '' : value;
      };

      // Lay the halves out left to right with one blank column between them, so
      // the right half starts in the same column even where a row on the left is
      // short. halfOf maps an output column back to its half, -1 being a spacer.
      var halfOf = [], offsetOf = [], columns = 0;
      for (i = 0; i < grids.length; i++) {
         if (i) { halfOf.push(-1); columns++; }
         offsetOf.push(columns);
         for (w = 0; w < widths[i]; w++) { halfOf.push(i); columns++; }
      }

      // Bold is decided per half rather than per row: the two sides carry their
      // section headings on different rows, so a single verdict for the whole row
      // would either bold figures on one side or miss a heading on the other.
      var boldAt = [];
      for (i = 0; i < grids.length; i++) {
         boldAt.push([]);
         for (r = 0; r < height; r++) {
            var filled = 0, label = '', total = false;
            for (c = 0; c < widths[i]; c++) {
               var cell = cellAt(i, r, c).trim();
               if (cell !== '') {
                  filled++;
                  if (label === '') { label = cell; }
                  // Any column, not just the first - the total row opens with
                  // last year's figure and carries the word in column two.
                  if (/^total$/i.test(cell)) { total = true; }
               }
            }
            // The heading rows are bold; a row carrying just a label is a section
            // heading; the closing total is bold as well.
            boldAt[i].push(r < headRows || total || (filled === 1 && !isAmount(label)));
         }
      }

      // Society name, registration number, address, report title - the same
      // block the page prints above the table. Flagged bold or not.
      var banner = [];
      var h5 = source.querySelector('h5');
      if (h5 && h5.textContent.trim()) { banner.push([h5.textContent.trim(), true]); }
      var addr = source.querySelectorAll('.report-address-heading');
      for (i = 0; i < addr.length; i++) {
         if (addr[i].textContent.trim()) { banner.push([addr[i].textContent.trim(), false]); }
      }
      // All of them, not just the first: a report often has a second .report-bill
      // under the title - the balance sheet's date range, the bank book's bank
      // name - and those belong in the exported banner too.
      var titles = source.querySelectorAll('.report-bill');
      for (i = 0; i < titles.length; i++) {
         if (titles[i].textContent.trim()) { banner.push([titles[i].textContent.trim(), true]); }
      }

      // The block the report is signed off under. Print carries it because it
      // copies the whole container; the exports build their own output, so they
      // read it here and re-lay it out, otherwise the sheet and the PDF stop at
      // the table and are not the same document.
      var signature = [];
      var sigTable = source.querySelector('.report-signature');
      if (sigTable) {
         for (r = 0; r < sigTable.rows.length; r++) {
            var sigCells = [], sigRow = sigTable.rows[r];
            for (c = 0; c < sigRow.cells.length; c++) {
               var sigCell = sigRow.cells[c];
               // Alignment is taken from the cell so the exports place each name
               // where the page does - Chairman sits right of its column, the
               // other two centred - instead of always centring.
               var align = window.getComputedStyle ? window.getComputedStyle(sigCell).textAlign : '';
               if (align !== 'left' && align !== 'right') { align = 'center'; }
               // Share of the row this cell occupies. Spacing between the names
               // is set by the column widths on the page, so carrying the ratio
               // is what keeps the exports matching it rather than splitting the
               // width evenly and ignoring the layout.
               var sigWidth = sigTable.offsetWidth
                            ? (sigCell.offsetWidth / sigTable.offsetWidth) : 0;
               sigCells.push({
                  text: sigCell.textContent.replace(/\u00a0/g, ' ').trim(),
                  align: align,
                  width: sigWidth
               });
            }
            signature.push(sigCells);
         }
      }

      return {
         grids: grids, widths: widths, height: height, columns: columns,
         halfOf: halfOf, offsetOf: offsetOf, boldAt: boldAt,
         cellAt: cellAt, banner: banner, headRows: headRows, signature: signature,
         // The value at an output column, '' for the spacer between the halves.
         valueAt: function (row, col) {
            var half = halfOf[col];
            return half < 0 ? '' : cellAt(half, row, col - offsetOf[half]);
         },
         boldFor: function (row, col) {
            var half = halfOf[col];
            return half >= 0 && boldAt[half][row];
         }
      };
   }

   /*
    * Written as SpreadsheetML 2003 rather than a real .xlsx.
    *
    * The free build of SheetJS cannot write cell styles at all - borders, bold
    * and alignment are paid features - and these reports are read as documents,
    * so the ruled layout matters. An HTML table sent as ms-excel was tried first
    * and Excel dropped most of the CSS; SpreadsheetML states every border and
    * alignment outright, so what Excel shows is what is written here. Excel may
    * warn that the extension does not match the contents; that is expected,
    * answer Yes.
    */
   function exportReportExcel(containerId, sheetName, fileName) {
      var layout = reportLayout(containerId);
      if (!layout) { return; }
      var columns = layout.columns, height = layout.height, banner = layout.banner;
      var halfOf = layout.halfOf, offsetOf = layout.offsetOf;
      var i, r, c;

      var xml = function (v) {
         return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                         .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      };
      var box = '<Borders>' +
                ['Left', 'Right', 'Top', 'Bottom'].map(function (side) {
                   return '<Border ss:Position="' + side +
                          '" ss:LineStyle="Continuous" ss:Weight="1"/>';
                }).join('') +
                '</Borders>';

      var out = [];
      out.push('<?xml version="1.0"?>');
      out.push('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' +
               ' xmlns:o="urn:schemas-microsoft-com:office:office"' +
               ' xmlns:x="urn:schemas-microsoft-com:office:excel"' +
               ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">');
      out.push('<Styles>');
      out.push('<Style ss:ID="Default" ss:Name="Normal"><Font ss:FontName="Calibri" ss:Size="11"/>' +
               '<Alignment ss:Vertical="Center"/></Style>');
      out.push('<Style ss:ID="ttl"><Font ss:FontName="Calibri" ss:Size="14" ss:Bold="1"/>' +
               '<Alignment ss:Horizontal="Center"/></Style>');
      out.push('<Style ss:ID="sub"><Alignment ss:Horizontal="Center"/></Style>');
      out.push('<Style ss:ID="hd"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>' +
               '<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' + box + '</Style>');
      out.push('<Style ss:ID="tx">' + box + '</Style>');
      out.push('<Style ss:ID="nm"><Alignment ss:Horizontal="Right"/>' + box + '</Style>');
      out.push('<Style ss:ID="bt"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>' + box + '</Style>');
      out.push('<Style ss:ID="bn"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>' +
               '<Alignment ss:Horizontal="Right"/>' + box + '</Style>');
      out.push('<Style ss:ID="gap"/>');
      // Borderless, for the signature block, which follows the page's alignment.
      out.push('<Style ss:ID="sgl"><Alignment ss:Horizontal="Left"/></Style>');
      out.push('<Style ss:ID="sgr"><Alignment ss:Horizontal="Right"/></Style>');
      out.push('</Styles>');
      out.push('<Worksheet ss:Name="' + xml(sheetName || 'Report') + '"><Table>');

      // Column two of each half carries the ledger head and needs the room; the
      // rest hold figures, and the spacer between the halves stays narrow.
      for (c = 0; c < columns; c++) {
         var within = halfOf[c] < 0 ? -1 : c - offsetOf[halfOf[c]];
         out.push('<Column ss:AutoFitWidth="0" ss:Width="' +
                  (within < 0 ? 18 : (within === 1 ? 200 : 95)) + '"/>');
      }

      var row = function (cells) { out.push('<Row>' + cells.join('') + '</Row>'); };
      var cell = function (style, value, across) {
         return '<Cell ss:StyleID="' + style + '"' +
                (across ? ' ss:MergeAcross="' + across + '"' : '') +
                '><Data ss:Type="String">' + xml(value) + '</Data></Cell>';
      };

      for (i = 0; i < banner.length; i++) {
         row([cell(banner[i][1] ? 'ttl' : 'sub', banner[i][0], columns - 1)]);
      }
      row([]);

      for (r = 0; r < height; r++) {
         var cells = [];
         for (c = 0; c < columns; c++) {
            if (halfOf[c] < 0) { cells.push(cell('gap', '')); continue; }
            var value = layout.valueAt(r, c);
            var bold = layout.boldFor(r, c);
            // Values stay strings: as numbers Excel would drop the Cr/Dr suffix
            // that says which side of the ledger a figure sits on.
            var style = r < layout.headRows ? 'hd'
                      : isAmount(value) ? (bold ? 'bn' : 'nm')
                      : (bold ? 'bt' : 'tx');
            cells.push(cell(style, value));
         }
         row(cells);
      }

      // The signature block, laid back out across the sheet's columns: a row with
      // one entry spans the width, the three signatories take a third each.
      if (layout.signature.length) {
         row([]);
         for (r = 0; r < layout.signature.length; r++) {
            var sig = layout.signature[r], sigOut = [];
            if (!sig.length) { row([]); continue; }
            var used = 0;
            for (c = 0; c < sig.length; c++) {
               // Columns in proportion to the page, so the names land at the same
               // relative spacing. The last one takes the remainder, so the row
               // always spans the sheet exactly however the rounding falls.
               var share = sig[c].width || (1 / sig.length);
               var across = (c === sig.length - 1)
                          ? (columns - used)
                          : Math.max(1, Math.round(columns * share));
               if (across < 1) { across = 1; }
               used += across;
               var sigStyle = sig.length === 1 ? 'ttl'
                            : sig[c].align === 'right' ? 'sgr'
                            : sig[c].align === 'left' ? 'sgl' : 'sub';
               sigOut.push(cell(sigStyle, sig[c].text, across - 1));
            }
            row(sigOut);
         }
      }

      out.push('</Table>');
      out.push('<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">' +
               '<PageSetup><Layout x:Orientation="Landscape"/>' +
               '<PageMargins x:Left="0.4" x:Right="0.4" x:Top="0.5" x:Bottom="0.5"/></PageSetup>' +
               '<FitToPage/><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight>' +
               '<ValidPrinterInfo/><Scale>100</Scale></Print>' +
               '</WorksheetOptions>');
      out.push('</Worksheet></Workbook>');

      var link = document.createElement('a');
      link.href = URL.createObjectURL(new Blob(['\ufeff' + out.join('')],
                                               {type: 'application/vnd.ms-excel'}));
      link.download = fileName || 'Report.xls';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(link.href);
   }

   /*
    * "Export To PDF" used to call printAllReports, which only opened the print
    * dialog and left the operator to pick "Save as PDF" - so the button did not
    * do what it said. This writes the file directly.
    *
    * autoTable draws real text rather than a screenshot of the page, so the
    * figures stay selectable and searchable in the PDF and the file stays small.
    */
   function exportReportPdf(containerId, fileName) {
      if (!window.jspdf || !window.jspdf.jsPDF) {
         alert('PDF library did not load - check the connection and reload the page.');
         return;
      }
      var layout = reportLayout(containerId);
      if (!layout) { return; }

      var doc = new window.jspdf.jsPDF({orientation: 'landscape', unit: 'mm', format: 'a4'});
      var pageWidth = doc.internal.pageSize.getWidth();
      var y = 12;

      for (var b = 0; b < layout.banner.length; b++) {
         doc.setFont('helvetica', layout.banner[b][1] ? 'bold' : 'normal');
         doc.setFontSize(layout.banner[b][1] ? 12 : 9);
         doc.text(String(layout.banner[b][0]), pageWidth / 2, y, {align: 'center'});
         y += layout.banner[b][1] ? 6 : 5;
      }

      var rowValues = function (r) {
         var cells = [];
         for (var c = 0; c < layout.columns; c++) { cells.push(layout.valueAt(r, c)); }
         return cells;
      };
      var head = [], body = [], r;
      for (r = 0; r < layout.headRows && r < layout.height; r++) { head.push(rowValues(r)); }
      for (r = layout.headRows; r < layout.height; r++) { body.push(rowValues(r)); }

      // Which column holds the long text differs by report - the balance sheet's
      // is column 1 (the ledger head), the bank book's is the particular - so it
      // is detected from the data rather than hardcoded. A column whose body is
      // mostly amounts gets a fixed narrow width and right alignment; the rest
      // share the remaining width, so the description column is the one that grows.
      var columnStyles = {};
      for (var c = 0; c < layout.columns; c++) {
         if (layout.halfOf[c] < 0) { columnStyles[c] = {cellWidth: 4}; continue; }
         var amounts = 0, filled = 0;
         for (r = layout.headRows; r < layout.height; r++) {
            var v = layout.valueAt(r, c).trim();
            if (v !== '') { filled++; if (isAmount(v)) { amounts++; } }
         }
         columnStyles[c] = (filled > 0 && amounts >= filled * 0.6)
                         ? {cellWidth: 22, halign: 'right'}
                         : {cellWidth: 'auto'};
      }

      doc.autoTable({
         head: head,
         body: body,
         startY: y + 1,
         theme: 'grid',
         margin: {left: 8, right: 8},
         styles: {fontSize: 7, cellPadding: 1.2, lineColor: [0, 0, 0], lineWidth: 0.1,
                  textColor: [0, 0, 0], overflow: 'linebreak'},
         headStyles: {fillColor: [235, 235, 235], textColor: [0, 0, 0],
                      fontStyle: 'bold', halign: 'center'},
         columnStyles: columnStyles,
         didParseCell: function (data) {
            if (data.section !== 'body') { return; }
            // Offset because the body starts after the heading rows.
            if (layout.boldFor(data.row.index + layout.headRows, data.column.index)) {
               data.cell.styles.fontStyle = 'bold';
            }
            if (layout.halfOf[data.column.index] < 0) {
               // The spacer between the halves is a gap, not a column.
               data.cell.styles.lineWidth = 0;
            }
         }
      });

      // The signature block, under the table: one entry is centred across the
      // page, three are spread a third each so they sit under their own space.
      if (layout.signature.length) {
         var sigY = (doc.lastAutoTable ? doc.lastAutoTable.finalY : y) + 10;
         var left = 8, usable = pageWidth - 16;
         doc.setFontSize(9);
         for (r = 0; r < layout.signature.length; r++) {
            var line = layout.signature[r], filled = '';
            for (c = 0; c < line.length; c++) { filled += line[c].text; }
            // Blank rows in the block are spacing, not text.
            if (!line.length || !filled.trim()) { sigY += 6; continue; }
            if (sigY > doc.internal.pageSize.getHeight() - 15) {
               doc.addPage(); sigY = 20;
            }
            doc.setFont('helvetica', line.length === 1 ? 'bold' : 'normal');
            var slotStart = left;
            for (c = 0; c < line.length; c++) {
               // The page's own column share, falling back to an even split if the
               // block was not laid out (hidden, so no measurable width).
               var slot = usable * (line[c].width || (1 / line.length));
               if (String(line[c].text).trim()) {
                  // Same alignment the page uses, within this entry's share.
                  var sigX = line[c].align === 'right' ? slotStart + slot
                           : line[c].align === 'left' ? slotStart
                           : slotStart + slot / 2;
                  doc.text(String(line[c].text), sigX, sigY, {align: line[c].align});
               }
               slotStart += slot;
            }
            sigY += 6;
         }
      }

      doc.save(fileName || 'Report.pdf');
   }

   // Runs before any export, which only read the table on click.
   function alignOnReady(containerId) {
      var run = function () { alignReportTotals(containerId); };
      if (window.jQuery) { jQuery(document).ready(run); }
      else if (document.addEventListener) { document.addEventListener('DOMContentLoaded', run); }
   }

   window.reportExport = {
      layout: reportLayout,
      excel: exportReportExcel,
      pdf: exportReportPdf,
      alignTotals: alignReportTotals,
      alignOnReady: alignOnReady,
      isAmount: isAmount
   };
}(window, document));
