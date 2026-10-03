<?php

namespace App\Services\Reports;

use App\Support\ReportUtil;
use App\Support\TextSelect;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * "Export to Excel" of the half-page bills (CakePHP excels_report/download_account_bill_half): one block per bill on a
 * single sheet - society heading, unit line, "Bill For" line, the tariff lines and the closing lines - laid out on the
 * twelve columns A-L the CakePHP sheet uses.
 *
 * As in CakePHP, the date / month / unit / bill-no ranges of this export all sit in one OR group (a bill matching ANY of
 * them is listed), the "Bill For" cell holds the month NUMBER, the label "Bill Due Date" carries the bill date, and the
 * Total / Add : Interest / Less : Adjustment / arrears lines all repeat the sum of the tariff lines.
 */
class BillHalfPageExcel
{
    public function __construct(private int $societyId)
    {
    }

    /** @param array $in the filter in the names of the screen filter (month, month_to, bill_date, bill_date_to, flat_no.., bill_no..) */
    public function download(array $in, string $filename)
    {
        $bills = $this->bills($in);
        $spreadsheet = $this->sheet($bills);

        return response()->streamDownload(function () use ($spreadsheet) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function bills(array $in): array
    {
        $empty = fn ($v) => BillPrintReport::phpEmpty($v);
        $q = DB::table('member_bill_summaries as b')
            ->leftJoin('societies as s', 's.id', '=', 'b.society_id')
            ->leftJoin('members as m', 'm.id', '=', 'b.member_id')
            ->select(array_merge(
                TextSelect::columns('member_bill_summaries', 'b'),
                TextSelect::columns('societies', 's', 's__'),
                TextSelect::columns('members', 'm', 'm__')
            ))
            ->where('b.society_id', $this->societyId);

        $or = [];
        $date = $in['bill_date'] ?? null;
        $dateTo = $in['bill_date_to'] ?? null;
        if (!$empty($date) && !$empty($dateTo)) {
            $or[] = ['b.bill_generated_date', [$date, $dateTo]];
        } elseif (!$empty($dateTo)) {
            $q->where('b.bill_generated_date', $dateTo);
        } elseif (!$empty($date)) {
            $q->where('b.bill_generated_date', $date);
        }
        $month = $in['month'] ?? null;
        $monthTo = $in['month_to'] ?? null;
        if (!$empty($month) && !$empty($monthTo)) {
            $or[] = ['b.month', [$month, $monthTo]];
        } elseif (!$empty($month)) {
            $q->where('b.month', $month);
        } elseif (!$empty($monthTo)) {
            $q->where('b.month', $monthTo);
        }
        $flat = $in['flat_no'] ?? null;
        $flatTo = $in['flat_no_to'] ?? null;
        if (!$empty($flat) && !$empty($flatTo)) {
            $or[] = ['b.flat_no', [$flat, $flatTo]];
        } elseif (!$empty($flatTo)) {
            $q->where('b.flat_no', $flatTo);
        } elseif (!$empty($flat)) {
            $q->where('b.flat_no', $flat);
        }
        $billNo = $in['bill_no'] ?? null;
        $billNoTo = $in['bill_no_to'] ?? null;
        if (!$empty($billNo) && !$empty($billNoTo)) {
            $or[] = ['b.bill_no', [$billNo, $billNoTo]];
        } elseif (!$empty($billNo)) {
            $q->where('b.bill_no', $billNo);
        } elseif (!$empty($billNoTo)) {
            $q->where('b.bill_no', $billNoTo);
        }
        if ($or) {
            $q->where(function ($w) use ($or) {
                foreach ($or as [$col, $range]) {
                    $w->orWhereBetween($col, $range);
                }
            });
        }

        $tariffs = new BillPrintReport($this->societyId);
        $rows = [];
        foreach ($q->orderBy('b.bill_no')->get() as $r) {
            $row = ['MemberBillSummary' => [], 'Society' => [], 'Member' => [], 'MemberTariff' => []];
            foreach ((array) $r as $col => $v) {
                if (str_starts_with($col, 's__')) {
                    $row['Society'][substr($col, 3)] = $v;
                } elseif (str_starts_with($col, 'm__')) {
                    $row['Member'][substr($col, 3)] = $v;
                } else {
                    $row['MemberBillSummary'][$col] = $v;
                }
            }
            foreach ($tariffs->tariffLines($row['MemberBillSummary']) as $k => $t) {
                $row['MemberTariff'][$k] = ['amount' => $t['amount'] ?? '', 'title' => $t['title'] ?? ''];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function sheet(array $bills): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bill half page report');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['H' => 30, 'B' => 20, 'I' => 10, 'J' => 20, 'K' => 20, 'L' => 10, 'M' => 10] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $put = function (int $row, array $cells, bool $bold = false, int $size = 10) use ($sheet) {
            foreach ($cells as $i => $v) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . $row, (string) $v, DataType::TYPE_STRING);
            }
            $range = 'A' . $row . ':L' . $row;
            $sheet->getStyle($range)->getFont()->setBold($bold)->setSize($size);
        };
        $row = 1;
        foreach ($bills as $b) {
            $s = $b['MemberBillSummary'];
            $society = $b['Society'];
            $m = $b['Member'];
            $row++; // the blank line above every bill
            $cell = fn (array $at) => array_replace(array_fill(0, 12, ' '), $at);

            $put($row++, $cell([7 => ReportUtil::plain($society['society_name'] ?? '')]), true, 15);
            $put($row++, $cell([6 => 'Registration No :', 7 => ReportUtil::plain($society['registration_no'] ?? '')]));
            $put($row++, $cell([7 => 'Address :', 8 => ReportUtil::plain($society['address'] ?? '')]));
            $put($row++, $cell([0 => 'Unit No: ', 1 => $m['flat_no'] ?? '', 4 => ' Unit Area : ', 5 => $m['area'] ?? '', 7 => 'Unit type :', 8 => $m['unit_type'] ?? '',
                10 => 'Bill Due Date : ', 11 => $s['bill_generated_date'] ?? '']));
            $put($row++, $cell([0 => 'Bill For :', 1 => $s['month'] ?? '', 7 => ' Unit Area : ', 8 => $m['area'] ?? '', 10 => 'Due Date :', 11 => $s['bill_due_date'] ?? '']));

            $sheet->mergeCells('B' . $row . ':K' . $row);
            $put($row, $cell([0 => 'Sr.', 1 => 'PARTICULAR OF CHARGES', 11 => 'Amount']), true);
            $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
            $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d6d6c2');
            $row++;

            $sum = 0;
            $n = 1;
            foreach ($b['MemberTariff'] as $t) {
                $sum += $t['amount'];
                $sheet->mergeCells('B' . $row . ':K' . $row);
                $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00');
                $put($row++, $cell([0 => $n++, 1 => $t['title'], 11 => $t['amount']]));
            }

            $words = ReportUtil::convertToWords(abs($s['balance_amount']));
            foreach ([['Total', true], ['Add : Interest', true], ['Less : Adjustment', true], ['Principal Arrears', false], ['Interest Arrears', false]] as [$label, $mergeJK]) {
                $sheet->mergeCells('A' . $row . ':I' . $row);
                if ($mergeJK) {
                    $sheet->mergeCells('J' . $row . ':K' . $row);
                }
                $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00');
                $put($row++, $cell([9 => $label, 11 => "$sum"]));
            }
            $sheet->mergeCells('A' . $row . ':I' . $row);
            $sheet->mergeCells('J' . $row . ':K' . $row);
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('0.00');
            $put($row, $cell([0 => $words, 9 => 'Total Due & Payable Amount', 11 => $s['balance_amount']]));
            $sheet->getStyle('J' . $row . ':K' . $row)->getFont()->setBold(true);
            $row++;
            $row++; // and one below
        }

        return $spreadsheet;
    }
}
