{{-- Carried over from CakePHP app/View/SocietyBills/email_member_bills.ctp: the whole file, unchanged --}}
<?php
$paymentModeArray = array(1=>'Cash',3=>'Cheque',2=>'NEFT',4=>'Other');
$societyLedgerHeadIds = array_keys($societyLedgerHeadTitleList);
$monthArray = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    
	<style type='text/css'>
		@page bill {
  size: legal portrait;
  /*margin: 2cm;*/
}

.society_print_bill {
   page: bill;
   page-break-after: always;
}

/* half-page (2 bills per sheet) variant, selected via the Print Bill modal's
   Bill Format dropdown - mirrors .society-print-bill-half in custom_society.css */
.society_print_bill_half {
   page: bill;
   page-break-inside: avoid;
}
.society_print_bill_half .bill-outer-border {
   overflow: visible;
}
.society_print_bill_half:nth-of-type(2n):not(:last-child) {
   page-break-after: always;
}



/* Parsley Validation */
.parsley-errors-list {
    margin-top: 4px;
    padding: 0;
}
.parsley-errors-list li {
    padding: 0;
    list-style: none;
    color: #cc0000;
}
.parsley-error {
    border-color: #c00 !important;
}
.parsley-errors-list{
    margin: 0px 0px 0px 0px !important;
}
.required{
    color: #e9322d;
}
.display-none{
    display: none;
}

