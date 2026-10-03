<?php

// Copy of CakePHP app/Lib/AuditReportData.php (static bilingual content of the statutory audit report), namespaced.

namespace App\Support;

/**
 * Static content for the statutory audit report (Maharashtra Co-operative
 * Societies Act, 1960 - Housing Society format, "Form 28"). One entry per
 * question: bilingual label plus the safe/common default answer used when no
 * violation exists. Defaults are starting points only - AuditReportsController
 * always stores a 'status' of draft until a human confirms them, and the PDF
 * prints a REVIEW banner on any report still in draft status.
 */
class AuditReportData {

    // Section 81(2) - "Nine Points" statement.
    public static function ninePoints() {
        return array(
            array('code' => 'np1', 'mr' => 'कोणतीही कर्ज असल्यास त्यांच्या बऱ्याच काळ थकलेल्या रकमा.', 'en' => 'Whether any loans and advances made by the society appear as bad or doubtful of recovery.', 'default_mr' => 'अशा रक्कमा नाहीत.', 'default_en' => 'No such amounts.'),
            array('code' => 'np2', 'mr' => 'रोख शिल्लक व कर्ज रोखे आणि संस्थेच्या मत्ता व दायित्वे यांचे मूल्यांकन.', 'en' => 'Cash balance and loan securities and valuation of society assets and liabilities.', 'default_mr' => 'संस्था कर्ज वाटपाचे व्यवहार करत नाही. लागू नाही', 'default_en' => 'The society does not carry out loan disbursement transactions. Not applicable.'),
            array('code' => 'np3', 'mr' => 'प्रतिभूर्तीच्या आधारे संस्थेने दिलेले कर्ज व आगाऊ रकमा आणि घेतलेली कर्ज योग्य प्रकारे प्रतिभूत करण्यात आलेली आहे किंवा कसे.', 'en' => 'Whether loans and advances given/taken by the society against security are properly secured, and whether the terms are not prejudicial to members\' interests.', 'default_mr' => 'संस्था कर्ज वाटपाचे व्यवहार करत नाही. लागू नाही', 'default_en' => 'The society does not carry out loan disbursement transactions. Not applicable.'),
            array('code' => 'np4', 'mr' => 'केवळ पुस्तक नोंदीव्दारे संस्थेकडून केले जाणारे संव्यवहार असे व्यवहार संस्थेच्या हित संबंधाला बाधक ठरणारे आहेत किंवा कसे.', 'en' => 'Whether transactions represented merely by book entries are prejudicial to the interests of the society.', 'default_mr' => 'सामान्य सूचना व शेरे पहा.', 'default_en' => 'See General Instructions and Remarks.'),
            array('code' => 'np5', 'mr' => 'संस्थेने दिलेली कर्ज व आगाऊ रकमा ठेवी म्हणून दाखविण्यात आल्या आहेत काय.', 'en' => 'Whether loans and advances given by the society have been shown as deposits.', 'default_mr' => 'संस्था कर्ज वाटपाचे व्यवहार करत नाही. लागू नाही', 'default_en' => 'The society does not carry out loan disbursement transactions. Not applicable.'),
            array('code' => 'np6', 'mr' => 'वैयक्तिक खर्च महसूली लेख्यांवर भारीत करण्यात आला आहे काय.', 'en' => 'Whether personal expenses have been charged to revenue account.', 'default_mr' => 'नाही', 'default_en' => 'No.'),
            array('code' => 'np7', 'mr' => 'आपली उदिष्टे साध्य करतांना संस्थेला काही खर्च झाला आहे काय.', 'en' => 'Whether the society has incurred any expenditure in achieving its objectives.', 'default_mr' => 'होय', 'default_en' => 'Yes.'),
            array('code' => 'np8', 'mr' => 'शासन किंवा शासकिय उपक्रम अथवा वित्त संस्था यांनी दिलेल्या सहाय्याचा योग्य प्रकारे वापर संस्थेने केला आहे काय.', 'en' => 'Whether government/government undertaking/financial institution assistance has been properly utilised for the purpose granted.', 'default_mr' => 'शासन सहाय्य नाही', 'default_en' => 'No government assistance received.'),
            array('code' => 'np9', 'mr' => 'संस्था सदस्याबाबतची आपली उदिष्टे व जबाबदाऱ्या योग्य प्रकारे पार पाडत आहे किंवा कसे.', 'en' => 'Whether the society is properly discharging its objectives and responsibilities towards its members.', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
        );
    }

    // Part 1 statutory checklist (Rule 69 / Form N style questionnaire).
    // Grouped the same way the sample report groups them (numbered items with
    // lettered sub-answers folded into one row where the source form does).
    public static function part1() {
        return array(
            array('code' => 'p1_new_members', 'section' => '11', 'mr' => 'नवे सभासद यथोचित करून घेतले आहेत काय? त्यांचे लेखी अर्ज घेऊन योग्य क्रमांक देण्यात आले आहेत काय?', 'en' => 'Are new members admitted properly? Have written applications been obtained and proper numbers allotted?', 'default_mr' => 'नवे सभासद करून घेतलेले आहेत. त्यांचेकडून सभासद अर्ज व इतर कागदपत्र घेतल्याचे दिसत आहे.', 'default_en' => 'New members have been admitted. Membership applications and other documents appear to have been obtained.'),
            array('code' => 'p1_registers_ij', 'section' => '12', 'mr' => 'म.स.सं. अधिनियम १९६० नियम ३२, ३३ आणि ६५(१) अन्वये नमुन्यात "आय", "जे" नोंदवही ठेवलेली आहे काय?', 'en' => 'Are the "I" and "J" registers maintained as prescribed under Rules 32, 33 and 65(1) of the M.C.S. Act, 1960?', 'default_mr' => 'नोंदवहया ठेवलेल्या आहेत.', 'default_en' => 'Registers are being maintained.'),
            array('code' => 'p1_nominee', 'section' => '13', 'mr' => 'म.स.सं. अधिनियम १९६० कलम २५ प्रमाणे वारस नेमीन दिलेले आहेत काय? आणि त्यांची नोंद नियम २६ प्रमाणे यथोचितरित्या केल्या आहेत काय?', 'en' => 'Have nominations been made under Section 25 of the M.C.S. Act, 1960 and recorded as per Rule 26?', 'default_mr' => 'पुष्कळ सभासदाकडून वारस अर्ज घेतलेले नाहीत, व वारस नोंदवही योग्य त्या नोंदीसह संस्था दप्तरी ठेवलेली नाही. सामान्य सूचना व शेरे पहा', 'default_en' => 'Nomination forms have not been obtained from many members, and the nominee register has not been maintained with proper entries. See General Instructions and Remarks.'),
            array('code' => 'p1_deceased', 'section' => '14', 'mr' => 'मृत, काढून टाकलेल्या व राजिनामा दिलेल्या सभासदांचे बाबतित आवश्यक ती नोंद केलेली आहे काय?', 'en' => 'Have proper entries been made for deceased, removed and resigned members?', 'default_mr' => '"आय", "जे" व इतर अनुषंगिक नोंदवहीत नोंदी पूर्ण केल्याचे दिसत आहे.', 'default_en' => 'Entries in the "I", "J" and other related registers appear complete.'),
            array('code' => 'p1_resignations', 'section' => '15', 'mr' => 'राजिनामे रितसर आहेत काय? आणि ते यथोचित स्विकारले आहेत काय?', 'en' => 'Are resignations in order and duly accepted?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_shares_applications', 'section' => '16.1', 'mr' => 'भागाकरिता आलेले अर्ज बरोबर आहेत काय?', 'en' => 'Are the applications received for shares in order?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_shares_register', 'section' => '16.2', 'mr' => 'भागांची नोंदवही अध्यावत लिहीलेली आहे काय?', 'en' => 'Is the share register kept up to date?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_shares_ledger_match', 'section' => '16.3', 'mr' => 'भागांच्या नोंदवहातील नोंदी किर्दीतील नोंदीशी जुळतात काय?', 'en' => 'Do entries in the share register agree with the cash book/ledger entries?', 'default_mr' => 'होय', 'default_en' => 'Yes.'),
            array('code' => 'p1_shares_general_ledger', 'section' => '16.4', 'mr' => 'भागांची खतावणी अध्यावत लिहिली आहे काय?', 'en' => 'Is the share general ledger kept up to date?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_shares_balance_match', 'section' => '16.5', 'mr' => 'भागांची एकून खतावणी बाकी ताळेबंदातील भाग भांडवलाच्या आकडयाशी जुळते काय?', 'en' => 'Does the total share ledger balance agree with the share capital figure in the balance sheet?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_share_certificates', 'section' => '16.6', 'mr' => 'भागधारकांना त्यांनी घेतलेल्या सर्व भागाबद्दल भाग दाखले दिली आहेत काय?', 'en' => 'Have share certificates been issued to shareholders for all shares held?', 'default_mr' => 'सर्व भाग दाखले बनवलेले आहेत. काही सभासदांनी नेले नाहीत.', 'default_en' => 'All share certificates have been prepared. Some members have not collected theirs.'),
            array('code' => 'p1_share_transfer', 'section' => '16.7', 'mr' => 'भागांचे हस्तांतरण (ट्रान्सफर) अगर त्यांची किंमत परत देणे या गोष्टी कायदा, कानून व पोटनियम यांतील तरतुदी प्रमाणे झाल्या आहेत काय?', 'en' => 'Have share transfers/refunds been carried out as per the Act, Rules and Bye-laws?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_loan_limit', 'section' => '17.1', 'mr' => 'पोटनियमाप्रमाणे संस्थेने कर्ज घेण्याची मर्यादा काय ठरविली आहे?', 'en' => 'What borrowing limit has the society fixed as per its bye-laws?', 'default_mr' => 'महाराष्ट्र सह. संस्था अधिनियम १९६० चा नियम १९६१ नियम ३४ प्रमाणे.', 'default_en' => 'As per Rule 34 of the Maharashtra Co-operative Societies Rules, 1961.'),
            array('code' => 'p1_loan_limit_breached', 'section' => '17.2', 'mr' => 'ती उल्लंघली गेली आहे काय?', 'en' => 'Has that limit been exceeded?', 'default_mr' => 'नाही', 'default_en' => 'No.'),
            array('code' => 'p1_loan_limit_sanction', 'section' => '17.3', 'mr' => 'जर ती उल्लंघली गेली असेल तर, सक्षम अधिकाऱ्याकडून आवश्यक ती अनुज्ञा मिळाली आहे काय?', 'en' => 'If exceeded, has approval been obtained from the competent authority?', 'default_mr' => 'लागू नाही.', 'default_en' => 'Not applicable.'),
            array('code' => 'p1_agm_date', 'section' => '18.1a', 'mr' => 'वार्षिक सर्वसाधरण सभा दिनांक', 'en' => 'Annual General Meeting date', 'default_mr' => '', 'default_en' => '', 'type' => 'date'),
            array('code' => 'p1_sgm_date', 'section' => '18.1b', 'mr' => 'विशेष सर्वसाधारण सभा दिनांक', 'en' => 'Special General Meeting date', 'default_mr' => 'तपासणी कालावधीत विशेष सर्वसाधारण सभा झालेली नाही.', 'default_en' => 'No Special General Meeting was held during the audit period.'),
            array('code' => 'p1_board_meetings', 'section' => '18.2a', 'mr' => 'लेखापरिक्षण कालावधीत भरलेल्या संचालक मंडळ सभांची संख्या', 'en' => 'Number of Managing Committee meetings held during the audit period', 'default_mr' => '', 'default_en' => '', 'type' => 'number'),
            array('code' => 'p1_sub_committee_meetings', 'section' => '18.2b', 'mr' => 'कार्यकारी व उप समितीच्या सभा', 'en' => 'Executive/sub-committee meetings', 'default_mr' => 'नाही', 'default_en' => 'None.'),
            array('code' => 'p1_other_meetings', 'section' => '18.2c', 'mr' => 'अन्य सभा', 'en' => 'Other meetings', 'default_mr' => 'नाहीत', 'default_en' => 'None.'),
            array('code' => 'p1_prev_defect_report_sent', 'section' => '19.1', 'mr' => 'संस्थेने मागील लेखापरिक्षण अहवालाचा दोष दुरूस्ती अहवाल पाठविला आहे काय?', 'en' => 'Has the society submitted the defect-rectification report for the previous audit?', 'default_mr' => 'सन मागील वर्षाच्या वैधानिक लेखापरिक्षण अहवालाचा दोष दुरूस्ती अहवाल विहीत नमुन्यात बनवून संबधीताना सादर केल्याचे दिसत आहे.', 'default_en' => 'The defect-rectification report for the previous year\'s statutory audit appears to have been prepared in the prescribed form and submitted to the concerned authority.'),
            array('code' => 'p1_prev_points_ignored', 'section' => '19.2', 'mr' => 'मागील लेखापरिक्षण अहवालात नमुद केलेल्या काही महत्वाच्या मुद्दयाकडे संस्थेने दुर्लक्ष केले आहे काय?', 'en' => 'Has the society ignored any important point noted in the previous audit report?', 'default_mr' => 'सामान्य सूचना व शेरे पहा', 'default_en' => 'See General Instructions and Remarks.'),
            array('code' => 'p1_internal_audit', 'section' => '21.1', 'mr' => 'अंतर्गत व स्थानिक लेखापरिक्षा झाले असेल तर ते कोणी केले? त्याचा अहवाल संस्थेच्या दप्तरी आहे काय?', 'en' => 'If internal/local audit was conducted, by whom, and is its report on the society\'s record?', 'default_mr' => 'तपासणी कालावधी करीता अंतर्गत लेखापरिक्षण करणेसाठी योग्य व्यक्तीची नियुक्ती केलेली आहे.', 'default_en' => 'A suitable person has been appointed to carry out internal audit for the period under examination.'),
            array('code' => 'p1_internal_statutory_coord', 'section' => '21.2', 'mr' => 'संविधिक (वैधानिक) लेखापरिक्षा व अंर्तगत लेखापरिक्षा यांच्यात समन्वय आहेत काय?', 'en' => 'Is there coordination between the statutory and internal audit?', 'default_mr' => 'लागू नाही', 'default_en' => 'Not applicable.'),
            array('code' => 'p1_secretary_name', 'section' => '22.1', 'mr' => 'सचिव/कार्यकारी संचालक/चिटणीस यांचे नाव', 'en' => 'Name of Secretary/Executive Director/Chitnis', 'default_mr' => '', 'default_en' => '', 'type' => 'text'),
            array('code' => 'p1_secretary_salary', 'section' => '22.2', 'mr' => 'दरमहा घेतलेले वेतन', 'en' => 'Monthly remuneration drawn', 'default_mr' => 'मंडळातील सदस्यानी वेतन घेतलेले नाही.', 'default_en' => 'No committee member draws remuneration.'),
            array('code' => 'p1_secretary_perks', 'section' => '22.3', 'mr' => 'त्यांना अन्य भत्ते व सवलती (उदा. विनामूल्य निवासगृह) उपलब्ध करून दिल्या आहेत काय?', 'en' => 'Are any other allowances/concessions (e.g. free housing) provided?', 'default_mr' => 'नाहीत.', 'default_en' => 'None.'),
            array('code' => 'p1_secretary_is_member', 'section' => '22.4', 'mr' => 'ते संस्थेचे सभासद आहेत काय?', 'en' => 'Are they a member of the society?', 'default_mr' => 'आहेत', 'default_en' => 'Yes.'),
            array('code' => 'p1_secretary_loan', 'section' => '22.5', 'mr' => 'जर ते सभासद असतील तर त्यांनी कर्ज घेतले आहे काय? किंवा त्यांना उधार-उचल, सवलती दिल्या आहेत काय?', 'en' => 'If a member, have they taken any loan, advance or concession?', 'default_mr' => 'लागू नाही', 'default_en' => 'Not applicable.'),
            array('code' => 'p1_bylaw_violation', 'section' => '23.2', 'mr' => 'कायदा, कानून व उपविधी उल्लघंन झाल्याच्या नोंदी', 'en' => 'Instances of violation of Act, Rules or Bye-laws', 'default_mr' => 'सामान्य सूचना व शेरे पहा', 'default_en' => 'See General Instructions and Remarks.'),
            array('code' => 'p1_profit_loss_last_year', 'section' => '24.1', 'mr' => 'गेल्या वर्षात किती नफा/तोटा झाला?', 'en' => 'Profit/loss for the previous year', 'default_mr' => '', 'default_en' => '', 'type' => 'text'),
            array('code' => 'p1_profit_division', 'section' => '24.2', 'mr' => 'निव्वळ नफ्याची विभागणी कशी केली ते लिहा.', 'en' => 'How was the net profit appropriated?', 'default_mr' => 'शिल्लक वाढाव्याची विभागणी केलेली नाही.', 'default_en' => 'The surplus has not been appropriated.'),
            array('code' => 'p1_cash_count_date', 'section' => '25a1', 'mr' => 'रोकड रक्कम मोजल्याचा दिनांक व रक्कम', 'en' => 'Date and amount of cash physically verified', 'default_mr' => '', 'default_en' => '', 'type' => 'text'),
            array('code' => 'p1_cash_authority', 'section' => '25a2', 'mr' => 'रक्कम मोजण्यासाठी कोणी सादर केली? त्याचे नाव व पदनाम. रोकड ठेवण्याबाबत त्यांना अधिकार आहेत काय?', 'en' => 'Who produced the cash for counting (name, designation)? Are they authorised to hold cash?', 'default_mr' => 'व्यवस्थापक होय.', 'default_en' => 'Yes, the Manager.'),
            array('code' => 'p1_cash_matches_books', 'section' => '25a3', 'mr' => 'किर्दीप्रमाणे रोकड रक्कम बरोबर आहे काय?', 'en' => 'Does the cash tally with the cash book?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_cash_safety', 'section' => '25a4', 'mr' => 'ने-आण करताना आणि तिजोरीत ठेवलेल्या शिल्लक रक्कमेच्या सुरक्षिबाबत केलेली व्यवस्था पुरेशी आहे काय?', 'en' => 'Are the arrangements for safety of cash in transit and in the safe adequate?', 'default_mr' => 'लागू नाही.', 'default_en' => 'Not applicable.'),
            array('code' => 'p1_bank_reco', 'section' => '25b', 'mr' => 'बँकेच्या पासबुकातील, पत्रकातील, बैंक दाखल्यातील शिल्लक संस्थेच्या जमा खर्च वहीशी/मेळपत्रकाशी जुळतात काय?', 'en' => 'Do bank passbook/statement/certificate balances agree with the society\'s cash book/reconciliation statement?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_investment_name', 'section' => '25c1', 'mr' => 'गुंतवणुक रोखे प्रत्यक्ष पाहिले व ते संस्थेच्या नावावरच आहेत काय?', 'en' => 'Were investment securities physically verified, and are they in the society\'s name?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_investment_interest', 'section' => '25c2', 'mr' => 'गुंतवणुकीवर व्याज/लाभांश यथावकाश वसूल करण्यात येत आहे काय?', 'en' => 'Is interest/dividend on investments collected in time?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_investment_bank_certs', 'section' => '25c3', 'mr' => 'जर रोखे बँकेत ठेवले असतील तर, त्याबाबतचे संबंधीत दाखले मिळाले आहेत काय?', 'en' => 'If securities are held with a bank, have the relevant certificates been obtained?', 'default_mr' => 'लागू नाही', 'default_en' => 'Not applicable.'),
            array('code' => 'p1_investment_register', 'section' => '25c4', 'mr' => 'गुंतवणुकीची नोंदवही अदयावत नोंदीसह संस्था दप्तरी ठेवली आहे काय?', 'en' => 'Is the investment register maintained up to date on the society\'s record?', 'default_mr' => 'आहे.', 'default_en' => 'Yes.'),
            array('code' => 'p1_fixed_asset_register', 'section' => '26.1', 'mr' => 'स्थावर व जंगम मालमत्तेची संबंधीत नोंदवही अदयावत नोंदी सह संस्था दप्तरी ठेवली आहे काय?', 'en' => 'Is the immovable/movable property register maintained up to date?', 'default_mr' => 'नोंदवहया ठेवलेल्या नाहीत.', 'default_en' => 'Registers have not been maintained.'),
            array('code' => 'p1_fixed_asset_verify', 'section' => '26.2', 'mr' => 'मालमत्तेची यादी घेऊन प्रत्यक्ष रूजवात घ्या. ताळेबंदातील रक्कमशी वाक्या जुळतात काय?', 'en' => 'Does the property list, physically verified, agree with the balance sheet figure?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_land_title', 'section' => '26.3', 'mr' => 'जमिन, स्थावर मालमत्ता बाबत त्यांचे विलेख/दस्तऐवज पहा व ते संस्थेच्या नावावर आहे काय?', 'en' => 'Are the title deeds for land/immovable property in the society\'s name?', 'default_mr' => 'जमिनीचे हक्क पत्रे संस्थेच्या लाभात नोंदवलेले नाहीत.', 'default_en' => 'Title deeds have not been registered in the society\'s favour.'),
            array('code' => 'p1_asset_insurance', 'section' => '26.4', 'mr' => 'आवश्यक मालमत्तेचा विमा उतरवलेला आहे काय?', 'en' => 'Is the necessary property insured?', 'default_mr' => 'होय', 'default_en' => 'Yes.'),
            array('code' => 'p1_depreciation', 'section' => '26.5', 'mr' => 'घसारा यथावत आकारला आहे काय?', 'en' => 'Is depreciation charged regularly?', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'p1_report_discussed', 'section' => '27', 'mr' => 'लेखापरिक्षण अहवालाबाबत समिती सभेत चर्चा केली काय? दिनांक दया.', 'en' => 'Was the audit report discussed at a Committee meeting? Give the date.', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
        );
    }

    // Financial summary (Part "B", items 1.1 - 1.10). Each maps to one or more
    // account_head_id totals via AuditReportDataComponent::financialSnapshot().
    public static function financialSummaryFields() {
        return array(
            array('code' => 'share_capital', 'mr' => 'वसूल भाग भांडवल जमा', 'en' => 'Paid-up Share Capital'),
            array('code' => 'reserve_fund', 'mr' => 'राखीव निधी जमा', 'en' => 'Reserve Fund'),
            array('code' => 'sinking_fund', 'mr' => 'सिंकिंग फंड जमा', 'en' => 'Sinking Fund'),
            array('code' => 'repair_fund', 'mr' => 'इमारत देखभाल व दुरूस्ती निधी जमा', 'en' => 'Building Repairs & Maintenance Fund'),
            array('code' => 'education_fund', 'mr' => 'सहकार शिक्षण व प्रशिक्षण निधी', 'en' => 'Co-op Education & Training Fund'),
            array('code' => 'other_liabilities', 'mr' => 'इतर देणी व तरतूदी जमा', 'en' => 'Other Liabilities & Provisions'),
            array('code' => 'cash_in_hand', 'mr' => 'हातातील रोख शिल्लक नावे', 'en' => 'Cash in Hand'),
            array('code' => 'bank_balance', 'mr' => 'बँक शिल्लक नावे', 'en' => 'Bank Balance'),
            array('code' => 'investments', 'mr' => 'गुंतवणूक नावे', 'en' => 'Investments'),
            array('code' => 'fixed_assets', 'mr' => 'कायम मालमत्ता नावे', 'en' => 'Fixed Assets'),
        );
    }

    // Part A - "Specific Report / Notes on Accounts". Six standard findings,
    // almost always NIL for a clean audit - default answers reflect that.
    public static function partA() {
        return array(
            array('code' => 'embezzlement', 'mr' => 'आर्थिक अफरातफर', 'en' => 'Financial embezzlement', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'misappropriation', 'mr' => 'निधीचा गैरवापर', 'en' => 'Misappropriation of funds', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'improper_appropriation', 'mr' => 'निधीचे अयोग्य विनियोजन', 'en' => 'Improper appropriation of funds', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'policy_effect', 'mr' => 'धोरणात्मक निर्णयामुळे व्यवहारावर परिणाम', 'en' => 'Effect of transactions caused due to policy decisions', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'improper_loans', 'mr' => 'अयोग्य व अनियमित कर्ज व्यवहार', 'en' => 'Improper and irregular loan transactions', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'improper_investment', 'mr' => 'अयोग्य गुंतवणूक', 'en' => 'Improper investment', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
        );
    }

    // Part B - management + finance summary. Mostly pulled straight from
    // fields the app already has (member count, meetings, financial
    // snapshot) - this list is only the couple of items that have no other
    // home, rendered alongside those existing values in pdf.ctp.
    public static function partBFields() {
        return array(
            array('code' => 'last_election_date', 'mr' => 'संस्थेची शेवटची निवडणूक', 'en' => 'Society\'s Last Election', 'default_mr' => '', 'default_en' => ''),
            array('code' => 'committee_tenure_end', 'mr' => 'समिती कार्यकाळ समाप्ती दिनांक', 'en' => 'Date of End of Tenure of Committee', 'default_mr' => '', 'default_en' => ''),
            array('code' => 'audit_compliance_report', 'mr' => 'लेखापरिक्षण दोष दुरूस्ती अहवाल', 'en' => 'Audit Compliance Report', 'default_mr' => '', 'default_en' => ''),
        );
    }

    // Part C - Audit Objections & General Remarks. Compliance status +
    // suggestion per subject; the 25-row list every housing-society audit in
    // this format covers, in the same order as the source report.
    public static function partC() {
        return array(
            array('code' => 'c_cash_limit', 'mr' => 'हातातील रोख रकमेची मर्यादा', 'en' => 'Limit of Cash on Hand', 'default_mr' => 'दैनंदिन रोख शिल्लक रू. ५,०००/- च्या आत ठेवावी.', 'default_en' => 'A sum not exceeding Rs. 5,000/- should be kept in hand at the close of every day.'),
            array('code' => 'c_bank_balance', 'mr' => 'बँक शिल्लक', 'en' => 'Bank Balance', 'default_mr' => 'बचत खात्यात मोठी शिल्लक ठेवल्याने संस्थेचे व्याजाचे नुकसान होते.', 'default_en' => 'Loss of interest to the society due to keeping a large balance in the Savings Account.'),
            array('code' => 'c_building_insurance', 'mr' => 'इमारत विमा', 'en' => 'Building Insurance', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
            array('code' => 'c_conveyance', 'mr' => 'संस्था अभिहस्तांतरण (कन्व्हेयन्स)', 'en' => 'Society Conveyance Deed', 'default_mr' => 'संस्थेने अभिहस्तांतरण पूर्ण करावे.', 'default_en' => 'Society has to complete the Conveyance Deed.'),
            array('code' => 'c_statutory_registers', 'mr' => 'वैधानिक नोंदवह्या/पुस्तके', 'en' => 'Statutory Registers/Books', 'default_mr' => 'संस्थेने वैधानिक नोंदवह्या ठेवलेल्या आहेत.', 'default_en' => 'Society has maintained the statutory books/registers.'),
            array('code' => 'c_share_certificate', 'mr' => 'भाग दाखले वितरण', 'en' => 'Issue of Share Certificate', 'default_mr' => 'काही सभासदांना भाग दाखले दिलेले नाहीत; वाटपानंतर सहा महिन्यांत द्यावेत.', 'default_en' => 'Share certificates have not been issued to some members; must be issued within six months of allotment.'),
            array('code' => 'c_nomination', 'mr' => 'सभासद नामनिर्देशन', 'en' => 'Nomination of Members', 'default_mr' => 'सर्व सभासदांकडून नामनिर्देशन अर्ज घेण्यात यावेत.', 'default_en' => 'Society must collect nomination forms from all members.'),
            array('code' => 'c_election', 'mr' => 'समिती निवडणूक', 'en' => 'Election of the Committee', 'default_mr' => 'म.स.सं. अधिनियम कलम ७३(क ब) व उपविधी ११५ नुसार निवडणूक झाली.', 'default_en' => 'Society conducted the election as per MCS Act Sec. 73(CB) & Bye-law 115.'),
            array('code' => 'c_committee_strength', 'mr' => 'समिती सदस्य संख्या', 'en' => 'Strength of the Committee', 'default_mr' => 'उपविधी कलम ११४(१९) नुसार पुरेशी आहे.', 'default_en' => 'Sufficient, as per Bye-law Sec. 114(19).'),
            array('code' => 'c_fire_audit', 'mr' => 'अग्निसुरक्षा तपासणी', 'en' => 'Fire Audit', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 'c_lift_inspection', 'mr' => 'लिफ्ट तपासणी', 'en' => 'Lift Inspection', 'default_mr' => 'लिफ्ट तपासणी करण्यात आली आहे.', 'default_en' => 'Lift inspection has been done.'),
            array('code' => 'c_maintenance_charges', 'mr' => 'देखभाल शुल्क आकारणी', 'en' => 'Maintenance Charges', 'default_mr' => 'उपविधीनुसार देखभाल शुल्क आकारले जाते.', 'default_en' => 'Society is charging maintenance as per Bye-laws.'),
            array('code' => 'c_defaulters', 'mr' => 'थकबाकीदार सभासद', 'en' => 'Defaulters', 'default_mr' => 'काही सभासद ६ महिन्यांहून अधिक काळ थकबाकीदार आहेत; कलम १५४(बी)(२९) नुसार वसुली कारवाई करावी.', 'default_en' => 'Some members are more than 6 months in default; the committee shall initiate recovery under Section 154(B)29 of the Act.'),
            array('code' => 'c_interest_on_default', 'mr' => 'थकबाकीवरील व्याज', 'en' => 'Interest on Defaulted Charges', 'default_mr' => 'शासन राजपत्रानुसार सर्वसाधारण सभेने ठरवलेल्या दराने (कमाल १२% वार्षिक) व्याज आकारावे.', 'default_en' => 'Society may charge simple interest at 12% p.a. or a lower rate fixed by the General Body, per the government gazette.'),
            array('code' => 'c_repair_fund', 'mr' => 'दुरूस्ती व देखभाल निधी निर्मिती', 'en' => 'Creation of Repairs & Maintenance Fund', 'default_mr' => 'उपविधीनुसार प्रत्येक सदनिकेच्या बांधकाम किमतीच्या ०.७५% दराने निधी उभारावा.', 'default_en' => 'The Repairs & Maintenance Fund should be built up @ 0.75% p.a. of the construction cost of each flat.'),
            array('code' => 'c_sinking_fund', 'mr' => 'सिंकिंग फंड निर्मिती', 'en' => 'Creation of Sinking Fund', 'default_mr' => 'उपविधीनुसार प्रत्येक सदनिकेच्या बांधकाम किमतीच्या ०.२५% दराने निधी उभारावा.', 'default_en' => 'The Sinking Fund should be built up @ 0.25% p.a. of the construction cost of each flat.'),
            array('code' => 'c_education_fund', 'mr' => 'शिक्षण व प्रशिक्षण निधी निर्मिती', 'en' => 'Creation of Education & Training Fund', 'default_mr' => 'सभासदांकडून दरमहा रू.१०/- प्रति सदनिका किंवा सर्वसाधारण सभेने ठरवलेल्या दराने निधी उभारावा.', 'default_en' => 'Build the Education & Training Fund from a member contribution of Rs. 10 per month/unit or as decided by the General Body.'),
            array('code' => 'c_unreconciled', 'mr' => 'न जुळलेली रक्कम', 'en' => 'Unreconciled Amount', 'default_mr' => 'संस्थेने योग्य हिशोब पुस्तके ठेवावीत.', 'default_en' => 'Society must maintain proper account books.'),
            array('code' => 'c_investment_provision', 'mr' => 'गुंतवणूक तरतुदी', 'en' => 'Investments Provisions', 'default_mr' => '"अ" वर्ग मिळालेल्या राज्य/जिल्हा मध्यवर्ती सहकारी बँक किंवा शेड्युल्ड बँकेत गुंतवणूक करावी.', 'default_en' => 'Society has to invest in a State/District Central Co-op Bank or a Scheduled Bank awarded "A" Audit Class.'),
            array('code' => 'c_rectification_report', 'mr' => 'दोष दुरूस्ती अहवाल', 'en' => 'Audit Rectification Report', 'default_mr' => 'संस्थेने दोष दुरूस्ती अहवाल सादर केला आहे.', 'default_en' => 'Society has submitted the Audit Rectification Report.'),
            array('code' => 'c_model_byelaw', 'mr' => 'आदर्श उपविधी स्वीकृती', 'en' => 'Model Bye-law', 'default_mr' => 'शासन परिपत्रकानुसार आदर्श उपविधी स्वीकारून भाग रक्कम रू.२५०/- वरून रू.५००/- करावी व इतर सुधारणा कराव्यात.', 'default_en' => 'Society must adopt the Model Bye-laws per the Government Circular - raising share value from Rs.250 to Rs.500 and other amendments.'),
            array('code' => 'c_tds', 'mr' => 'टीडीएस व आयकर', 'en' => 'TDS & Income Tax', 'default_mr' => 'संस्थेने देयकांवर टीडीएस कापून संबंधित प्राधिकरणाकडे भरणा करावा.', 'default_en' => 'Society has to deduct TDS on payments and remit it to the respective authority.'),
            array('code' => 'c_federation', 'mr' => 'फेडरेशन सदस्यत्व', 'en' => 'Federation Membership', 'default_mr' => 'जिल्हा हाऊसिंग फेडरेशनचे सदस्यत्व घ्यावे.', 'default_en' => 'Society has to take membership of the district housing federation.'),
            array('code' => 'c_promoter_bank', 'mr' => 'प्रवर्तक बँक खाते हस्तांतरण', 'en' => 'Chief Promoter Bank Account', 'default_mr' => 'प्रवर्तकाचे बँक खाते संस्थेच्या नावे हस्तांतरित करावे.', 'default_en' => 'Chief Promoter Bank Account has to be transferred to the Society\'s name.'),
        );
    }

    // Statutory Report u/s 81(2) - Schedules I to V (the newer, five-schedule
    // rendering of the same statutory requirement the Nine-Point Statement
    // above covers in the older format). Both are included since either may
    // be what a given registrar's office expects.
    public static function statutorySchedules() {
        return array(
            array('code' => 's1', 'mr' => 'अधिनियम, नियम व उपविधीतील तरतुदींचे उल्लंघन झालेल्या व्यवहारांचा तपशिल', 'en' => 'Transactions involving infringement of the provisions of the Act, Rules and Bye-laws', 'default_mr' => 'सामान्य सूचना व शेरे पहा.', 'default_en' => 'See General Remarks.'),
            array('code' => 's2', 'mr' => 'जमा करावयाच्या पण जमा न केलेल्या रकमांचा तपशिल', 'en' => 'Particulars of sums which ought to have been but have not been brought into account', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 's3', 'mr' => 'अयोग्य व अनियमित देयके', 'en' => 'Improper and irregular payments', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 's4', 'mr' => 'संशयास्पद कर्जांची यादी', 'en' => 'List of doubtful debts', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
            array('code' => 's5', 'mr' => 'वसुलीसाठी संशयास्पद समजल्या जाणाऱ्या जंगम, स्थावर मालमत्ता व इतर मत्तेची यादी', 'en' => 'List of movable & immovable property and other assets considered doubtful of realization', 'default_mr' => 'निरंक', 'default_en' => 'NIL'),
        );
    }

    // Form No. 28, Part II. Consolidated the same way Form No. 1 (part1())
    // is - one row per top-level numbered item from the source form rather
    // than every roman-numeral sub-question, since most sub-answers collapse
    // to the same NA/YES for a given society.
    public static function formNo28PartII() {
        return array(
            array('code' => 'f28_borrowings', 'section' => 'I', 'mr' => 'शासन व इतर संस्थांकडून घेतलेली बाहेरील कर्जे (मंजूर रक्कम, उचल, परतफेड, थकबाकी, अटींचे पालन)', 'en' => 'Outside borrowings from Government & other agencies (amount sanctioned, drawn, repaid, overdue, compliance with conditions)', 'default_mr' => 'लागू नाही.', 'default_en' => 'Not applicable.'),
            array('code' => 'f28_govt_assistance', 'section' => 'II', 'mr' => 'शासकीय आर्थिक सहाय्य - मंजूर व प्राप्त रक्कम, जमीन सुधारणा अनुदान', 'en' => 'Government financial assistance - amount sanctioned/received, land development subsidy', 'default_mr' => 'लागू नाही.', 'default_en' => 'Not applicable.'),
            array('code' => 'f28_membership_special', 'section' => 'III', 'mr' => 'सभासदत्व - मागासवर्गीय/अनुदानित औद्योगिक गृहनिर्माण तरतुदी व स्वतःच्या घराबाबत प्रतिज्ञापत्र', 'en' => 'Membership - backward-class/subsidized-industrial housing provisions, and declaration of not owning other property', 'default_mr' => 'संस्था मागासवर्गीय नाही, त्यामुळे लागू नाही.', 'default_en' => 'Society is not a backward-class society, hence not applicable.'),
            array('code' => 'f28_land_development', 'section' => 'IV', 'mr' => 'जमीन व त्यांचा विकास - जमीन संपादन, हक्कपत्रे, वापर, मंजूर आराखडे व पूर्तता प्रमाणपत्रे', 'en' => 'Lands and their development - acquisition, title deeds, land use, approved layouts and completion certificates', 'default_mr' => 'CIDCO कडून विकसित व हस्तांतरित.', 'default_en' => 'Developed and handed over by CIDCO.'),
            array('code' => 'f28_construction', 'section' => 'V', 'mr' => 'इमारत बांधकाम - सुरूवात/पूर्तता, कंत्राट अटी, नकाशे मंजुरी, विमा, घसारा, भाडे/शुल्क आकारणी आधार', 'en' => 'Construction of buildings - commencement/completion, contract terms, plan approvals, insurance, depreciation, basis for rent/charges', 'default_mr' => 'बांधकाम पूर्ण झाले असून सदनिका सभासदांना दिलेल्या आहेत.', 'default_en' => 'Construction is complete and flats have been allotted to members.'),
            array('code' => 'f28_loans_to_members', 'section' => 'VI', 'mr' => 'सभासदांना दिलेली कर्जे - वसुलीचे वक्तशीरपण, थकबाकी व वसुली प्रयत्न', 'en' => 'Loans to members - punctuality of recovery, overdue amounts and recovery steps', 'default_mr' => 'लागू नाही.', 'default_en' => 'Not applicable.'),
            array('code' => 'f28_expenditure', 'section' => 'VII', 'mr' => 'खर्च - व्यवस्थापन समितीने वेळोवेळी मान्यता दिली आहे काय', 'en' => 'Expenditure - approved by the Managing Committee from time to time', 'default_mr' => 'होय.', 'default_en' => 'Yes.'),
        );
    }

    // Statutory Auditor's Report - the opinion section. Fixed boilerplate
    // (Section 81 / Rule 69(3) wording) with only the audit-period dates and
    // classification varying, so it's one block of text per language rather
    // than a question list.
    public static function auditorsOpinionParagraphs($isMr) {
        if ($isMr) {
            return array(
                'हिशोबाच्या पुस्तकांवरून व आम्हास मिळालेल्या माहिती व खुलाश्यांनुसार, वरील ताळेबंद व उत्पन्न-खर्च पत्रक महाराष्ट्र सहकारी संस्था अधिनियम १९६० व त्याअंतर्गत नियम, तसेच निबंधकांनी वेळोवेळी काढलेली परिपत्रके यांना अनुसरून, सर्वसाधारणपणे भारतात स्वीकारल्या जाणाऱ्या हिशोब तत्त्वांशी सुसंगत अशी खरी व योग्य स्थिती दर्शवितात.',
                'संस्थेचे हिशोब पुस्तके व्यवस्थित ठेवलेली असून, तपासणीत आढळलेल्या त्रुटी व शेरे "सामान्य सूचना व शेरे" या भागात नमूद केले आहेत.',
                'लेखापरिक्षण कालावधीकरिता संस्थेला "अ" वर्ग देण्यात येत आहे.',
            );
        }
        return array(
            'On the basis of the books of account and the information and explanations given to us, the said Balance Sheet and Income & Expenditure Account, read with the notes thereon, give a true and fair view in conformity with the accounting principles generally accepted in India, and comply with the Maharashtra Co-operative Societies Act 1960, the Rules thereunder, and circulars issued by the Registrar from time to time.',
            'The Society has kept proper books of account, and the discrepancies noticed during our examination have been recorded under General Remarks.',
            'For the year under audit, the Society has been awarded "A" classification.',
        );
    }
}
