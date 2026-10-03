{{-- Carried over from CakePHP app/View/AccountReports/pdf.ctp lines 1-272: only the helper calls changed --}}
<?php
$isMr = ($lang == 'mr');

if (!function_exists('_auditReportFormatDate')) {
    function _auditReportFormatDate($date) {
        if (empty($date) || $date == '0000-00-00') { return '____________'; }
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('_auditReportQuestionRows')) {
    // Shared renderer for every "Sr.No | Particulars | Remarks" checklist
    // table (Part A, Nine-Point Statement, Schedules I-V, Form 1, Form 28,
    // Part C) - all of them are the same three-column shape over a
    // AuditReportData::*() array plus its saved-answers JSON blob.
    function _auditReportQuestionRows($questions, $answers, $isMr) {
        $html = '';
        foreach ($questions as $i => $q) {
            $srNo = !empty($q['section']) ? e($q['section']) : ($i + 1);
            $label = $isMr ? e($q['mr']) : e($q['en']);
            $answer = !empty($answers[$q['code']]) ? nl2br(e($answers[$q['code']])) : ($isMr ? e($q['default_mr']) : e($q['default_en']));
            $html .= '<tr><td class="col-sr">' . $srNo . '</td><td class="col-particulars">' . $label . '</td><td class="col-remarks">' . $answer . '</td></tr>';
        }
        return $html;
    }
}

// Static chrome around the dynamic question/answer content. AuditReportData
// already carries bilingual question text; this array only needs the labels
// that aren't tied to a specific question.
$reg = $report['SocietyAuditReport'];
$soc = $society['Society'];
$auditPeriod = _auditReportFormatDate($reg['audit_start_date']) . ($isMr ? ' ते ' : ' to ') . _auditReportFormatDate($reg['audit_end_date']);

$t = array(
    'title' => $isMr ? 'सन ' . $financialYear['FinancialYearMaster']['year'] . ' चा वैधानिक लेखापरिक्षण अहवाल' : 'Statutory Audit Report for ' . $financialYear['FinancialYearMaster']['year'],
    'to' => $isMr ? 'प्रति,' : 'To,',
    'registrar_salutation' => $isMr ? 'मा. उपनिबंधक, सहकारी संस्था' : 'Deputy Registrar, Co-operative Societies',
    'submission_to_registrar_subject' => $isMr ? 'विषय :- सन ' . e($financialYear['FinancialYearMaster']['year']) . ' चा वैधानिक लेखापरिक्षण अहवाल सादर करणेबाबत.' : 'Sub: Submission of Audit Report of ' . e($financialYear['FinancialYearMaster']['year']) . '.',
    'submission_to_registrar_body' => $isMr
        ? e($soc['society_name']) . ' या संस्थेचे दि. ' . $auditPeriod . ' या कालावधीचे वैधानिक लेखापरिक्षण पूर्ण झाले असून, सोबत लेखापरिक्षण अहवाल सादर करण्यात येत आहे. कृपया याची पोच द्यावी.'
        : 'Statutory Audit of M/s. ' . e($soc['society_name']) . ', for the period ' . $auditPeriod . ' is completed and please find enclosed herewith the Audit Report. So please acknowledge the report.',
    'salutation' => $isMr ? 'मा. अध्यक्ष, सचिव व कार्यकारी मंडळ,' : 'The Chairman/Secretary, Managing Committee,',
    'submission_to_society_subject' => $isMr ? 'विषय :- संस्थेचा दि. ' . $auditPeriod . ' अखेरचा वैधानिक लेखापरिक्षण अहवाल.' : 'Sub: Submission of Statutory Audit Report for the accounting year ended ' . _auditReportFormatDate($reg['audit_end_date']) . '.',
    'submission_to_society_body' => $isMr
        ? e($soc['society_name']) . ' या संस्थेच्या हिशोब पुस्तकांचे, कार्यालयीन अधिकाऱ्यांनी पुरवलेल्या माहिती व कागदपत्रांच्या आधारे लेखापरिक्षण करण्यात आले असून, उत्पन्न-खर्च पत्रक व ताळेबंद तपासण्यात आले आहे. तपासणीत आढळलेल्या त्रुटी व शेरे या अहवालासोबत जोडलेल्या सामान्य सूचना व शेऱ्यांमध्ये नमूद केले आहेत. महाराष्ट्र सहकारी संस्था अधिनियम १९६० कलम ८२ नुसार दोष दुरूस्ती अहवाल विहीत मुदतीत "ओ" नमुन्यात त्रिप्रतीत निबंधक कार्यालयास सादर करावा.'
        : 'The accounts of M/s. ' . e($soc['society_name']) . ' have been audited on the basis of the records produced and information supplied (oral and written) by the office bearers during the course of our audit. The Income & Expenditure Account and the Balance Sheet for the aforesaid period have been duly examined, and the observations and discrepancies noticed have been mentioned in the audit objections and general remarks enclosed herewith. The Society is instructed to submit the audit rectification report in Form "O", in triplicate, within three months from the date of this report, to the office of the Registrar of Co-operative Societies, as required under Section 82 of the Maharashtra State Co-operative Societies Act, 1960.',
    'opinion_heading' => $isMr ? 'वैधानिक लेखापरिक्षकांचा अहवाल (कलम ८१ व नियम ६९(३))' : 'Statutory Auditor\'s Report [See Section 81 and Rule 69(3)]',
    'opinion_to' => $isMr ? 'प्रति, सर्व सभासद,' : 'To, The Members,',
    'part_a_heading' => $isMr ? 'भाग "अ" - विशिष्ट अहवाल (हिशोब टिपणी)' : 'Part A - Specific Report (Notes on Accounts)',
    'part_b_heading' => $isMr ? 'भाग "ब" - व्यवस्थापन व आर्थिक तपशिल' : 'Part B - Management & Finance Details',
    'part_c_heading' => $isMr ? 'भाग "क" - लेखापरिक्षण आक्षेप व सामान्य शेरे' : 'Part C - Audit Objections & General Remarks',
    'nine_point_heading' => $isMr ? 'महाराष्ट्र सहकारी संस्था कायदा कलम ८१(२) अन्वये नऊ मुददयांवर द्यावयाचे शेरे' : 'Remarks under the nine points as per Section 81(2) of the Maharashtra Co-operative Societies Act',
    'schedule_heading' => $isMr ? 'कलम ८१(२) नुसार सांविधिक अहवाल - परिशिष्ठ १ ते ५' : 'Statutory Report u/s 81(2) - Schedules I to V',
    'sr_no' => $isMr ? 'अ.नं.' : 'Sr. No.',
    'particulars' => $isMr ? 'मुददयांचा तपशिल' : 'Particulars',
    'remarks' => $isMr ? 'शेरा' : 'Remarks',
    'society_name_label' => $isMr ? 'संस्थेचे नाव व पत्ता' : 'Name & Address of Society',
    'registration_no_label' => $isMr ? 'नोंदणी क्रमांक' : 'Registration No.',
    'audit_period' => $isMr ? 'लेखापरिक्षण कालावधी' : 'Audit Period',
    'member_count' => $isMr ? 'एकूण सभासद संख्या' : 'Total Number of Members',
    'committee_meetings' => $isMr ? 'संचालक मंडळ सभांची संख्या' : 'No. of Committee Meetings',
    'agm_date' => $isMr ? 'वार्षिक सर्वसाधारण सभा दिनांक' : 'Annual General Meeting Date',
    'audit_class' => $isMr ? 'लेखापरिक्षण वर्ग' : 'Audit Classification',
    'part1_heading' => $isMr ? 'नमुना क्रमांक १ (भाग-१) - वैधानिक तपासणी सुची' : 'Form No. 1 (Part I) - Statutory Checklist',
    'form28_heading' => $isMr ? 'नमुना क्रमांक २८ (भाग-२)' : 'Form No. 28 (Part II)',
    'financial_heading' => $isMr ? 'आर्थिक तपशिल (ताळेबंद पत्रक)' : 'Financial Summary (Balance Sheet Items)',
    'amount' => $isMr ? 'रक्कम रू.' : 'Amount (Rs.)',
    'general_remarks_heading' => $isMr ? 'सामान्य सूचना व शेरे' : 'General Instructions and Remarks',
    'law_violation_heading' => $isMr ? 'सहकारी कायदा, कानून व उपविधी उल्लंघन तपशिल' : 'Details of Violations of the Co-operative Act, Rules and Bye-laws',
    'fee_bill_heading' => $isMr ? 'वैधानिक लेखापरिक्षण फी चे बिल' : 'Statutory Audit Fee Bill',
    'fee_particulars' => $isMr ? 'तपशिल' : 'Particulars',
    'fee_audit' => $isMr ? 'वैधानिक लेखापरिक्षण फी' : 'Statutory Audit Fee',
    'fee_other' => $isMr ? 'अधिक: इतर खर्च' : 'Add: Other Charges',
    'fee_tds' => $isMr ? 'वजा: टीडीएस' : 'Less: TDS',
    'fee_total' => $isMr ? 'एकूण' : 'Total',
    'place' => $isMr ? 'ठिकाण' : 'Place',
    'date' => $isMr ? 'दिनांक' : 'Date',
    'auditor' => $isMr ? 'लेखापरिक्षक' : 'Auditor',
    'panel_no' => $isMr ? 'पॅनेल क्र.' : 'Panel No.',
    'review_banner' => $isMr ? 'सूचना: हा अहवाल अद्याप अंतिम (finalized) झालेला नाही. लेखापरिक्षकांनी पडताळणी केल्याशिवाय हा सादर करू नये.' : 'NOTICE: This report has not been finalized. It must not be submitted until the auditor has verified every field.',
);

// Embedded as a base64 data: URI rather than relying on an OS-installed
// font: an OS font depends on the exact user/service context the web
// server's PHP process runs under having that font registered, which
// differs from an interactive shell even on the same machine and same
// Windows account (confirmed here - installing the font and even
// restarting Apache did not make it visible to PHP's exec()'d wkhtmltopdf).
// A self-contained @font-face needs nothing installed on the server at all,
// on Windows or Linux.
$devanagariFontFace = '';
if ($isMr) {
    $fontPath = storage_path('app/fonts/NotoSansDevanagari.ttf');
    if (file_exists($fontPath)) {
        $fontBase64 = base64_encode(file_get_contents($fontPath));
        $devanagariFontFace = "@font-face { font-family: 'NotoSansDevanagari'; src: url(data:font/ttf;base64,$fontBase64) format('truetype'); }";
    }
}

// Do not add forced page breaks (CSS page-break-before/after, or a
// <div class="page-break">) back into this template. On this wkhtmltopdf
// build they corrupt Devanagari shaping for everything rendered after the
// break - the exact same font/text that renders correctly without a page
// break renders as blank glyph boxes with one inserted, reproduced
// consistently while building this report. Let sections flow naturally.
$fontFamily = $isMr ? "'NotoSansDevanagari', sans-serif" : "Helvetica, Arial, sans-serif";
?>
<!DOCTYPE html>
<html lang="<?php echo $isMr ? 'mr' : 'en'; ?>">
<head>
<meta charset="utf-8">
<style type="text/css">
<?php echo $devanagariFontFace; ?>
@page { margin: 25px 30px; }
body { font-family: <?php echo $fontFamily; ?>; font-size: 11px; color: #000; }
h2, h3, h4 { text-align: center; margin: 4px 0; }
.review-banner { border: 1px solid #a00; background: #fee; color: #a00; padding: 6px; text-align: center; font-weight: bold; margin-bottom: 10px; }
table.plain { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
table.plain td { padding: 2px 4px; vertical-align: top; }
table.bordered { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
table.bordered th, table.bordered td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-size: 10.5px; }
table.bordered th { background: #eee; text-align: left; }
.col-sr { width: 6%; text-align: center; }
.col-particulars { width: 54%; }
.col-remarks { width: 40%; }
.section-title { margin-top: 16px; margin-bottom: 6px; font-size: 13px; }
.signature-block { margin-top: 20px; }

p { margin: 4px 0; }
</style>
</head>
<body>

<?php if ($reg['status'] != 'finalized'): ?>
<div class="review-banner"><?php echo $t['review_banner']; ?></div>
<?php endif; ?>

<h2><?php echo e($soc['society_name']); ?></h2>
<p style="text-align:center;"><?php echo e($soc['address']); ?></p>
<h3><?php echo $t['title']; ?></h3>
<p style="text-align:center;"><?php echo e($reg['auditor_name']); ?> &mdash; <?php echo $t['panel_no']; ?>: <?php echo e($reg['auditor_panel_no']); ?></p>

<!-- Letter of submission: to the Registrar -->
<table class="plain">
    <tr><td><strong><?php echo $t['to']; ?></strong></td><td style="text-align:right;"><?php echo $t['date']; ?>: <?php echo _auditReportFormatDate($reg['audit_report_date']); ?></td></tr>
</table>
<p><?php echo $t['registrar_salutation']; ?>,<br><?php echo nl2br(e($reg['registrar_address'])); ?></p>
<p><strong><?php echo $t['submission_to_registrar_subject']; ?></strong></p>
<p><?php echo $t['submission_to_registrar_body']; ?></p>

<!-- Letter of submission: to the Society -->
<table class="plain">
    <tr><td><strong><?php echo $t['to']; ?></strong></td><td style="text-align:right;"><?php echo $t['date']; ?>: <?php echo _auditReportFormatDate($reg['audit_report_date']); ?></td></tr>
</table>
<p><?php echo $t['salutation']; ?><br><?php echo e($soc['society_name']); ?>, <?php echo e($soc['address']); ?></p>
<p><strong><?php echo $t['submission_to_society_subject']; ?></strong></p>
<p><?php echo $t['submission_to_society_body']; ?></p>

<!-- Statutory Auditor's Report (opinion) -->
<h4><?php echo $t['opinion_heading']; ?></h4>
<p><?php echo $t['opinion_to']; ?> <?php echo e($soc['society_name']); ?>, <?php echo e($soc['address']); ?></p>
<?php foreach ($opinionParagraphs as $para): ?>
    <p><?php echo e($para); ?></p>
<?php endforeach; ?>

<!-- Part A -->
<div class="section-title"><?php echo $t['part_a_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($partAQuestions, $partAAnswers, $isMr); ?>
</table>

<!-- Part B -->
<div class="section-title"><?php echo $t['part_b_heading']; ?></div>
<table class="plain">
    <tr><td style="width:35%;"><strong><?php echo $t['society_name_label']; ?></strong></td><td><?php echo e($soc['society_name']); ?>, <?php echo e($soc['address']); ?></td></tr>
    <tr><td><strong><?php echo $t['registration_no_label']; ?></strong></td><td><?php echo e($soc['registration_no']); ?></td></tr>
    <tr><td><strong><?php echo $t['audit_period']; ?></strong></td><td><?php echo $auditPeriod; ?></td></tr>
    <tr><td><strong><?php echo $t['member_count']; ?></strong></td><td><?php echo intval($memberCount); ?></td></tr>
    <tr><td><strong><?php echo $t['committee_meetings']; ?></strong></td><td><?php echo intval($reg['board_meetings_count']); ?></td></tr>
    <tr><td><strong><?php echo $t['agm_date']; ?></strong></td><td><?php echo _auditReportFormatDate($reg['agm_date']); ?></td></tr>
    <tr><td><strong><?php echo $t['audit_class']; ?></strong></td><td><?php echo e($reg['audit_grade']); ?></td></tr>
    <?php foreach ($partBFields as $f): ?>
    <tr>
        <td><strong><?php echo $isMr ? e($f['mr']) : e($f['en']); ?></strong></td>
        <td><?php echo !empty($partBExtra[$f['code']]) ? nl2br(e($partBExtra[$f['code']])) : '&mdash;'; ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="section-title"><?php echo $t['financial_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th style="width:20%;text-align:right;"><?php echo $t['amount']; ?></th></tr>
    <?php foreach ($financialFields as $i => $f): ?>
    <tr>
        <td class="col-sr"><?php echo $i + 1; ?></td>
        <td class="col-particulars"><?php echo $isMr ? e($f['mr']) : e($f['en']); ?></td>
        <td style="text-align:right;"><?php echo number_format(isset($financialSnapshot[$f['code']]) ? $financialSnapshot[$f['code']] : 0, 2); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Part C -->
<div class="section-title"><?php echo $t['part_c_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($partCQuestions, $partCCompliance, $isMr); ?>
</table>

<div class="section-title"><?php echo $t['general_remarks_heading']; ?></div>
<p><?php echo !empty($reg['general_remarks']) ? nl2br(e($reg['general_remarks'])) : '&mdash;'; ?></p>

<div class="section-title"><?php echo $t['law_violation_heading']; ?></div>
<p><?php echo !empty($reg['law_violation_notes']) ? nl2br(e($reg['law_violation_notes'])) : '&mdash;'; ?></p>

<!-- Nine-Point Statement -->
<div class="section-title"><?php echo $t['nine_point_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($ninePoints, $ninePointRemarks, $isMr); ?>
</table>

<!-- Statutory Report - Schedules I-V -->
<div class="section-title"><?php echo $t['schedule_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($scheduleQuestions, $scheduleRemarks, $isMr); ?>
</table>

<div class="signature-block">
    <table class="plain">
        <tr>
            <td><?php echo $t['place']; ?>: <?php echo e($reg['report_place']); ?></td>
            <td style="text-align:right;"><?php echo $t['auditor']; ?>: <?php echo e($reg['auditor_name']); ?></td>
        </tr>
        <tr>
            <td><?php echo $t['date']; ?>: <?php echo _auditReportFormatDate($reg['audit_report_date']); ?></td>
            <td style="text-align:right;"><?php echo $t['panel_no']; ?>: <?php echo e($reg['auditor_panel_no']); ?></td>
        </tr>
    </table>
</div>

<!-- Form No. 1 (Part I) -->
<div class="section-title"><?php echo $t['part1_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($part1Questions, $part1Answers, $isMr); ?>
</table>

<!-- Form No. 28 (Part II) -->
<div class="section-title"><?php echo $t['form28_heading']; ?></div>
<table class="bordered">
    <tr><th class="col-sr"><?php echo $t['sr_no']; ?></th><th class="col-particulars"><?php echo $t['particulars']; ?></th><th class="col-remarks"><?php echo $t['remarks']; ?></th></tr>
    <?php echo _auditReportQuestionRows($form28Questions, $form28Answers, $isMr); ?>
</table>

<!-- Fee Bill -->
<div class="section-title"><?php echo $t['fee_bill_heading']; ?></div>
<table class="bordered">
    <tr><th><?php echo $t['fee_particulars']; ?></th><th style="width:25%;text-align:right;"><?php echo $t['amount']; ?></th></tr>
    <tr><td><?php echo $t['fee_audit']; ?> (<?php echo intval($memberCount); ?> x Rs.100)</td><td style="text-align:right;"><?php echo number_format($reg['audit_fee_amount'], 2); ?></td></tr>
    <tr><td><?php echo $t['fee_other']; ?></td><td style="text-align:right;"><?php echo number_format($reg['other_charges_amount'], 2); ?></td></tr>
    <tr><td><?php echo $t['fee_tds']; ?></td><td style="text-align:right;">(<?php echo number_format($reg['tds_amount'], 2); ?>)</td></tr>
    <?php
        $total = floatval($reg['audit_fee_amount']) + floatval($reg['other_charges_amount']) - floatval($reg['tds_amount']);
    ?>
    <tr><td><strong><?php echo $t['fee_total']; ?></strong></td><td style="text-align:right;"><strong><?php echo number_format($total, 2); ?></strong></td></tr>
</table>

</body>
</html>
