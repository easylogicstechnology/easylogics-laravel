-- Group 3: structure-only (NO data) for tables missing on LIVE society13.
-- Source: local DB neweasylogics_live (mysqldump --no-data). Generated 2026-10-03. Idempotent: CREATE TABLE IF NOT EXISTS; no DROP/INSERT.
-- Review, and take a full backup of society13 first.
-- user_logins has a 0000-00-00 default, so strict sql_mode is relaxed for this session only.

SET @old_sql_mode=@@sql_mode;
SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION';

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `society_tariff_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `society_id` int(11) NOT NULL,
  `ledger_head_id` int(11) NOT NULL COMMENT 'society ledger head type ',
  `tariff_serial` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `states` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'State Unique ID',
  `state_name` varchar(27) DEFAULT NULL COMMENT 'State Name',
  `state_code` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `tariff_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff_type` varchar(50) NOT NULL,
  `cdate` datetime NOT NULL,
  `udate` datetime NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `temp_copy_credit_closing_balance` (
  `financial_year_id` int(11) NOT NULL DEFAULT 0,
  `society_id` int(11) NOT NULL DEFAULT 0,
  `ledger_head_id` int(11) DEFAULT NULL,
  `credit` double(19,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `temp_copy_debit_closing_balance` (
  `financial_year_id` int(11) NOT NULL DEFAULT 0,
  `society_id` int(11) NOT NULL DEFAULT 0,
  `ledger_head_id` int(11) DEFAULT NULL,
  `debit` double(19,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ipaddress` varchar(50) NOT NULL,
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

SET SESSION sql_mode=@old_sql_mode;
