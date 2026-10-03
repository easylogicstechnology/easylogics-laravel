<?php

/*
|--------------------------------------------------------------------------
| Reports - Accounts (CakePHP: account_reports/account)
|--------------------------------------------------------------------------
| The buttons of the "Report - Accounts" page, in the order CakePHP shows them
| (app/View/AccountReports/account.ctp). One row per button:
|
|   slug          URL part of the placeholder page (society/report-accounts/{slug})
|   label         text on the button
|   cake          the CakePHP controller/action the button points to (reference only)
|   route         a Laravel route name that already exists for the same report; when
|                 set the button goes straight there, otherwise to the slug page
|   member_access CakePHP shows the button to a Member login only when this id is in the
|                 member's report_access list; null = hidden from Members. Society and
|                 Reseller logins see every button.
|
| The plain "Member Ledger" button (account_member_ledger, member_access 7) is commented
| out in the CakePHP view and is therefore not listed here.
*/
return [
    ['slug' => 'bank-book', 'label' => 'Bank Book', 'cake' => 'account_reports/account_bank_book', 'route' => 'society.reports.bankBook', 'member_access' => 3],
    ['slug' => 'bank-reconciliation', 'label' => 'Bank Reconciliation', 'cake' => 'account_reports/account_bank_reconciliation', 'action' => 'bankReconciliation', 'route' => null, 'member_access' => null],
    ['slug' => 'cash-book', 'label' => 'Cash Book', 'cake' => 'account_reports/account_cash_book', 'route' => 'society.reports.cashBook', 'member_access' => 4],
    ['slug' => 'dues-from-members', 'label' => 'Dues From Members', 'cake' => 'account_reports/account_dues_from_member', 'action' => 'duesFromMembers', 'route' => null, 'member_access' => 6],
    ['slug' => 'dues-advanced-from-members', 'label' => 'Dues-Advanced From Members', 'cake' => 'account_reports/account_dues_advance_from_member', 'action' => 'duesAdvanceFromMembers', 'route' => null, 'member_access' => 7],
    ['slug' => 'general-ledger', 'label' => 'General Ledger', 'cake' => 'account_reports/account_general_ledger', 'action' => 'generalLedger', 'route' => null, 'member_access' => null],
    ['slug' => 'income-expenditure-statement-details', 'label' => 'Income & Expenditure Statement Details', 'cake' => 'account_reports/account_income_exp_statement_details', 'action' => 'incomeExpDetails', 'route' => null, 'member_access' => 1],
    ['slug' => 'balance-sheet', 'label' => 'Balance Sheet', 'cake' => 'account_reports/account_balance_sheet', 'action' => 'balanceSheet', 'route' => null, 'member_access' => 1],
    ['slug' => 'statutory-audit-report', 'label' => 'Statutory Audit Report', 'cake' => 'audit_reports/edit', 'action' => 'auditReport', 'route' => null, 'member_access' => null],
    ['slug' => 'petty-cash-register', 'label' => 'Petty Cash Register', 'cake' => 'account_reports/account_petty_cash', 'action' => 'pettyCash', 'route' => null, 'member_access' => null],
    ['slug' => 'payment-register', 'label' => 'Payment Register', 'cake' => 'account_reports/account_payment_register', 'action' => 'paymentRegister', 'route' => null, 'member_access' => null],
    ['slug' => 'member-monthly-contribution', 'label' => 'Member Monthly Contribution', 'cake' => 'account_reports/account_member_monthly_contribution', 'action' => 'memberMonthlyContribution', 'route' => null, 'member_access' => null],
    ['slug' => 'member-ledger-default', 'label' => 'Member Ledger Default', 'cake' => 'account_reports/account_member_ledger_default', 'action' => 'memberLedgerDefault', 'route' => null, 'member_access' => 5],
    ['slug' => 'bill-half-page', 'label' => 'Bill Half Page', 'cake' => 'account_reports/bill_half_page', 'action' => 'billHalfPage', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-full-page', 'label' => 'Bill Full Page', 'cake' => 'account_reports/bill_full_page', 'action' => 'billFullPage', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-tax-invoice-gst', 'label' => 'Bill Tax Invoice(GST)', 'cake' => 'account_reports/bill_tax_invoice_gst', 'action' => 'billTaxInvoiceGst', 'route' => null, 'member_access' => null],
    ['slug' => 'bank-slip', 'label' => 'Bank Slip', 'cake' => 'account_reports/account_bank_slip', 'action' => 'bankSlip', 'route' => null, 'member_access' => null],
    ['slug' => 'journal-voucher-register', 'label' => 'Journal Voucher Register', 'cake' => 'account_reports/account_journal_voucher_register', 'action' => 'journalVoucherRegister', 'route' => null, 'member_access' => null],
    ['slug' => 'member-list', 'label' => 'Member List', 'cake' => 'account_reports/account_member_list', 'action' => 'memberList', 'route' => null, 'member_access' => null],
    ['slug' => 'collection-sheet', 'label' => 'Collection Sheet', 'cake' => 'account_reports/account_collection_sheet', 'action' => 'collectionSheet', 'route' => null, 'member_access' => null],
    ['slug' => 'trial-balance', 'label' => 'Trial Balance', 'cake' => 'account_reports/account_trial_balance', 'action' => 'trialBalance', 'route' => null, 'member_access' => null],
    ['slug' => 'trial-balance-difference', 'label' => 'Trial Balance Difference', 'cake' => 'account_reports/account_trial_balance_difference', 'action' => 'trialBalanceDifference', 'route' => null, 'member_access' => null],
    ['slug' => 'trial-balance-diff-dr-vs-cr', 'label' => 'Trial Balance Diff (Dr vs Cr)', 'cake' => 'account_reports/trial_balance_diff', 'action' => 'trialBalanceDiff', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-register', 'label' => 'Bill Register', 'cake' => 'account_reports/account_bill_register', 'action' => 'billRegister', 'route' => null, 'member_access' => null],
    ['slug' => 'member-chart', 'label' => 'Member Chart', 'cake' => 'account_reports/member_chart', 'action' => 'memberChart', 'route' => null, 'member_access' => null],
    ['slug' => 'gst-register', 'label' => 'GST Register', 'cake' => 'account_reports/account_gst_register', 'action' => 'gstRegister', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-with-interest-gst', 'label' => 'Bill With Interest & GST', 'cake' => 'account_reports/bill_with_interest_gst', 'action' => 'billWithInterestGst', 'route' => null, 'member_access' => null],
    ['slug' => 'member-collection-register', 'label' => 'Member Collection Register', 'cake' => 'account_reports/member_collection_register', 'action' => 'memberCollectionRegister', 'route' => null, 'member_access' => null],
    ['slug' => 'receipt-payment', 'label' => 'Receipt & Payment', 'cake' => 'account_reports/account_receipt_payment', 'action' => 'receiptPayment', 'route' => null, 'member_access' => null],
    ['slug' => 'opening-balance', 'label' => 'Opening Balance', 'cake' => 'account_reports/bill_opening_balance', 'action' => 'openingBalance', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-summary-update', 'label' => 'Bill Summary Update', 'cake' => 'account_reports/comparison_2018closing_2019opening', 'action' => 'billSummaryUpdate', 'route' => null, 'member_access' => null],
    ['slug' => 'bill-summary-with-prev-data', 'label' => 'Bill Summary With PrevData', 'cake' => 'account_reports/bill_summary_with_prev_data', 'action' => 'billSummaryWithPrevData', 'route' => null, 'member_access' => null],
];