.span_message{color: #d01515;}

.ui-autocomplete{
    float : left; 
    clear : both; /* make sure it doesn't pull the next line up next to it */
    position : relative;  /* z-index will not work without this */
    z-index : 50;
    list-style : none;
    background-color: #e0e0e0;
    border: solid 1px lightblue;
    text-align : left;
    overflow-y : scroll;
    max-height : 100px;
    margin : 0px; /* pull the list items over the left edge */
    padding : 0px;
}

/*Society Dashboard Top Menus*/
.dash-top-header{
    height:auto;
    margin-left: 25px;
    border:0px solid #f5f5f5;
    line-height:160%;
    padding:8px;
    margin-top:12px;
    overflow:hidden;
    display:block;
}

.dash-top-header-menu {	
    width:200px;
    display:inline;   
}
.dash-top-header-menu a{
    border:0px solid #cfcfcf;
    padding:10px;
    /*background:#09a2bd;*/
    color:#fff;
    background:#b10058;
    border-radius:2px;
    line-height:160%;	
    width:10px;
}
/*.dash-top-header-menu a:hover{   
        background:#066374;
        color:#fff;
    }
*/	
@media (max-width: 1023px) {
    .dash-top-header{
        display:none;
    } 
}
/*End*/

.font-50{font-size: 50px !important;}
.font-40{font-size: 40px !important;}
.font-30{font-size: 30px !important;}

/*Society Dashboard at small screen*/
.dash-prameter-lists{
    margin-left:-20px;
    border:0px solid #f5f5f5;
    overflow:hidden;
    display:block;
    padding:15px;
}
.dash-top-header-menu-list a{
    border:0px solid #cfcfcf;
    padding:10px;
    background:#b10058;
    color:#fff;
    border-radius:2px;
    line-height:160%;	
}
.dash-top-header-menu-list a:hover{   
    background:#b10058;
    color:#fff;
}
@media (min-width: 1022px) {
    .dash-prameter-lists{
        display:none;
    } 
}
@media (max-width: 668px) {  
    .dash-top-header-menu-list a{    
        display:inline-block;
        overflow:hidden;
    }
}	
/*End*/
.img-responsive, .thumbnail > img, .thumbnail a > img, .carousel-inner > .item > img, .carousel-inner > .item > a > img {
    display: block;
    width: 100%;
    height: auto;
}

/*common to all*/
.btns{padding:6px;margin-top:2px;}
.txt-black{color:#555;}
.font-weight-bold{font-weight:bold;}
.mr-top-20{margin-top:20px;}
.border-top-bottom-bold{border-bottom:1px solid #444 !important;border-top:1px solid #444 !important;}
.simple-border{border:1px solid #cfcfcf;}
.simple-border-dotted{border:1px solid #cfcfcf;border-style: dashed;}
.border-top-bold{border-top:1px solid #444 !important;}
.margin-top-3{margin-top:30px;}
.margin-top-2{margin-top:20px;}
.border-1{border:1px solid red;}
.border-2{border:1px solid blue;}
.border-3{border:1px solid green;}
.border-4{border:1px solid yellow;}
.border-5{border:1px solid lime;}
.border-6{border:1px solid black;}
.line-break{margin-top:10px;margin-bottom:10px;border:1px solid #ccc;border-width: 100%;}
.color-333{color:#333 !important;}
.bg-white{background: #fff;}
.table{
    margin-bottom: 0px;
}
.table > thead {
    background: #d2d2d2;
}
.table > tfoot > tr > td{
    padding:5px !important;
    background: #d2d2d2;
    color:#333;
}
.opening-bal{color:#0066cc !important;font-weight: 500;}
#member-bank-details{font-size:15px;font-weight:400;color:#b10058;margin-bottom:5px;}
.branch-textarea textarea{resize: none;}
.payment_voucher_outer_border{border: 0px solid #cfcfcf;padding:15px;margin-bottom: 10px;}
.member-passbook-heading{text-align:center;color:#000;font-size:15px;margin:15px 0px 5px 0px;}
.account-payment-register-heading{text-align:center;color:#000;font-size:15px;margin:15px 0px 5px 0px;}

/*.brand-img{;margin-top:-5px;}
.brand-text{font-size:16px !important;border:0px solid red;overflow: hidden !important;margin-top:-5px;}*/

/*member details design*/
.member-details-box{display:block;overflow:hidden;border:1px solid #cfcfcf;padding:15px 10px 15px 10px;margin-bottom:25px;}
.member-data{border:1px solid #e5e5e5;padding:10px;}
.member-head{font-size:14px;color:#333;}
.nopadding {
    padding: 0 !important;
    margin: 0 !important;
} 
.no-padding-right {
    padding-right:  0 !important;
    margin-right: 0 !important;
}
.no-padding-left {
    padding-left:  0 !important;
    margin-left: 0 !important;
}
.padding-0-margin-right{
    padding: 0 !important;
    margin-top:0px;
    margin-right:1px !important;
    margin-left:0px !important;
    margin-bottom:0px !important;
}
.text-right{text-align:right;}
.input-height-30{height:30px !important;}
.no-gutter {margin-left: 0;margin-right: 0;}
.padding-0{padding-right:4px;padding-left:4px;}
.padding-1{padding-right:4px;padding-left:8px;}
.padding-2{padding-right:8px;padding-left:4px;}
.padding-3{padding-right:0px;padding-left:0px;}
.padding-th-none-2 > thead > tr > th {padding:2px;}
.padding-td-none-2 > tbody > tr > td {padding:2px;color:#333;}
.padding-th-none > thead > tr > th {padding:8px;}
.padding-td-none > tbody > tr > td {padding:5px;color:#333;}
.padding-th-none-zero > thead > tr > th {padding:0px !important;}
.padding-td-none-zero > tbody > tr > td {padding:0px !important;color:#333;}

/* Modal window body-background color */
.modal-header-bg{background:#dc0030;padding: 10px;}
.modal-title-color{color:#fff;}
.modal-content {overflow:hidden;margin-bottom:20px;}
.modal-body-bg{background: #f8f8f8;border:1px solid #f8f8f8;margin:15px;}
.modal-form-btn{margin-top:20px;border:0px solid red;}

/*Print bill deign*/
.address-heading{text-align:center;font-size:13px;}
.bill{text-align:center;font-size:16px;color:#000000;}
.bill-outer-border{border:1px solid #c8c8c8;display:block;overflow:hidden;height:auto;}
.bill-member-details-section{padding:10px;}
.bill-padding-5{padding:5px;}
.bill-padding-10{padding:10px;}
.receipt-title{text-align:center;font-size:16px;margin:15px 15px;color:#000000;}

/* next previous buttons on modals design */
.previous-btn-list{    
    display: block;
    overflow: hidden;
    position: absolute;
    top: 250px;
    left:0px;
    height:50px;
    background: #f2b701;
    border:0px solid green;
    color:#000;
    font-weight: 900;  
    padding-top:12px;
}
.next-btn-list{
    display: block;
    border:0px solid blue;
    overflow: hidden;
    position:absolute;
    top:250px;
    right:0px;
    height:50px;
    background: #f2b701;
    color:#333;
    font-weight: 600;  
    padding-top:12px;
}
/* display box of showin outstanding amount of members payments */
.outstanding-pay-box{background:#eee;display:block;overflow:hidden;margin-left:15px;padding:15px;}
/* .outstanding-title{font-size:16px;background: #dfdfdf;padding:5px;text-align:center;margin-bottom:25px;}*/

/* scoeity Member tariff design */
.member-tariffs-member-name{font-weight:600;}
.member-tariff-left-box{border:1px solid #ccc;padding:15px;}
.member-tariff-right-box{border:1px solid #ccc;padding:15px;height:440px;display:block;overflow:hidden;background: #eee;}

/* society_head_sub_categories design */
.society-head-sub-cat-frm{border:1px solid #d9d9d9;padding:10px;}

/* add_society_ledger_heads */
.bill-tariff-deatils-head{display:block;font-weight: 600;color:#333; margin-left:15px;}
.bill-tariff-deatils-label-box{border:0px solid red;padding:10px;margin-bottom:20px;}

/* modal window society_ledger_heads_modal form field design */
/*
.form-control-label{
    border:0px solid green;
    margin-left: -25px;
    padding-top: 7px;
    margin-bottom: 0;
    text-align:right;
}
.form-control-label2{
    border:0px solid green;
    padding-top: 7px;
    margin-bottom: 0;
    text-align:right;
}

*/


/* modal window Ledger Heads form field design */
.modal-ledger-form-box{border:0px solid red;height:auto;overflow: hidden;display: block;}
.modal-ledger-form-field-box{border:0px solid #000;color:#333;margin-bottom:2px;overflow:hidden;padding:2px;width:100%;float:left;}
.modal-ledger-form-box-label{border:0px solid green;color:#000;float:left;text-align: right;}
.modal-ledger-form-box-field-85{width:85%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-box-field-70{width:70%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-box-field-60{width:60%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-box-field-50{width:50%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-box-field-40{width:40%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-box-field-date{width:70%;color:#333;float:left;height:30px !important; }
.modal-ledger-form-box-field-dropdown{width:65%;color:#333;float:left;height:30px !important;}
.modal-ledger-form-btn .btns{padding:6px;margin-top:15px;}
.readonly{color:#333;background: #cfcfcf;border: none;padding:2px;}
.modal-ledger-tariff-head{color:#333;font-weight:400;color:#000;}

/* modal window society_payment_entry_modal form field design */
.modal-society-payment-form-box{border:0px solid red;height:auto;}
.field-box-1{border:0px solid #000;color:#333;float:left;overflow:hidden;width:180px;}
.modal-form-pay-entry-box-label{border:0px solid green;color:#000;float:left;}
.modal-form-pay-entry-box-date{width:130px;color:#333;float:left;height:30px !important;}
.field-box-2{border:0px solid #000;color:#333;float:left;overflow:hidden;width:140px; }
.modal-society-payment-field-dropdown-60{width:60%;color:#333;float:left;height:30px !important;}
.modal-society-payment-field-dropdown-70{width:70%;color:#333;float:left;height:30px !important;}
.numeric-field{border:1px solid #999;color:#333;float:left;width:60px;padding:2px;text-align: right;height:30px !important;}
.numeric-field-65{border:1px solid #999;color:#333;float:left;width:65px;padding:2px;text-align: right;height:30px !important;}
.field-box-3{border:0px solid #f99;color:#333;float:left;overflow:hidden;width:120px; }
.field-box-4{border:0px solid #f99;color:#333;float:left;overflow:hidden;width:110px; }
.modal-society-payment-form-btn{border:0px solid #000;}
.modal-society-payment-form-btn .btns{padding:6px;}

/* modal window society_flat_shop_details form field design */
.modal-flat-shop-form-box{border:0px solid red;height:auto;overflow: hidden;display: block;}
.modal-flat-shop-form-field-box{border:0px solid #000;margin-bottom:2px;overflow:hidden;padding:2px;width:100%;float:left;}
.modal-flat-shop-form-box-label{border:0px solid green;color:#000;float:left;text-align: left;}
.modal-flat-shop-form-box-field-85{width:85%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-70{width:70%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-60{width:60%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-50{width:50%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-40{width:40%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-30{width:30%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-20{width:20%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-date{width:70%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-dropdown{width:65%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-btn .btns{padding:6px;margin-top:15px;}
.readonly{background: #cfcfcf;border: none;padding:2px;}
.modal-ledger-tariff-head{font-weight:400;color:#000;}

/*Tabs Style of all Modals Pages*/
/*style="background:#a6f8f8;"*/
.tab-body-bg{background: #fff !important;}
.tab-active-bg a{background:#fff !important;color:#333 !important;}
.tab-active-bg a:hover{color:#333 !important;}
.tab-active-bg a:focus{background: #fff !important;color:#333 !important;}
/*.tab-active-bg a:not(:focus){background: #f8f8f8 !important;color:#333 !important;}*/
.tab-bg a{color:#333 !important;background: #f8f8f8 !important;}
.tab-bg a:hover{color:#333 !important;}
.tab-bg a:focus{background: #fff !important;color:#333 !important;}

/*.table-striped1 > tbody > tr:nth-of-type(odd) {
  background-color: #80ffff;
}*/


/*Date on bill*/
.bill-date{float:right !important;margin-right: 25px;color:#333;margin-top:2px;font-size: 15px;}
.font-weight-600{font-weight:600;color:#333;}

/*Society Reports*/
.left-section{border:1px solid #ccc;padding:15px;}
.right-section{border:1px solid #cfcfcf;}
.right-title{display:block;overflow:hidden;margin:5px;font-size:15px;font-weight:400;color:#333;}
.society-report-form-box-label{border:0px solid green;color:#000;float:left;text-align: left;}
.society-report-form-field-box{border:0px solid #000;color:#333;margin-bottom:2px;overflow:hidden;padding:2px;width:100%;float:left;}
.society-report-form-box-field-80{width:80%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-70{width:70%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-60{width:60%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-50{width:50%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-40{width:40%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-30{width:30%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-20{width:20%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-dropdown{width:70%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-dropdown-50{width:50%;color:#333;float:left;height:30px !important;}
.society-report-form-box-field-date{width:60%;color:#333;float:left;height:30px !important; }
.society-report-form-box-field-date-70{width:70%;color:#333;float:left;height:30px !important; }
.society-report-form-box-field-date-65{width:67%;color:#333;loat:left;height:30px !important; }
.society-report-form-box-field-date-60{width:60%;color:#333;float:left;height:30px !important; }
.society-report-form-box-field-date-50{width:50%;color:#333;float:left;height:30px !important; }
.society-report-form-box-field-date-45{width:45%;color:#333;float:left;height:30px !important; }
.society-report-form .btns{padding:6px;margin-top:6px;margin-right: 2px;}

/*Account Reports*/
.right-title{display:block;overflow:hidden;margin:5px;font-size:15px;font-weight:400;color:#333;}
.account-report-form-box-label{border:0px solid green;color:#000;float:left;text-align: left;}
.account-report-form-field-box{border:0px solid #000;color:#333;margin-bottom:2px;overflow:hidden;padding:2px;width:100%;float:left;}
.account-report-form-box-field-80{width:80%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-70{width:70%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-60{width:60%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-50{width:50%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-40{width:40%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-30{width:30%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-20{width:20%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown-70{width:70%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown-80{width:80%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown-65{width:65%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown{width:60%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown-40{width:40%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-dropdown-50{width:50%;color:#333;float:left;height:30px !important;}
.account-report-form-box-field-date{width:60%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-75{width:75%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-70{width:70%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-65{width:67%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-60{width:60%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-50{width:50%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-45{width:45%;color:#333;float:left;height:30px !important; }
.account-report-form-box-field-date-40{width:40%;color:#333;float:left;height:30px !important; }
.account-report-form .btns{padding:6px;margin-top:2px;}

/*Member Receipt Table Design*/
.td-editable{border:none;background-color: #f8f8f8;}

/*Cheque Clear Dates*/
.cheque-clear-form-box-field-dropdown{width:100%;display:block;color:#000000 !important;height:30px !important;}


.allMemberTariff{display: none;}
.allMemberTariffShow{display: block;}


/* Repoets bill deign*/
table .table-print-all-bill{
    table-layout: fixed !important;
    width: 100%;
}
.report-address-heading{text-align:center;font-size:13px;}
.report-bill{text-align:center;font-size:16px;color:#000000;}
.report-bill-outer-section{border:0px solid #c8c8c8;}
.report-receipt-title{text-align:center;font-size:16px;color:#000000;}


/* report print button*/
.report-print-btn a{background:#0f4fa8; color:#fff;}
.report-print-btn a:hover{background:#0f4fa8; color:#ffff66;}
.print-btn {
    display: inline-block;
    padding: 2px 8px; 
    margin: 0px 5px 5px 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    border: 1px solid transparent;
    border-radius: 4px;
}

/* report export to pdf button*/
.report-export-pdf-btn a{background:#0f4fa8; color:#fff;}
.report-export-pdf-btn a:hover{background:#0f4fa8; color:#ffff66;}
.export-pdf-btn {
    display: inline-block;
    padding: 2px 8px; 
    margin: 0px 20px 5px 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    border: 1px solid transparent;
    border-radius: 4px;
}

/* dues-advance-report-table */ 
.no-border-thead table > thead{    
    border-top: 1px solid #dfdfdf !important;
    border-bottom: 1px solid #dfdfdf !important;
}
.no-border-thead table > tbody > tr > td{    
    border-bottom: 1px solid #dfdfdf !important;
}

/*print all report section*/    
@page{    
    margin-top:0px;
    margin-left:25px;
    margin-bottom:0px;
    margin-right:25px;
}
@media print {
    .page-break{page-break-inside: avoid;}
    .page-break-print{page-break-inside: auto;}
    /* new-bill-page-break reports */
    .bill-half-page{
        page-break-inside: avoid;
        font-size:12px;
        padding: 20px !important;
    }
    .bill-full-page{
        page-break-after: always;
        font-size:12px;
        padding: 20px !important;
    }

    /* account_bank_book reports */
    .print-bank-book{
        padding: 20px !important;
        font-size:12px !important;
    }    
    /* account_cash_book reports */
    .print-cash-book{
        padding: 20px !important;
    } 

    /* account_dues_from_member reports */
    .print-dues-from-member{
        padding: 20px !important;
        font-size:12px !important;
    }    
    /* account_advance_receive_from_member reports */
    .print-dues-advance-from-member{
        padding: 20px !important;
        font-size:12px !important;
    }    
    /* account_print-member-ledger reports */
    .print-member-ledger{
        padding: 20px !important;
        font-size:12px !important;
    }    
    /* account_print-general-ledger reports */
    .print-general-ledger{
        page-break-after: always;
        padding: 20px !important;
        font-size:12px !important;
    }    
    /* account_income_exp_statement reports */
    .print-income-exp-statement{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* account_balance_sheet reports */
    .print-account-balancesheet{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* account_payment_register reports */
    .print-account-payment-register{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* account_member_monthly_contribution reports */
    .print-member-monthly-cotribution{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* gst-bill reports */
    .print-gst-bill{
        page-break-after: always;
        padding: 20px !important;
        font-size: 12px !important;
    }
    /* print-bank-slip reports */
    .print-bank-slip{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* print_journal_voucher reports */
    .print_journal_voucher{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* print_member-list reports */
    .print_member-list{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* print-collection-sheet reports */
    .print-collection-sheet{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* print-trial-balance reports */
    .print-trial-balance{
        padding: 20px !important;
        font-size:12px !important;
    }
    /* print-bill-register reports */
    .print-bill-register{
        font-size:10px;
        padding: 20px !important;
    }
    .print-bill-register table{
        width:100% !important;
    }
    /* print-gst-register reports */
    .print-gst-register{
        page-break-inside: auto;
        font-size:12px;
        padding: 20px !important;
    }
    .print-list-of-members{
        padding: 20px !important;
    }
    .print-list-of-tenants{
        padding: 20px !important;
        font-size:12px !important;
    }
    .print-member-bill{
        page-break-after: always;
        font-size:12px;
        padding: 20px !important;
    }
    .print-society-receipt-voucher{
        page-break-inside: avoid;
        font-size:12px;
        padding: 20px !important;
    }
    .print-society-payment-voucher{
        page-break-inside: avoid;
        font-size:12px;
        margin-top: 20px !important;;
    }
    .print-society-outstanding-bill{
        font-size:12px;
        padding: 20px !important;
    }
    .society-print-bill{
        page-break-after: always;
        font-size:12px;
        padding: 20px !important;
    }
    .print-bill-with-receipt{
        page-break-after: always;
        font-size:12px;
        padding: 20px !important;
    }
    .print-memberwise-tariff{
        font-size:12px;
        padding: 20px !important;
    }
    .print-member-passbook{
        font-size:12px;
        padding: 20px !important;
    }
    .print-ledger-heads{
        font-size:12px;
        padding: 20px !important;
    }
    .print-individual-member-ledger{
        font-size:12px;
        padding: 20px !important;
    }
    .society-report-form-field-box{
        font-size:12px;
        padding: 20px !important;        
    }
    .print-member-collection-register{
        font-size:12px;
        padding: 20px !important;        
    }
    .print-receipt-payment{
        font-size:12px;
        padding: 20px !important;        
    }
    .print-bank-reconciliation{   
        font-size:12px;
        padding: 20px !important;            
    }
    .hide-in-print{display: none;}
    .print-int-with-gst-bill{ 
        page-break-after: always;
        font-size:12px;
        padding: 20px !important;     
    }
    /* custom border while print the page */
    .print-custom-table-border td, .print-custom-table-border th{ 
    border-top: 1px solid #515151 !important;
    border-right:1px solid #515151 !important;
    border-left:1px solid #515151 !important;
    border-bottom:1px solid #cfcfcf !important;
    }
     /* custom border while print the page */
    .bill-outer-border{border:1px solid #515151 !important;display:block;overflow:hidden;height:auto;}    
    .padding-th-none-zero > thead > tr > th {border-bottom:1px solid #515151 !important;}    
    .padding-th-none > thead > tr > th {border-bottom:1px solid #515151 !important;}
    .padding-td-none > tbody > tr > td {border-bottom:1px solid #515151 !important;}    
    .print-custom-table-border tfoot td{border-bottom:1px solid #515151 !important;}
}

/* receipt voucher and payment voucher section design */
.receipt-voucher-section{border:1px solid #cfcfcf;margin:15px;padding:15px;}
.payment-voucher-section{border:1px solid #cfcfcf;margin:15px;padding:15px;}

/*get society details while print the flat-shop details individually*/
.societyinfo-section{border:0px solid red;overflow: hidden;display: none;}
.societyinfo-section .report-bill{text-align:center;font-size:16px;color:#000000;margin-top:15px;}
.member-name{float: left;margin-right:20px;color:#000000;}
.member-name span{color:#000000;}
.building-name{float: left;margin-right:20px;color:#000000;}
.building-name span{color:#000000;}
.wing-name{float: left;margin-right:10px;color:#000000;}
.wing-name span{color:#000000;}
.flat-no{float: left;margin-right:10px;color:#000000;}
.flat-no span{color:#000000;}

/* visible  section after print command in FlatShopReports */ 
.societyinfo-section-visible{border:0px solid red;overflow: hidden;display: block;}

/* special field text design */ 
.special-field-text{font-style: italic;font-weight: bold;text-align: center;}  


	</style>

</head>
<body>

    <?php foreach($printBillDetails as $memberId=>$printBill) {
        $societyDetails = $printBill['memberDetails']['Society'];
        $memberDetails = $printBill['memberDetails']['Member'];
        $buildingDetails = $printBill['memberDetails']['Building'];
        $wingDetails = $printBill['memberDetails']['Wing'];
        $billSummary = $printBill['MemberBillSummary'];
        $tariff = $printBill['tariff'];
        $memberReceipts = $printBill['receipts'];
    ?>
	
	<div class='<?php echo ($billFormat == 'half') ? 'society_print_bill_half' : 'society_print_bill'; ?>'>
        <div>
            <div>                                        
                <div class="bill-outer-border">
                    <div class="row">
                        <h5 class="text-center address-heading"><b><?php echo ucwords($societyDetails['society_name']);?></b></h5>
                        <div class="address-heading">Registration No. <?php echo $societyDetails['registration_no'];?> Dated: <?php echo date('d/m/Y',strtotime($societyDetails['registration_date']));?></div>
                        <div class="address-heading"><?php echo $societyDetails['address'];?></div>
                    </div>
                    <div class="bill"><b>Maintenance Bill</b></div>
                    <div class="simple-border"></div>
                    <div class="row">
                        <table class="table" style="width:102%" >
                            <tr>
                                <td>Unit No : <strong><?php echo $memberDetails['flat_no'];?></strong></td>
                                <td><!-- : <?php // echo $memberDetails[''];?> --></td>
                                <td>Unit type : <?php if($memberDetails['unit_type'] == 'C') { echo 'Commercial';} else if($memberDetails['unit_type'] == 'R') { echo 'Residential';} else if($memberDetails['unit_type'] == 'B') { echo 'Both';} else { echo $memberDetails['unit_type']; }?></td>
                                <td><strong>Bill No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<?php echo $billSummary['bill_no'];?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" >Name &nbsp;&nbsp; : &nbsp;<?php echo $memberDetails['member_prefix'].' '.$memberDetails['member_name'];?></td>
                                <td><strong>Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<?php echo date('d/m/Y',strtotime($billSummary['bill_generated_date']));?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3">Bill For &nbsp; : &nbsp;<?php echo $societyObj->monthWordFormatByBillingFrequency($billSummary['month']);?> <?php echo date('Y',strtotime($billSummary['bill_generated_date']));?></td>
                                <td><strong>Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<?php echo date('d/m/Y',strtotime($billSummary['bill_due_date']));?></strong></td>
                            </tr>
                            <tr>
                                <td>Wing &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;<?php echo $wingDetails['wing_name'];?></td>
                                <td>Floor No : <?php echo $memberDetails['floor_no'];?></td>                                                        
                            </tr>
                        </table>
                    </div>
                               
                    <div class="row1">
                        <div style="padding: 0px;">
                            <div class="table-wrap">
                                <div class="">
                                    <table style="width:100%;" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 5% !important;" >Sr.</th>
                                                <th colspan="3" class="text-center">Particular of Charges</th>
                                                <th class="text-center">Amount</th>
                                            </tr>
                                        </thead>
                                        <?php
                                            $tariffSr = 1;
                                            $memberBillTariff = array();
                                            foreach($tariff as $key=>$indTariff) {
                                            if($indTariff['MemberBillGenerate']['amount'] > 0) {
                                                    $memberBillTariff[] = $indTariff['MemberBillGenerate']['ledger_head_id'];
                                                    $ledgerHead = $ledgerHeadDetails[$indTariff['MemberBillGenerate']['ledger_head_id']];
                                                    $ledgerAmount = $indTariff['MemberBillGenerate']['amount'];
                                                    echo "<tr>
                                                            <td style='text-align:center;'>$tariffSr</td>
                                                            <td  colspan=\"3\" >&nbsp; $ledgerHead</td>
                                                            <td style='width:10%;' class='text-right'>";
                                                    echo number_format($ledgerAmount,2);
                                                    echo "</td>
                                                            </tr>";
                                                    $tariffSr++;
                                            }
                                        } 
                                        if(isset($societyParameters['SocietyParameter']['show_all_tariff_name']) && $societyParameters['SocietyParameter']['show_all_tariff_name'] == 1){                                                                            
                                                if(!empty($societyLedgerHeadIds)) {
                                                    $tariffsNotInBill = array_diff($societyLedgerHeadIds,$memberBillTariff);
                                                    if(!empty($tariffsNotInBill)) {
                                                        foreach($tariffsNotInBill as $headId) {                                                                        
                                                                $ledgerHead = $societyLedgerHeadTitleList[$headId];
                                                                $ledgerAmount = '0.00';
                                                                echo "<tr>
                                                                        <td style='text-align:center;width: 5% !important;'>$tariffSr</td>
                                                                       <td  colspan=\"3\" >&nbsp; $ledgerHead</td>
                                                                        <td style='width:14%;' class='text-right'>$ledgerAmount</td>
                                                                        </tr>";
                                                                $tariffSr++;
                                                         }
                                                    }
                                                }
                                            }
                                        ?>

                                        <tr>
                                            <td colspan="2" rowspan="5" style="width:50% !important;"> </td>
                                            <td colspan="2" >&nbsp; Total</td>
                                            <td class="text-right">
                                                <?php 
                                                    $totalTariffAmount =  $billSummary['monthly_amount'] - $billSummary['tax_total'];
                                                    echo number_format($totalTariffAmount,2);
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" >&nbsp; Interest</td>
                                            <td class="text-right"><?php echo number_format($billSummary['interest_balance'],2); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" >&nbsp; Less: Adjustment</td>
                                            <td class="text-right"><?php echo number_format($billSummary['principal_adjusted']+$billSummary['interest_adjusted']+$billSummary['discount'],2);?></td>
                                        </tr>
                                        <tr>
                                            <td >&nbsp; Principal Arrears</td>                                                                    
                                            <td class="text-right" >
                                                <?php if($billSummary['op_due_amount'] < 0) { $posNeg = 'Cr'; }
                                                else { $posNeg = 'Dr'; }
                                                $billSummary['op_due_amount'] = abs($billSummary['op_due_amount']);
                                                echo number_format($billSummary['op_due_amount'],2).' '.$posNeg;
                                                ?>                                                                        
                                            </td> 
                                            <td class="text-right" rowspan="2">
                                                <?php
                                                $totalArrears = $billSummary['op_principal_arrears'];
                                                if($totalArrears < 0) { $posNeg = 'Cr'; }
                                                else { $posNeg = 'Dr'; }
                                                $totalArrears = abs($totalArrears);
                                                echo number_format($totalArrears,2).' '.$posNeg;
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp; Interest Arrears</td>
                                            <td class="text-right"><?php echo $utilObj->CreditDebitAmountCheck($billSummary['op_interest_arrears']) ;?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" >&nbsp; Rupees <?php echo ucwords($societyObj->numberTowords($billSummary['balance_amount']));?> Only</td>
                                            <td colspan="2" >
                                            <?php  if($billSummary['balance_amount'] < 0) { echo "<span class='font-weight-bold'>&nbsp; Excess Amount Received</span>";  } else { echo "<span class='font-weight-bold'>&nbsp; Total Due Amount &amp; Payable</span>"; } ?>
                                            </td>
                                            <td class="text-right">
                                            <?php  if($billSummary['balance_amount'] < 0) { $posNeg = 'Cr'; }
                                            else { $posNeg = 'Dr'; }
                                            $billSummary['balance_amount'] = abs($billSummary['balance_amount']);

                                            echo number_format($billSummary['balance_amount'],2).' '.$posNeg;
                                            ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 col-xs-12">
                        <strong>Notes: </strong>
                        <div><?php echo $societyParameters['SocietyParameter']['bill_note']; ?> </div> 
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12 col-xs-12">
                        <p class="pull-right">for <?php echo $societyDetails['society_name'];?>&nbsp;&nbsp; </p>
                        <br> <br>
                        <p class="pull-right"><?php echo isset($societyDetails['authorised_person']) ? $societyDetails['authorised_person'] : 'Authorised Signature'; ?>&nbsp;&nbsp;</p>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12 col-xs-12 special-field-text"><?php echo $societyParameters['SocietyParameter']['special_field']; ?></div>
                    <br>
                    <div class="row simple-border-dotted" style="padding:0px;"></div>

                    <div class="row bill-padding-5">
                        <div class="receipt-title">RECEIPT</div>
                        <div class="col-md-8 col-xs-8">Received with thanks from <strong><?php echo $memberDetails['member_prefix'].' '.$memberDetails['member_name'];?></strong></div>
                        <div class="col-md-4 col-xs-4 text-left">Unit No :<strong> <?php echo $memberDetails['flat_no'];?></strong></div>
                        <div class="clearfix"></div>
                        <?php if(isset($receiptPeriod['From']) && !empty($receiptPeriod['From']) && isset($receiptPeriod['To']) && !empty($receiptPeriod['To'])) { ?>
                        <div class="col-md-12">Details of payments received are as under : <strong>Period : <?php echo date('d/m/Y',strtotime($receiptPeriod['From']));?> To <?php echo date('d/m/Y',strtotime($receiptPeriod['To']));?></strong></div>
                        <?php } else { ?>
                        <div class="col-md-12">Details of payments received are as under : <strong>Period : 01/04/2022 To Till date</strong></div>
                        <?php } ?>
                    </div>
                    <div class="row1">
                        <div style="padding: 0px;">
                            <div class="table-wrap">
                                <div class="table-responsive">
                                    <table style="width:100%" class="table padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                        <thead>
                                            <tr>
                                                <th style="width:6%;" class="text-center">Receipt</th>
                                                <th style="width:10%;" class="text-center">Date</th>
                                                <th style="width:8%;" class="text-center">Chq No.</th>
                                                <th style="width:10%;" class="text-center">Chq Date</th>
                                                <th class="text-center">Bank &amp; Branch</th>
                                                <th style="width:18%;" class="text-center">Towards bill No.</th>
                                                <th style="width:8%;" class="text-center">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $receiptSr = 1;
                                            $memberTotalPaid = 0;
                                            
                                            foreach($memberReceipts as $key=>$receipt) {
                                                
                                                $receiptDetails = $receipt['MemberPayment'];
                                                
                                                if ((strtotime($receiptDetails['payment_date']) >= strtotime($receiptPeriod['From'])) && (strtotime($receiptDetails['payment_date']) <= strtotime($receiptPeriod['To']))){
                                                $paymentDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                $entryDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                $billFor = $societyObj->monthWordFormatByBillingFrequency($billSummary['month']);	// for $billFor removed from the bill generated id
    		                                    $paymentMode = $paymentModeArray[$receiptDetails['payment_mode']];
                                                echo "<tr>
                                                        <td class=\"text-center\">$receiptDetails[receipt_id]</td>
                                                        <td class=\"text-center\">$entryDate</td>
                                                        <td class=\"text-center\">$receiptDetails[cheque_reference_number]</td>
                                                        <td class=\"text-center\">$paymentDate</td>
                                                        <td>$receiptDetails[member_bank_id], $receiptDetails[member_bank_branch]</td>
                                                        <td class=\"text-center\">$receiptDetails[bill_generated_id]</td>
                                                        <td class=\"text-right\">".number_format($receiptDetails[amount_paid])."</td>
                                                       </tr>";
    
                                                $memberTotalPaid += $receiptDetails['amount_paid'];
                                                $receiptSr++;
                                                }
                                            } ?>
                                            <tr id="">
                                                <td colspan="5" >&nbsp; Rupees <?php echo ucwords($societyObj->numberTowords($memberTotalPaid));?> only</td>
                                            </tr>
                                        </tbody>
                                </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php } ?>

                                
</body>
</html>
