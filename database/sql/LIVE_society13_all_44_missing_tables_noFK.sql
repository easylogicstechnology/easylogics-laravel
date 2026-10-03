-- ============================================================================
-- LIVE society13: create ALL 44 missing tables (structure only, NO data deleted)
-- NO-FOREIGN-KEY version (indexes kept): live gst_hsn_sac_master has no key on id, so FKs cannot be added.
-- Safe: CREATE TABLE IF NOT EXISTS only. No DROP / TRUNCATE / DELETE / UPDATE.
-- Existing tables are NOT touched. Take a backup of society13 first.
-- Select database `society13` in phpMyAdmin, then SQL tab -> paste -> Go.
-- Tables come out EMPTY. After this, run:  php artisan migrate   (applies only
-- the last migration, which adds columns to reseller_sub_users).
-- ============================================================================
SET @old_sql_mode=@@sql_mode;
SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION';   -- user_logins has a 0000-00-00 default
SET @old_fk=@@foreign_key_checks;
SET SESSION foreign_key_checks=0;                -- tables reference each other

CREATE TABLE IF NOT EXISTS `gst_advance_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `gst_advance_receipt_id` int(11) NOT NULL,
  `adjusted_against_type` enum('MemberBill','VendorBill') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MemberBill',
  `adjusted_against_id` int(11) NOT NULL,
  `adjusted_against_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjustment_date` date NOT NULL,
  `adjusted_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `adjusted_taxable_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `adjusted_gst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `cdate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gst_adv_adj_receipt` (`gst_advance_receipt_id`),
  KEY `idx_gst_adv_adj_society` (`society_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `gst_advance_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `receipt_no` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `party_type` enum('Member','Vendor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member',
  `member_id` int(11) DEFAULT NULL,
  `vendor_detail_id` int(10) unsigned DEFAULT NULL,
  `party_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gstin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pan_no` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_received` decimal(14,2) NOT NULL DEFAULT 0.00,
  `taxable_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_gst` decimal(14,2) NOT NULL DEFAULT 0.00,
  `adjustment_status` enum('Unadjusted','Partially Adjusted','Fully Adjusted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unadjusted',
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_gst_advance_no` (`society_id`,`financial_year_id`,`receipt_no`),
  KEY `idx_gst_advance_society_fy` (`society_id`,`financial_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `gst_credit_debit_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `note_type` enum('Credit Note','Debit Note') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Credit Note',
  `note_no` int(11) NOT NULL,
  `note_date` date NOT NULL,
  `original_invoice_type` enum('MemberBill','VendorBill') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MemberBill',
  `original_invoice_id` int(11) NOT NULL,
  `original_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_invoice_date` date DEFAULT NULL,
  `party_type` enum('Member','Vendor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Member',
  `member_id` int(11) DEFAULT NULL,
  `vendor_detail_id` int(10) unsigned DEFAULT NULL,
  `party_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gstin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hsn_sac_id` int(11) DEFAULT NULL,
  `taxable_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_gst` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_gst_note_no` (`society_id`,`financial_year_id`,`note_type`,`note_no`),
  KEY `idx_gst_note_society_fy` (`society_id`,`financial_year_id`),
  KEY `idx_gst_note_hsn` (`hsn_sac_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `gst_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `gstin` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_type` enum('Month','Quarter') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Month',
  `period_value` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cgst` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cess` decimal(14,2) NOT NULL DEFAULT 0.00,
  `interest` decimal(14,2) NOT NULL DEFAULT 0.00,
  `late_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `other_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(14,2) NOT NULL DEFAULT 0.00,
  `challan_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cin_cpin` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_portal_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount_paid` decimal(14,2) DEFAULT NULL,
  `payment_status` enum('Draft','Prepared','Paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `prepared_by` int(11) NOT NULL,
  `prepared_at` datetime NOT NULL,
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gst_payment_group` (`society_id`,`financial_year_id`,`period_type`,`period_value`),
  KEY `idx_gst_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `society_ledger_heads_bk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `short_code` varchar(20) NOT NULL,
  `account_category_id` int(11) NOT NULL,
  `account_head_id` int(11) NOT NULL,
  `society_head_sub_category_id` int(11) NOT NULL,
  `opening_amount` float(20,2) DEFAULT NULL,
  `is_in_bill_charges` int(11) DEFAULT 0,
  `is_tax_applicable` int(11) DEFAULT 0,
  `is_rebate_applicable` int(11) DEFAULT 0,
  `is_interest_free` int(11) DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_ledger_heads_opening_year_wise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `financial_year_id` int(11) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `ledger_head_id` int(11) DEFAULT NULL,
  `account_category_id` int(11) DEFAULT NULL,
  `op_credit` decimal(20,6) DEFAULT NULL,
  `op_debit` decimal(20,6) DEFAULT NULL,
  `credit` decimal(10,0) DEFAULT NULL,
  `debit` decimal(10,0) DEFAULT NULL,
  `balance_amount` decimal(20,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `society_id` (`society_id`),
  KEY `financial_year_id` (`financial_year_id`),
  KEY `account_category_id` (`account_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_month_interest_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `rate_year` smallint(6) NOT NULL,
  `rate_month` tinyint(4) NOT NULL,
  `interest_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_society_month` (`society_id`,`rate_year`,`rate_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `society_other_incomes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL,
  `general_receipt_number` int(11) NOT NULL,
  `title` varchar(100) NOT NULL COMMENT 'particulars',
  `description` varchar(255) NOT NULL COMMENT 'remark',
  `amount_paid` float(10,2) NOT NULL,
  `tds_amount` float NOT NULL,
  `net_amount` float NOT NULL,
  `tds_bank_id` varchar(50) NOT NULL,
  `cheque_no` int(11) NOT NULL DEFAULT 0,
  `cheque_date` date DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `society_bank_id` int(11) NOT NULL,
  `general_bank_name` varchar(255) DEFAULT NULL,
  `vendor_bank_id` int(11) DEFAULT NULL,
  `vendor_bank_ifsc` varchar(50) DEFAULT NULL,
  `vendor_bank_branch` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `credited_date` date DEFAULT NULL,
  `entry_date` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `financial_year_id` smallint(6) NOT NULL,
  `cdate` datetime NOT NULL DEFAULT current_timestamp(),
  `udate` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `society_id` (`society_id`,`ledger_head_id`,`financial_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `billing_frequency_id` int(11) NOT NULL,
  `interest_type_id` int(11) NOT NULL,
  `interest_rate` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `tariff_id` int(11) NOT NULL COMMENT 'tariff type',
  `cgst_tax_per` varchar(20) NOT NULL,
  `igst_tax_per` float DEFAULT NULL,
  `sgst_tax_per` float DEFAULT NULL,
  `is_tariff_mothly` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1->Yes,0->No',
  `show_all_tariff_name` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1->Yes,0->No',
  `bill_note` text DEFAULT NULL,
  `special_field` text DEFAULT NULL,
  `cdate` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `udate` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_bills_in_receipt` tinyint(1) NOT NULL DEFAULT 0,
  `gst_interest` varchar(10) NOT NULL,
  `gst_interest_arreas` varchar(10) NOT NULL,
  `scanner_image_path` varchar(255) NOT NULL,
  `signature_image_path` varchar(250) NOT NULL,
  `bilding_logo` varchar(250) NOT NULL,
  `settlement` int(11) NOT NULL DEFAULT 1,
  `current_bill_update_enabled` int(11) DEFAULT NULL,
  `gst_limit` float NOT NULL,
  `op_balance_saved` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `tds_account_id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL,
  `particulars` varchar(300) NOT NULL,
  `amount` float(15,2) NOT NULL,
  `tax_amount` float(15,2) NOT NULL,
  `total_amount` float(15,2) NOT NULL,
  `bill_voucher_number` varchar(25) NOT NULL,
  `payment_by_ledger_id` int(11) NOT NULL COMMENT 'society bank id',
  `cheque_reference_number` varchar(50) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_type` varchar(10) NOT NULL COMMENT 'Bank, Cash',
  `debited_date` date DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `financial_year_id` smallint(6) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sp_society_fy_ledger_date` (`society_id`,`financial_year_id`,`ledger_head_id`,`payment_date`),
  KEY `idx_sp_society_fy_paybyledger_date` (`society_id`,`financial_year_id`,`payment_by_ledger_id`,`payment_date`),
  KEY `idx_sp_society_fy_tds_date` (`society_id`,`financial_year_id`,`tds_account_id`,`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `amount` float(15,2) DEFAULT 0.00,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL,
  `updated_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_floor` (
  `id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `floor` int(11) NOT NULL,
  `amount` float(15,2) NOT NULL,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL COMMENT 'society ledger head type ',
  `tariff_serial` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_per_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `amount` float(15,2) DEFAULT 0.00,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_total_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `amount` float(15,2) DEFAULT 0.00,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_unit_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `unit_type` varchar(5) NOT NULL,
  `amount` float(15,2) DEFAULT 0.00,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_tariff_wings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ledger_head_id` int(11) NOT NULL,
  `society_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `wing_id` int(11) NOT NULL,
  `amount` float(15,2) DEFAULT 0.00,
  `tariff_serial` int(11) NOT NULL,
  `effective_since` date NOT NULL,
  `remark` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `society_year_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `year_id` int(11) DEFAULT NULL,
  `is_active` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `society_id` (`society_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `states` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'State Unique ID',
  `state_name` varchar(27) DEFAULT NULL COMMENT 'State Name',
  `state_code` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `supplementary_bill_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `month` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `sup_bill_type` varchar(25) NOT NULL,
  `ledger_head_id` int(11) NOT NULL,
  `rate_unit` enum('actual','area') NOT NULL,
  `amount` float(15,2) NOT NULL,
  `description` varchar(100) NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `tariff_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff_type` varchar(50) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `tds_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `ref_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int(11) NOT NULL,
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_data` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_data` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tds_audit_ref` (`ref_table`,`ref_id`),
  KEY `idx_tds_audit_society` (`society_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `vendor_detail_id` int(10) unsigned NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4','Full Year') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Full Year',
  `certificate_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_tds_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `generated_by` int(11) NOT NULL,
  `generated_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tds_cert_society_fy` (`society_id`,`financial_year_id`),
  KEY `idx_tds_cert_vendor` (`vendor_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_challan_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tds_challan_id` int(11) NOT NULL,
  `tds_transaction_id` int(11) NOT NULL,
  `cdate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tds_challan_txn` (`tds_transaction_id`),
  KEY `idx_tds_challan_txn_challan` (`tds_challan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_challans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `tan_no` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `assessment_year` varchar(9) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') COLLATE utf8mb4_unicode_ci NOT NULL,
  `month` tinyint(4) NOT NULL,
  `major_head` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minor_head` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_tds_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deductee_count` int(11) NOT NULL DEFAULT 0,
  `challan_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bsr_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `challan_date` date DEFAULT NULL,
  `cin` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('Pending','Challan Prepared','Payment Pending','Paid','Challan Verified','Filed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `prepared_by` int(11) NOT NULL,
  `prepared_at` datetime NOT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tds_challan_group` (`society_id`,`tan_no`,`financial_year_id`,`month`),
  KEY `idx_tds_challans_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_deductees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `vendor_detail_id` int(10) unsigned NOT NULL,
  `deductee_type` enum('Individual_HUF','Company','Firm','Others') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Individual_HUF',
  `resident_status` enum('Resident','Non-Resident') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Resident',
  `lower_deduction_cert_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lower_deduction_rate` decimal(5,2) DEFAULT NULL,
  `lower_deduction_valid_from` date DEFAULT NULL,
  `lower_deduction_valid_upto` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tds_deductee_vendor` (`vendor_detail_id`),
  KEY `idx_tds_deductees_society` (`society_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `section_code` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nature_of_payment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deductee_type` enum('Individual_HUF','Company','Firm','All') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'All',
  `resident_type` enum('Resident','Non-Resident','Both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Resident',
  `rate_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `threshold_limit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `threshold_basis` enum('Single Transaction','Aggregate in FY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aggregate in FY',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `tds_payable_ledger_head_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tds_section` (`society_id`,`section_code`,`deductee_type`,`resident_type`,`effective_from`),
  KEY `idx_tds_sections_society_status` (`society_id`,`status`),
  KEY `idx_tds_sections_ledger_head` (`tds_payable_ledger_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tds_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `financial_year_id` smallint(6) NOT NULL,
  `tds_section_id` int(11) NOT NULL,
  `vendor_detail_id` int(10) unsigned NOT NULL,
  `vendor_bill_id` int(10) unsigned DEFAULT NULL,
  `society_payment_id` int(11) DEFAULT NULL,
  `pan_no` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deduction_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gross_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tds_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tds_payable_ledger_head_id` int(11) DEFAULT NULL,
  `tds_challan_id` int(11) DEFAULT NULL,
  `challan_status` enum('Pending','Challan Prepared','Payment Pending','Paid','Challan Verified','Filed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `remarks` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_by` int(11) DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `reversal_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tds_txn_payment` (`society_payment_id`,`tds_section_id`,`vendor_detail_id`),
  KEY `idx_tds_txn_society_fy` (`society_id`,`financial_year_id`),
  KEY `idx_tds_txn_vendor` (`vendor_detail_id`),
  KEY `idx_tds_txn_challan` (`tds_challan_id`),
  KEY `idx_tds_txn_bill` (`vendor_bill_id`),
  KEY `idx_tds_txn_section` (`tds_section_id`),
  KEY `fk_tds_txn_ledger_head` (`tds_payable_ledger_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `temp_copy_credit_closing_balance` (
  `financial_year_id` int(11) NOT NULL DEFAULT 0,
  `society_id` int(11) NOT NULL DEFAULT 0,
  `ledger_head_id` int(11) DEFAULT NULL,
  `credit` double(19,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `temp_copy_debit_closing_balance` (
  `financial_year_id` int(11) NOT NULL DEFAULT 0,
  `society_id` int(11) NOT NULL DEFAULT 0,
  `ledger_head_id` int(11) DEFAULT NULL,
  `debit` double(19,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `temp_final_member_closing_balance` (
  `member_id` int(11) NOT NULL DEFAULT 0,
  `member_name` varchar(300) NOT NULL DEFAULT '',
  `flat_no` varchar(30) NOT NULL DEFAULT '',
  `bill_type` varchar(3) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `principal` double DEFAULT NULL,
  `interest` double DEFAULT NULL,
  `tax` double DEFAULT NULL,
  `bill_generated_date` date DEFAULT NULL,
  `bill_due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '(Id from member table)',
  `society_id` int(11) NOT NULL,
  `building_id` int(11) DEFAULT NULL,
  `wing_id` int(11) DEFAULT NULL,
  `flat_no` int(11) DEFAULT NULL,
  `tenant_name` varchar(100) NOT NULL,
  `lease_type` tinyint(4) NOT NULL,
  `agreement_on` date NOT NULL,
  `rent_per_month` decimal(10,2) NOT NULL,
  `nationality` int(11) NOT NULL,
  `address` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `phone` bigint(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ipaddress` varchar(50) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_add` tinyint(4) NOT NULL DEFAULT 0,
  `can_edit` tinyint(4) NOT NULL DEFAULT 0,
  `can_delete` tinyint(4) NOT NULL DEFAULT 0,
  `can_generate` tinyint(4) NOT NULL DEFAULT 0,
  `can_update` tinyint(4) NOT NULL DEFAULT 0,
  `can_view` tinyint(4) NOT NULL DEFAULT 0,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_module_unique` (`user_id`,`module`),
  KEY `user_permissions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(21) DEFAULT NULL,
  `password` varchar(128) NOT NULL,
  `role` varchar(20) NOT NULL,
  `access_level` tinyint(4) NOT NULL COMMENT '1-admin, 2-society,3-Reseller',
  `added_by` int(11) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `users_back` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `role` char(10) NOT NULL,
  `access_level` tinyint(4) NOT NULL COMMENT '1-admin, 2-society,3-Reseller',
  `added_by` int(11) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `vendor_bill_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_bill_id` int(10) unsigned NOT NULL,
  `ledger_head_id` int(11) NOT NULL COMMENT 'FK -> society_ledger_heads.id, the BILL PARTICULARS head for this line',
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cgst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `hsn_sac` varchar(20) DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_bill` (`vendor_bill_id`),
  KEY `idx_ledger_head` (`ledger_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `vendor_bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `financial_year_id` smallint(6) DEFAULT NULL,
  `vendor_ledger_head_id` int(11) NOT NULL COMMENT 'FK -> society_ledger_heads.id, must have a vendor_details row',
  `bill_type` varchar(20) NOT NULL DEFAULT 'Sales' COMMENT 'Sales|Purchase|Debit Note|Credit Note',
  `bill_no` varchar(50) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `po_no` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tds_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deduct_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_bill_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_society` (`society_id`),
  KEY `idx_vendor_ledger_head` (`vendor_ledger_head_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `vendor_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL COMMENT 'FK -> society_ledger_heads.id; the vendor IS this ledger head',
  `contact_person_name` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `company_email` varchar(150) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `amc_start_date` date DEFAULT NULL,
  `amc_end_date` date DEFAULT NULL,
  `facility_id` int(10) unsigned DEFAULT NULL COMMENT 'FK -> vendor_facilities.id',
  `sub_committee_list` text DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `cr_dr` enum('Cr','Dr') NOT NULL DEFAULT 'Cr',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_ledger_head` (`ledger_head_id`),
  KEY `idx_society` (`society_id`),
  KEY `idx_facility` (`facility_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `vendor_facilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_society` (`society_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `vw_member_transaction_data_test` (
  `member_prefix` varchar(20) DEFAULT NULL,
  `member_name` varchar(300) DEFAULT NULL,
  `flat_no` varchar(100) DEFAULT NULL,
  `member_email` varchar(70) DEFAULT NULL,
  `member_phone` varchar(21) DEFAULT NULL,
  `society_id` int(11) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `floor_no` varchar(200) DEFAULT NULL,
  `unit_type` text DEFAULT NULL,
  `financial_year_id` smallint(6) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `bill_no` int(11) DEFAULT NULL,
  `bill_type` enum('reg','sup') DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,
  `member_transfer` int(11) DEFAULT NULL,
  `interest_free_amount` float(15,2) DEFAULT NULL,
  `op_principal_arrears_original` float(15,2) DEFAULT NULL,
  `jv_adjustment` float(15,2) DEFAULT NULL,
  `op_principal_arrears` float(15,2) DEFAULT NULL,
  `op_interest_arrears` float(10,2) DEFAULT NULL,
  `op_due_amount` float(15,2) DEFAULT NULL,
  `bill_generated_date` date DEFAULT NULL,
  `monthly_amount` float(15,2) DEFAULT NULL,
  `monthly_bill_amount` float(15,2) DEFAULT NULL,
  `amount_payable` float(15,2) DEFAULT NULL,
  `op_tax_arrears` float(15,2) DEFAULT NULL,
  `principal_balance` float(15,2) DEFAULT NULL,
  `tax_total` float(15,2) DEFAULT NULL,
  `tax_balance` float(15,2) DEFAULT NULL,
  `interest_balance` float(15,2) DEFAULT NULL,
  `principal_paid` float(15,2) DEFAULT NULL,
  `tax_paid` float(15,2) DEFAULT NULL,
  `interest_adjusted` float(15,2) DEFAULT NULL,
  `balance_amount` float(15,2) DEFAULT NULL,
  `monthly_principal_amount` float(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `whatsapp_bill_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `society_id` int(11) NOT NULL,
  `bill_month` varchar(10) DEFAULT NULL,
  `financial_year_id` smallint(6) DEFAULT NULL,
  `total_bills` int(11) NOT NULL DEFAULT 0,
  `sent_count` int(11) NOT NULL DEFAULT 0,
  `delivered_count` int(11) NOT NULL DEFAULT 0,
  `read_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `pending_count` int(11) NOT NULL DEFAULT 0,
  `limit_count` int(11) NOT NULL DEFAULT 0,
  `invalid_phone_count` int(11) NOT NULL DEFAULT 0,
  `pdf_failed_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('IN_PROGRESS','COMPLETED','PARTIAL','STOPPED') NOT NULL DEFAULT 'IN_PROGRESS',
  `stopped_at_bill` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_id` (`batch_id`),
  KEY `idx_society` (`society_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `whatsapp_bill_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(50) NOT NULL,
  `society_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `bill_summary_id` int(11) NOT NULL,
  `bill_no` int(11) DEFAULT NULL,
  `bill_month` varchar(10) DEFAULT NULL,
  `financial_year_id` smallint(6) DEFAULT NULL,
  `flat_no` varchar(100) DEFAULT NULL,
  `member_name` varchar(200) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `amount` float(15,2) DEFAULT NULL,
  `status` enum('PENDING','SENT','DELIVERED','READ','FAILED','API_LIMIT_REACHED','INVALID_PHONE','PDF_FAILED') NOT NULL DEFAULT 'PENDING',
  `api_response` text DEFAULT NULL,
  `api_message_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `pdf_path` varchar(500) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_society` (`society_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_bill` (`bill_summary_id`),
  KEY `idx_status` (`status`),
  KEY `idx_society_batch` (`society_id`,`batch_id`),
  KEY `idx_api_message_id` (`api_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `wings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `wing_name` varchar(20) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Record the Laravel migrations whose tables are now created, so `artisan migrate`
-- will not try to create them again. (Migration 5 is left for artisan.)
INSERT INTO `migrations` (`migration`,`batch`)
SELECT m.n, 1 FROM (
  SELECT '2026_08_28_000001_create_user_permissions_table' AS n
  UNION ALL SELECT '2026_09_23_000001_add_can_update_to_user_permissions_table'
  UNION ALL SELECT '2026_09_26_000001_create_tds_tables'
  UNION ALL SELECT '2026_09_26_000002_create_gst_tables'
) m WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = m.n);

SET SESSION foreign_key_checks=@old_fk;
SET SESSION sql_mode=@old_sql_mode;

-- Verify (should return 140):
-- SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE';
