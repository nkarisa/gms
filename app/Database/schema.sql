-- Adminer 4.8.1 MySQL 8.0.28 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `account_system`;
CREATE TABLE `account_system` (
  `account_system_id` int NOT NULL AUTO_INCREMENT,
  `account_system_track_number` varchar(100) NOT NULL,
  `account_system_name` varchar(100) NOT NULL,
  `account_system_code` varchar(10) NOT NULL,
  `account_system_is_allocation_linked_to_account` int DEFAULT NULL,
  `account_system_is_active` int DEFAULT '1',
  `account_system_created_date` date DEFAULT NULL,
  `account_system_created_by` int DEFAULT NULL,
  `account_system_last_modified_by` int DEFAULT NULL,
  `account_system_last_modified_date` date DEFAULT NULL,
  `account_system_deleted_date` datetime DEFAULT NULL,
  `account_system_deleted_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `account_system_level` int DEFAULT NULL,
  PRIMARY KEY (`account_system_id`),
  KEY `account_system_code` (`account_system_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `account_system_language`;
CREATE TABLE `account_system_language` (
  `account_system_language_id` int NOT NULL AUTO_INCREMENT,
  `account_system_language_name` longtext NOT NULL,
  `account_system_language_track_number` longtext NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `fk_language_id` int NOT NULL,
  `account_system_language_created_date` date NOT NULL,
  `account_system_language_created_by` int NOT NULL,
  `account_system_language_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `account_system_language_last_modified_by` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`account_system_language_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_language_id` (`fk_language_id`),
  CONSTRAINT `account_system_language_ibfk_3` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `account_system_language_ibfk_4` FOREIGN KEY (`fk_language_id`) REFERENCES `language` (`language_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `account_system_setting`;
CREATE TABLE `account_system_setting` (
  `account_system_setting_id` int NOT NULL AUTO_INCREMENT,
  `account_system_setting_name` varchar(100) NOT NULL,
  `account_system_setting_track_number` varchar(100) NOT NULL,
  `fk_approve_item_id` int DEFAULT NULL,
  `fk_account_system_id_old` int DEFAULT NULL,
  `account_system_setting_value` int NOT NULL DEFAULT '1',
  `account_system_setting_description` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `account_system_setting_accounts` json DEFAULT NULL,
  `account_system_setting_created_by` int NOT NULL,
  `account_system_setting_created_date` date NOT NULL,
  `account_system_setting_last_modified_by` int NOT NULL,
  `account_system_setting_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`account_system_setting_id`),
  UNIQUE KEY `account_system_setting_name` (`account_system_setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `accrual_account`;
CREATE TABLE `accrual_account` (
  `accrual_account_id` int NOT NULL AUTO_INCREMENT,
  `accrual_account_track_number` longtext NOT NULL,
  `accrual_account_name` varchar(200) NOT NULL,
  `accrual_account_code` enum('receivables','payables','prepayments','depreciation','payroll_liability') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `accrual_account_effect` enum('debit','credit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `accrual_account_debit_effect` enum('receivables','payments','payables','disbursements','prepayments','settlements','depreciation','payroll_liability') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `accrual_account_credit_effect` enum('receivables','payments','payables','disbursements','prepayments','settlements','depreciation','payroll_liability') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `accrual_account_created_date` date DEFAULT NULL,
  `accrual_account_created_by` int DEFAULT NULL,
  `accrual_account_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accrual_account_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`accrual_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `approval`;
CREATE TABLE `approval` (
  `approval_id` int NOT NULL AUTO_INCREMENT,
  `approval_track_number` varchar(100) NOT NULL,
  `approval_name` varchar(100) NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `approval_created_by` int NOT NULL,
  `approval_created_date` date NOT NULL,
  `approval_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_last_modified_by` int NOT NULL,
  PRIMARY KEY (`approval_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_status_id` (`fk_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approval_exemption`;
CREATE TABLE `approval_exemption` (
  `approval_exemption_id` int NOT NULL AUTO_INCREMENT,
  `approval_exemption_track_number` varchar(100) NOT NULL,
  `approval_exemption_name` varchar(200) NOT NULL,
  `fk_office_id` int NOT NULL,
  `approval_exemption_status_id` int DEFAULT NULL,
  `approval_exemption_is_active` int NOT NULL DEFAULT '1',
  `approval_exemption_created_date` date DEFAULT NULL,
  `approval_exemption_created_by` int DEFAULT NULL,
  `approval_exemption_last_modified_by` int DEFAULT NULL,
  `approval_exemption_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`approval_exemption_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `approval_exemption_status_id` (`approval_exemption_status_id`),
  CONSTRAINT `approval_exemption_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `approval_exemption_ibfk_3` FOREIGN KEY (`approval_exemption_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approval_flow`;
CREATE TABLE `approval_flow` (
  `approval_flow_id` int NOT NULL AUTO_INCREMENT,
  `approval_flow_name` varchar(100) NOT NULL,
  `approval_flow_track_number` varchar(100) NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `approval_flow_is_active` int NOT NULL DEFAULT '1',
  `approval_flow_created_by` int NOT NULL,
  `approval_flow_created_date` date NOT NULL,
  `approval_flow_last_modified_by` int NOT NULL,
  `approval_flow_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`approval_flow_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `approval_flow_ibfk_8` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `approval_flow_ibfk_9` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approve_item`;
CREATE TABLE `approve_item` (
  `approve_item_id` int NOT NULL AUTO_INCREMENT,
  `approve_item_track_number` varchar(100) NOT NULL,
  `approve_item_name` varchar(100) NOT NULL,
  `approve_item_is_active` int NOT NULL DEFAULT '0',
  `approve_item_created_date` date NOT NULL,
  `approve_item_created_by` int NOT NULL,
  `approve_item_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approve_item_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`approve_item_id`),
  UNIQUE KEY `approve_item_name` (`approve_item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `asset_category`;
CREATE TABLE `asset_category` (
  `asset_category_id` int NOT NULL AUTO_INCREMENT,
  `asset_category_name` varchar(100) NOT NULL,
  `asset_category_track_number` varchar(100) NOT NULL,
  `asset_category_created_date` date DEFAULT NULL,
  `asset_category_created_by` int DEFAULT NULL,
  `asset_category_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `asset_category_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`asset_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `asset_depreciation`;
CREATE TABLE `asset_depreciation` (
  `asset_depreciation_id` int NOT NULL AUTO_INCREMENT,
  `asset_depreciation_name` varchar(100) NOT NULL,
  `asset_depreciation_track_number` varchar(100) NOT NULL,
  `asset_depreciation_month` date NOT NULL,
  `asset_depreciation_cost` decimal(10,2) NOT NULL,
  `fk_capital_asset_id` int unsigned NOT NULL,
  `asset_depreciation_created_date` date DEFAULT NULL,
  `asset_depreciation_created_by` int DEFAULT NULL,
  `asset_depreciation_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `asset_depreciation_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`asset_depreciation_id`),
  KEY `fk_capital_asset_id` (`fk_capital_asset_id`),
  CONSTRAINT `asset_depreciation_ibfk_1` FOREIGN KEY (`fk_capital_asset_id`) REFERENCES `capital_asset` (`capital_asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `asset_status`;
CREATE TABLE `asset_status` (
  `asset_status_id` int NOT NULL AUTO_INCREMENT,
  `asset_status_track_number` varchar(100) NOT NULL,
  `asset_status_name` varchar(100) NOT NULL,
  `asset_status_is_default` varchar(100) NOT NULL,
  `asset_status_created_date` date NOT NULL,
  `asset_status_created_by` int DEFAULT NULL,
  `asset_status_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `asset_status_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`asset_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `attachment`;
CREATE TABLE `attachment` (
  `attachment_id` int NOT NULL AUTO_INCREMENT,
  `attachment_name` varchar(100) NOT NULL,
  `attachment_track_number` varchar(100) NOT NULL,
  `attachment_size` int NOT NULL,
  `attachment_file_type` varchar(100) NOT NULL,
  `attachment_url` longtext NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `attachment_primary_id` int NOT NULL,
  `fk_attachment_type_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL DEFAULT '0',
  `attachment_is_s3_upload` int NOT NULL DEFAULT '0',
  `attachment_created_date` date NOT NULL,
  `attachment_created_by` int NOT NULL,
  `attachment_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_attachment_type_id` (`fk_attachment_type_id`),
  KEY `attachment_primary_id` (`attachment_primary_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `attachment_ibfk_2` FOREIGN KEY (`fk_attachment_type_id`) REFERENCES `attachment_type` (`attachment_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `attachment_type`;
CREATE TABLE `attachment_type` (
  `attachment_type_id` int NOT NULL AUTO_INCREMENT,
  `attachment_type_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `attachment_type_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `attachment_type_created_date` date DEFAULT NULL,
  `attachment_type_created_by` int DEFAULT NULL,
  `attachment_type_last_modified_by` int DEFAULT NULL,
  `attachment_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`attachment_type_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `attachment_type_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `attachment_type_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `audit`;
CREATE TABLE `audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origin_table` varchar(100) NOT NULL,
  `record_id` int NOT NULL,
  `original_data` json NOT NULL,
  `updated_data` json DEFAULT NULL,
  `action_taken` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `bank`;
CREATE TABLE `bank` (
  `bank_id` int NOT NULL AUTO_INCREMENT,
  `bank_track_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(45) DEFAULT NULL,
  `bank_swift_code` varchar(45) DEFAULT NULL,
  `bank_is_active` int NOT NULL DEFAULT '1',
  `fk_account_system_id` int NOT NULL DEFAULT '1',
  `bank_created_date` date DEFAULT NULL,
  `bank_created_by` int DEFAULT NULL,
  `bank_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bank_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`bank_id`),
  UNIQUE KEY `bank_swift_code` (`bank_swift_code`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `bank_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table list all the banks for centers';


DROP VIEW IF EXISTS `bank_to_bank_contra_contributions`;
CREATE TABLE `bank_to_bank_contra_contributions` (`office_id` int, `office_bank_id` int, `voucher_date` date, `voucher_number` int, `income_account_id` int, `voucher_detail_total_cost` decimal(65,2));


DROP VIEW IF EXISTS `bank_to_bank_contra_receipts`;
CREATE TABLE `bank_to_bank_contra_receipts` (`office_id` int, `office_bank_id` int, `voucher_date` date, `voucher_number` int, `income_account_id` int, `voucher_detail_total_cost` decimal(65,2));


DROP TABLE IF EXISTS `beneficiary`;
CREATE TABLE `beneficiary` (
  `beneficiary_id` int NOT NULL AUTO_INCREMENT,
  `beneficiary_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `beneficiary_number` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `beneficiary_dob` varchar(10) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL,
  `beneficiary_gender` varchar(10) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `fk_account_system_id` int NOT NULL DEFAULT '0',
  `beneficiary_created_date` date NOT NULL,
  `beneficiary_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`beneficiary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `budget`;
CREATE TABLE `budget` (
  `budget_id` int NOT NULL AUTO_INCREMENT,
  `budget_track_number` varchar(45) DEFAULT NULL,
  `budget_name` varchar(100) DEFAULT NULL,
  `fk_office_id` int DEFAULT NULL,
  `fk_budget_tag_id` int DEFAULT NULL,
  `fk_custom_financial_year_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT '0',
  `fk_status_id` int DEFAULT '0',
  `budget_review_count_number_drop` int DEFAULT NULL,
  `budget_year` int DEFAULT NULL,
  `budget_created_by` int DEFAULT NULL,
  `budget_created_date` date DEFAULT NULL,
  `budget_last_modified_by` int DEFAULT NULL,
  `budget_last_modified_date` date DEFAULT NULL,
  `budget_approvers` json DEFAULT NULL,
  PRIMARY KEY (`budget_id`),
  KEY `fk_budget_center1_idx` (`fk_office_id`),
  KEY `fk_budget_tag_id` (`fk_budget_tag_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_custom_financial_year_id` (`fk_custom_financial_year_id`),
  CONSTRAINT `budget_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `budget_ibfk_2` FOREIGN KEY (`fk_budget_tag_id`) REFERENCES `budget_tag` (`budget_tag_id`),
  CONSTRAINT `budget_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `budget_ibfk_4` FOREIGN KEY (`fk_custom_financial_year_id`) REFERENCES `custom_financial_year` (`custom_financial_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds the budget items by activity';


DROP TABLE IF EXISTS `budget_item`;
CREATE TABLE `budget_item` (
  `budget_item_id` int NOT NULL AUTO_INCREMENT,
  `budget_item_track_number` varchar(100) DEFAULT NULL,
  `budget_item_name` varchar(100) DEFAULT NULL,
  `fk_budget_id` int NOT NULL,
  `budget_item_total_cost` decimal(50,2) DEFAULT NULL,
  `fk_expense_account_id` int DEFAULT NULL,
  `budget_item_description` longtext,
  `budget_item_quantity` int NOT NULL DEFAULT '0',
  `budget_item_unit_cost` decimal(50,2) NOT NULL DEFAULT '0.00',
  `budget_item_often` int NOT NULL DEFAULT '1',
  `budget_item_marked_for_review` int DEFAULT '0',
  `budget_item_revisions` json DEFAULT NULL,
  `budget_item_source_id` int DEFAULT NULL,
  `budget_item_objective` json DEFAULT NULL,
  `fk_status_id` int DEFAULT '0',
  `fk_approval_id` int DEFAULT '0',
  `fk_project_allocation_id` int DEFAULT NULL,
  `budget_item_created_by` int DEFAULT NULL,
  `budget_item_last_modified_by` int DEFAULT NULL,
  `budget_item_created_date` date DEFAULT NULL,
  `budget_item_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `budget_item_approvers` json DEFAULT NULL,
  `budget_item_details` json DEFAULT NULL,
  PRIMARY KEY (`budget_item_id`),
  KEY `fk_budget_detail_id_expense_account_id_idx` (`fk_expense_account_id`),
  KEY `fk_budget_detail_budget_id_idx` (`fk_budget_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_item_ibfk_1` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`),
  CONSTRAINT `budget_item_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `budget_item_ibfk_3` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_budget_detail_id_expense_account_id` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This hold activties and their budgeted cost';


DROP TABLE IF EXISTS `budget_item_detail`;
CREATE TABLE `budget_item_detail` (
  `budget_item_detail_id` int NOT NULL AUTO_INCREMENT,
  `budget_item_detail_track_number` varchar(100) DEFAULT NULL,
  `budget_item_detail_name` varchar(100) DEFAULT NULL,
  `fk_budget_item_id` int DEFAULT NULL,
  `fk_month_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT '0',
  `fk_approval_id` int DEFAULT '0',
  `budget_item_detail_amount` decimal(50,2) DEFAULT NULL,
  `budget_item_detail_amount_usd` decimal(50,2) DEFAULT NULL,
  `budget_item_detail_created_date` date DEFAULT NULL,
  `budget_item_detail_created_by` int DEFAULT NULL,
  `budget_item_detail_last_modified_by` int DEFAULT NULL,
  `budget_item_detail_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`budget_item_detail_id`),
  KEY `fk_budget_month_spread_budget_detail1_idx` (`fk_budget_item_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_item_detail_ibfk_1` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `budget_item_detail_ibfk_2` FOREIGN KEY (`fk_budget_item_id`) REFERENCES `budget_item` (`budget_item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table distributes budget allocations by month';


DROP TABLE IF EXISTS `budget_limit`;
CREATE TABLE `budget_limit` (
  `budget_limit_id` int NOT NULL AUTO_INCREMENT,
  `budget_limit_track_number` varchar(100) NOT NULL,
  `budget_limit_name` varchar(100) NOT NULL,
  `fk_budget_id` int DEFAULT NULL,
  `fk_office_id` int DEFAULT NULL,
  `budget_limit_year` int DEFAULT NULL,
  `fk_budget_tag_id` int DEFAULT NULL,
  `fk_income_account_id` int NOT NULL,
  `budget_limit_amount` decimal(50,2) NOT NULL,
  `budget_limit_created_date` date DEFAULT NULL,
  `budget_limit_created_by` int DEFAULT NULL,
  `budget_limit_last_modified_by` int DEFAULT NULL,
  `budget_limit_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`budget_limit_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  KEY `fk_budget_id` (`fk_budget_id`),
  CONSTRAINT `budget_limit_ibfk_4` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`),
  CONSTRAINT `budget_limit_ibfk_6` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_projection`;
CREATE TABLE `budget_projection` (
  `budget_projection_id` int NOT NULL AUTO_INCREMENT,
  `budget_projection_name` varchar(100) NOT NULL,
  `budget_projection_track_number` varchar(100) NOT NULL,
  `fk_budget_id` int NOT NULL,
  `budget_projection_created_by` int NOT NULL,
  `budget_projection_created_date` date NOT NULL,
  `budget_projection_last_modified_by` int NOT NULL,
  `budget_projection_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`budget_projection_id`),
  KEY `fk_budget_id` (`fk_budget_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_projection_ibfk_2` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`),
  CONSTRAINT `budget_projection_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_projection_income_account`;
CREATE TABLE `budget_projection_income_account` (
  `budget_projection_income_account_id` int NOT NULL AUTO_INCREMENT,
  `budget_projection_income_account_name` varchar(100) NOT NULL,
  `budget_projection_income_account_track_number` varchar(100) NOT NULL,
  `fk_budget_projection_id` int NOT NULL,
  `fk_income_account_id` int NOT NULL,
  `budget_projection_income_account_amount` decimal(10,2) NOT NULL,
  `budget_projection_income_account_created_by` int NOT NULL,
  `budget_projection_income_account_created_date` date NOT NULL,
  `budget_projection_income_account_last_modified_by` int NOT NULL,
  `budget_projection_income_account_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`budget_projection_income_account_id`),
  KEY `fk_budget_projection_id` (`fk_budget_projection_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_1` FOREIGN KEY (`fk_budget_projection_id`) REFERENCES `budget_projection` (`budget_projection_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_review_count`;
CREATE TABLE `budget_review_count` (
  `budget_review_count_id` int NOT NULL AUTO_INCREMENT,
  `budget_review_count_track_number` varchar(100) NOT NULL,
  `budget_review_count_name` varchar(100) NOT NULL,
  `budget_review_count_number` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `budget_review_count_created_date` date NOT NULL,
  `budget_review_count_created_by` int NOT NULL,
  `budget_review_count_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `budget_review_count_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`budget_review_count_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `budget_review_count_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_tag`;
CREATE TABLE `budget_tag` (
  `budget_tag_id` int NOT NULL AUTO_INCREMENT,
  `budget_tag_track_number` varchar(100) NOT NULL,
  `budget_tag_name` varchar(100) NOT NULL,
  `fk_month_id` int NOT NULL,
  `budget_tag_level` int NOT NULL,
  `budget_tag_is_active` int NOT NULL DEFAULT '1',
  `fk_account_system_id` int NOT NULL,
  `budget_tag_created_date` date NOT NULL,
  `budget_tag_created_by` int NOT NULL,
  `budget_tag_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `budget_tag_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`budget_tag_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_month_id` (`fk_month_id`),
  CONSTRAINT `budget_tag_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `budget_tag_ibfk_2` FOREIGN KEY (`fk_month_id`) REFERENCES `month` (`month_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cancel_cheque`;
CREATE TABLE `cancel_cheque` (
  `cancel_cheque_id` int NOT NULL AUTO_INCREMENT,
  `cancel_cheque_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cancel_cheque_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fk_cheque_book_id` int NOT NULL,
  `cancel_cheque_number` int NOT NULL,
  `fk_item_reason_id` int DEFAULT NULL,
  `fk_voucher_id` int DEFAULT NULL,
  `other_reason` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cancel_cheque_created_date` date DEFAULT NULL,
  `cancel_cheque_created_by` int DEFAULT NULL,
  `cancel_cheque_last_modified_by` int DEFAULT NULL,
  `cancel_cheque_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `cancel_cheque_approvers` json DEFAULT NULL,
  PRIMARY KEY (`cancel_cheque_id`),
  KEY `fk_cheque_book_id` (`fk_cheque_book_id`),
  KEY `fk_item_reason_id` (`fk_item_reason_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `cancel_cheque_ibfk_1` FOREIGN KEY (`fk_cheque_book_id`) REFERENCES `cheque_book` (`cheque_book_id`),
  CONSTRAINT `cancel_cheque_ibfk_2` FOREIGN KEY (`fk_item_reason_id`) REFERENCES `item_reason` (`item_reason_id`),
  CONSTRAINT `cancel_cheque_ibfk_4` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `capital_asset`;
CREATE TABLE `capital_asset` (
  `capital_asset_id` int unsigned NOT NULL AUTO_INCREMENT,
  `capital_asset_track_number` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `capital_asset_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `capital_asset_serial` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fk_asset_category_id` int NOT NULL,
  `capital_asset_description` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `capital_asset_purchase_date` date NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_voucher_id` int DEFAULT NULL,
  `capital_asset_cost` decimal(50,2) NOT NULL,
  `capital_asset_total_depreciation` decimal(50,2) NOT NULL,
  `fk_asset_status_id` decimal(50,2) DEFAULT NULL,
  `capital_asset_end_term_date` date DEFAULT NULL,
  `capital_asset_location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capital_asset_created_date` date DEFAULT NULL,
  `capital_asset_created_by` int DEFAULT NULL,
  `capital_asset_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `capital_asset_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`capital_asset_id`),
  KEY `fk_asset_category_id` (`fk_asset_category_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `capital_asset_ibfk_1` FOREIGN KEY (`fk_asset_category_id`) REFERENCES `asset_category` (`asset_category_id`),
  CONSTRAINT `capital_asset_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `capital_asset_ibfk_3` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `cash_recipient_account`;
CREATE TABLE `cash_recipient_account` (
  `cash_recipient_account_id` int NOT NULL AUTO_INCREMENT,
  `cash_recipient_account_name` varchar(100) NOT NULL,
  `cash_recipient_account_track_number` varchar(100) NOT NULL,
  `fk_voucher_id` int NOT NULL,
  `fk_office_bank_id` int DEFAULT NULL,
  `fk_office_cash_id` int DEFAULT NULL,
  `cash_recipient_account_created_date` date DEFAULT NULL,
  `cash_recipient_account_created_by` int DEFAULT NULL,
  `cash_recipient_account_last_modified_by` int DEFAULT NULL,
  `cash_recipient_account_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`cash_recipient_account_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `cash_recipient_account_ibfk_1` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cheque_book`;
CREATE TABLE `cheque_book` (
  `cheque_book_id` int NOT NULL AUTO_INCREMENT,
  `cheque_book_track_number` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `cheque_book_name` varchar(100) NOT NULL,
  `fk_office_bank_id` int DEFAULT NULL,
  `cheque_book_is_active` int DEFAULT '0',
  `cheque_book_start_serial_number` int DEFAULT NULL,
  `cheque_book_count_of_leaves` int DEFAULT NULL,
  `cheque_book_use_start_date` date DEFAULT NULL,
  `cheque_book_created_date` date DEFAULT NULL,
  `cheque_book_created_by` int DEFAULT NULL,
  `cheque_book_last_modified_by` int DEFAULT NULL,
  `cheque_book_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `cheque_book_approvers` json DEFAULT NULL,
  `cheque_book_is_used` int DEFAULT '0',
  PRIMARY KEY (`cheque_book_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `cheque_book_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cheque_book_reset`;
CREATE TABLE `cheque_book_reset` (
  `cheque_book_reset_id` int NOT NULL AUTO_INCREMENT,
  `cheque_book_reset_name` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `cheque_book_reset_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `cheque_book_reset_serial` int NOT NULL,
  `cheque_book_reset_is_active` int NOT NULL DEFAULT '1',
  `fk_item_reason_id` int NOT NULL,
  `cheque_book_reset_created_date` date NOT NULL,
  `cheque_book_reset_created_by` int NOT NULL,
  `cheque_book_reset_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cheque_book_reset_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`cheque_book_reset_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_item_reason_id` (`fk_item_reason_id`),
  CONSTRAINT `cheque_book_reset_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `cheque_book_reset_ibfk_2` FOREIGN KEY (`fk_item_reason_id`) REFERENCES `item_reason` (`item_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `cheque_injection`;
CREATE TABLE `cheque_injection` (
  `cheque_injection_id` int NOT NULL AUTO_INCREMENT,
  `cheque_injection_track_number` varchar(100) NOT NULL,
  `cheque_injection_name` varchar(100) NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `cheque_injection_number` varchar(100) NOT NULL,
  `fk_item_reason_id` int NOT NULL,
  `cheque_injection_is_active` int NOT NULL DEFAULT '1',
  `cheque_injection_created_date` date NOT NULL,
  `cheque_injection_created_by` int NOT NULL,
  `cheque_injection_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cheque_injection_last_modified_by` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  PRIMARY KEY (`cheque_injection_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_item_reason_id` (`fk_item_reason_id`),
  CONSTRAINT `cheque_injection_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `cheque_injection_ibfk_2` FOREIGN KEY (`fk_item_reason_id`) REFERENCES `item_reason` (`item_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE `ci_sessions` (
  `id` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  `ci_sessions_created_date` date DEFAULT NULL,
  `ci_sessions_created_by` int DEFAULT NULL,
  `ci_sessions_last_modified_by` int DEFAULT NULL,
  `ci_sessions_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `closing_allocation_balance`;
CREATE TABLE `closing_allocation_balance` (
  `fk_office_id` int NOT NULL,
  `closing_allocation_balance_month` int NOT NULL,
  `fk_project_id` int NOT NULL DEFAULT '0',
  `closing_allocation_balance_amount` decimal(50,2) NOT NULL,
  `closing_allocation_balance_created_date` date NOT NULL,
  `closing_allocation_balance_created_by` int NOT NULL,
  `closing_allocation_balance_last_modified_by` int NOT NULL,
  `closing_allocation_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fk_office_id`,`closing_allocation_balance_month`,`fk_project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_bank_balance`;
CREATE TABLE `closing_bank_balance` (
  `fk_office_id` int NOT NULL,
  `closing_bank_balance_month` date NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL DEFAULT '0',
  `closing_bank_balance_amount` decimal(50,2) NOT NULL,
  `closing_bank_balance_created_date` date NOT NULL,
  `closing_bank_balance_created_by` int NOT NULL,
  `closing_bank_balance_last_modified_by` int NOT NULL,
  `closing_bank_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fk_office_id`,`closing_bank_balance_month`,`fk_office_bank_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  CONSTRAINT `closing_bank_balance_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `closing_bank_balance_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `closing_bank_balance_ibfk_3` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_cash_balance`;
CREATE TABLE `closing_cash_balance` (
  `closing_cash_balance_id` int NOT NULL AUTO_INCREMENT,
  `closing_cash_balance_name` varchar(100) NOT NULL,
  `closing_cash_balance_track_number` varchar(100) NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_voucher_type_account_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL DEFAULT '0',
  `fk_office_cash_id` int NOT NULL DEFAULT '0',
  `closing_cash_balance_amount` decimal(50,2) NOT NULL,
  `closing_cash_balance_created_date` date NOT NULL,
  `closing_cash_balance_created_by` int NOT NULL,
  `closing_cash_balance_last_modified_by` int NOT NULL,
  `closing_cash_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`closing_cash_balance_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  KEY `fk_voucher_type_account_id` (`fk_voucher_type_account_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `closing_cash_balance_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`),
  CONSTRAINT `closing_cash_balance_ibfk_2` FOREIGN KEY (`fk_voucher_type_account_id`) REFERENCES `voucher_type_account` (`voucher_type_account_id`),
  CONSTRAINT `closing_cash_balance_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_deposit_transit`;
CREATE TABLE `closing_deposit_transit` (
  `closing_deposit_transit_id` int NOT NULL AUTO_INCREMENT,
  `closing_deposit_transit_name` longtext NOT NULL,
  `closing_deposit_transit_track_number` varchar(200) NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_voucher_id` int NOT NULL,
  `closing_deposit_transit_date` date NOT NULL,
  `closing_deposit_transit_amount` decimal(50,2) NOT NULL,
  `closing_deposit_transit_created_by` int NOT NULL,
  `closing_deposit_transit_created_date` date DEFAULT NULL,
  `closing_deposit_transit_created_last_modified_by` int DEFAULT NULL,
  `closing_deposit_transit_created_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`closing_deposit_transit_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `closing_deposit_transit_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`),
  CONSTRAINT `closing_deposit_transit_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `closing_deposit_transit_ibfk_4` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_financial_report_total`;
CREATE TABLE `closing_financial_report_total` (
  `closing_financial_report_total_id` int NOT NULL AUTO_INCREMENT,
  `fk_financial_report_id` int NOT NULL,
  `closing_financial_report_total_fund_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_bank_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_cash_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_outstanding_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_transit_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_reconciliation_balance` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_stale_cheques_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `closing_financial_report_total_stale_deposit_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`closing_financial_report_total_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  CONSTRAINT `closing_financial_report_total_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_fund_balance`;
CREATE TABLE `closing_fund_balance` (
  `closing_fund_balance_id` int NOT NULL AUTO_INCREMENT,
  `closing_fund_balance_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `closing_fund_balance_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_income_account_id` int NOT NULL,
  `fk_project_id` int DEFAULT NULL,
  `fk_office_bank_id` int DEFAULT NULL,
  `closing_fund_balance_amount` decimal(50,2) NOT NULL,
  `closing_fund_balance_created_by` int DEFAULT NULL,
  `closing_fund_balance_created_date` date DEFAULT NULL,
  `closing_fund_balance_last_modified_by` int DEFAULT NULL,
  `closing_fund_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`closing_fund_balance_id`),
  UNIQUE KEY `composite_key` (`fk_financial_report_id`,`fk_income_account_id`,`fk_project_id`,`fk_office_bank_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  KEY `fk_project_id` (`fk_project_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `closing_fund_balance_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`),
  CONSTRAINT `closing_fund_balance_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`),
  CONSTRAINT `closing_fund_balance_ibfk_3` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `closing_fund_balance_ibfk_4` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_outstanding_cheque`;
CREATE TABLE `closing_outstanding_cheque` (
  `closing_outstanding_cheque_id` int NOT NULL AUTO_INCREMENT,
  `closing_outstanding_cheque_name` longtext NOT NULL,
  `closing_outstanding_cheque_track_number` int NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_voucher_id` int NOT NULL,
  `closing_outstanding_cheque_date` date NOT NULL,
  `closing_outstanding_cheque_cheque_number` decimal(50,2) NOT NULL,
  `closing_outstanding_cheque_amount` int NOT NULL,
  `closing_outstanding_cheque_created_by` int DEFAULT NULL,
  `closing_outstanding_cheque_created_date` date DEFAULT NULL,
  `closing_outstanding_cheque_created_last_modified_by` int DEFAULT NULL,
  `closing_outstanding_cheque_created_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`closing_outstanding_cheque_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `closing_outstanding_cheque_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`),
  CONSTRAINT `closing_outstanding_cheque_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `closing_outstanding_cheque_ibfk_4` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `closing_total_fund_balance`;
CREATE TABLE `closing_total_fund_balance` (
  `closing_total_fund_balance_id` int NOT NULL AUTO_INCREMENT,
  `fk_financial_report_id` int NOT NULL,
  `closing_total_fund_balance_amount` decimal(50,2) NOT NULL,
  PRIMARY KEY (`closing_total_fund_balance_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  CONSTRAINT `closing_total_fund_balance_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `context_center`;
CREATE TABLE `context_center` (
  `context_center_id` int NOT NULL AUTO_INCREMENT,
  `context_center_track_number` varchar(100) DEFAULT NULL,
  `context_center_name` varchar(100) DEFAULT NULL,
  `context_center_description` varchar(100) DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int DEFAULT NULL,
  `fk_context_cluster_id` int DEFAULT NULL,
  `context_center_created_date` date DEFAULT NULL,
  `context_center_created_by` int DEFAULT NULL,
  `context_center_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `context_center_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_center_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_context_cluster_id` (`fk_context_cluster_id`),
  CONSTRAINT `context_center_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `context_center_ibfk_4` FOREIGN KEY (`fk_context_cluster_id`) REFERENCES `context_cluster` (`context_cluster_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `context_center_ibfk_5` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_center_user`;
CREATE TABLE `context_center_user` (
  `context_center_user_id` int NOT NULL AUTO_INCREMENT,
  `context_center_user_track_number` varchar(100) DEFAULT NULL,
  `context_center_user_name` varchar(100) DEFAULT NULL,
  `fk_context_center_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_center_user_is_active` int NOT NULL,
  `context_center_user_created_by` int DEFAULT NULL,
  `context_center_user_created_date` date DEFAULT '0000-00-00',
  `context_center_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_center_user_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_center_user_id`),
  KEY `fk_context_center_id` (`fk_context_center_id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `fk_designation_id` (`fk_designation_id`),
  CONSTRAINT `context_center_user_ibfk_1` FOREIGN KEY (`fk_context_center_id`) REFERENCES `context_center` (`context_center_id`),
  CONSTRAINT `context_center_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `context_center_user_ibfk_3` FOREIGN KEY (`fk_designation_id`) REFERENCES `designation` (`designation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_cluster`;
CREATE TABLE `context_cluster` (
  `context_cluster_id` int NOT NULL AUTO_INCREMENT,
  `context_cluster_track_number` varchar(100) DEFAULT NULL,
  `context_cluster_name` longtext,
  `context_cluster_description` longtext,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int DEFAULT NULL,
  `fk_context_cohort_id` int DEFAULT NULL,
  `context_cluster_created_date` date DEFAULT NULL,
  `context_cluster_created_by` int DEFAULT NULL,
  `context_cluster_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `context_cluster_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_cluster_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_context_cohort_id` (`fk_context_cohort_id`),
  CONSTRAINT `context_cluster_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `context_cluster_ibfk_4` FOREIGN KEY (`fk_context_cohort_id`) REFERENCES `context_cohort` (`context_cohort_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `context_cluster_ibfk_5` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_cluster_user`;
CREATE TABLE `context_cluster_user` (
  `context_cluster_user_id` int NOT NULL AUTO_INCREMENT,
  `context_cluster_user_track_number` varchar(100) DEFAULT NULL,
  `context_cluster_user_name` longtext,
  `fk_context_cluster_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_cluster_user_is_active` int NOT NULL,
  `context_cluster_user_created_by` int NOT NULL,
  `context_cluster_user_created_date` date DEFAULT '0000-00-00',
  `context_cluster_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cluster_user_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_cluster_user_id`),
  KEY `fk_context_cluster_id` (`fk_context_cluster_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `context_cluster_user_ibfk_1` FOREIGN KEY (`fk_context_cluster_id`) REFERENCES `context_cluster` (`context_cluster_id`),
  CONSTRAINT `context_cluster_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_cohort`;
CREATE TABLE `context_cohort` (
  `context_cohort_id` int NOT NULL AUTO_INCREMENT,
  `context_cohort_track_number` varchar(100) DEFAULT NULL,
  `context_cohort_name` varchar(100) DEFAULT NULL,
  `context_cohort_description` varchar(100) DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int DEFAULT NULL,
  `fk_context_country_id` int DEFAULT NULL,
  `context_cohort_created_date` date DEFAULT NULL,
  `context_cohort_created_by` int DEFAULT NULL,
  `context_cohort_last_modified_date` timestamp NULL DEFAULT NULL,
  `context_cohort_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_cohort_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_context_country_id` (`fk_context_country_id`),
  CONSTRAINT `context_cohort_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `context_cohort_ibfk_4` FOREIGN KEY (`fk_context_country_id`) REFERENCES `context_country` (`context_country_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `context_cohort_ibfk_5` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_cohort_user`;
CREATE TABLE `context_cohort_user` (
  `context_cohort_user_id` int NOT NULL AUTO_INCREMENT,
  `context_cohort_user_track_number` varchar(100) DEFAULT NULL,
  `context_cohort_user_name` varchar(100) DEFAULT NULL,
  `fk_context_cohort_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_cohort_user_is_active` int NOT NULL,
  `context_cohort_user_created_by` int NOT NULL,
  `context_cohort_user_created_date` date DEFAULT '0000-00-00',
  `context_cohort_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cohort_user_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_cohort_user_id`),
  KEY `fk_context_cohort_id` (`fk_context_cohort_id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `fk_designation_id` (`fk_designation_id`),
  CONSTRAINT `context_cohort_user_ibfk_1` FOREIGN KEY (`fk_context_cohort_id`) REFERENCES `context_cohort` (`context_cohort_id`),
  CONSTRAINT `context_cohort_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `context_cohort_user_ibfk_3` FOREIGN KEY (`fk_designation_id`) REFERENCES `designation` (`designation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_country`;
CREATE TABLE `context_country` (
  `context_country_id` int NOT NULL AUTO_INCREMENT,
  `context_country_track_number` varchar(100) DEFAULT NULL,
  `context_country_name` varchar(100) DEFAULT NULL,
  `context_country_description` varchar(100) DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int DEFAULT NULL,
  `fk_context_region_id` int DEFAULT NULL,
  `context_country_created_date` date DEFAULT NULL,
  `context_country_created_by` int DEFAULT NULL,
  `context_country_last_modified_date` timestamp NULL DEFAULT NULL,
  `context_country_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_country_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_context_region_id` (`fk_context_region_id`),
  CONSTRAINT `context_country_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `context_country_ibfk_3` FOREIGN KEY (`fk_context_region_id`) REFERENCES `context_region` (`context_region_id`),
  CONSTRAINT `context_country_ibfk_4` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_country_user`;
CREATE TABLE `context_country_user` (
  `context_country_user_id` int NOT NULL AUTO_INCREMENT,
  `context_country_user_track_number` varchar(100) DEFAULT NULL,
  `context_country_user_name` varchar(100) DEFAULT NULL,
  `fk_context_country_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_country_user_is_active` int NOT NULL,
  `context_country_user_created_by` int NOT NULL,
  `context_country_user_created_date` date DEFAULT NULL,
  `context_country_user_last_modified_date` timestamp NULL DEFAULT NULL,
  `context_country_user_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_country_user_id`),
  KEY `fk_context_country_id` (`fk_context_country_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `context_country_user_ibfk_2` FOREIGN KEY (`fk_context_country_id`) REFERENCES `context_country` (`context_country_id`),
  CONSTRAINT `context_country_user_ibfk_3` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `context_country_user_ibfk_4` FOREIGN KEY (`fk_context_country_id`) REFERENCES `context_country` (`context_country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_definition`;
CREATE TABLE `context_definition` (
  `context_definition_id` int NOT NULL AUTO_INCREMENT,
  `context_definition_track_number` varchar(100) DEFAULT NULL,
  `context_definition_name` varchar(100) DEFAULT NULL,
  `context_definition_level` int DEFAULT NULL,
  `context_definition_is_implementing` int DEFAULT NULL,
  `context_definition_is_active` int NOT NULL DEFAULT '1',
  `context_definition_created_date` date DEFAULT NULL,
  `context_definition_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `context_definition_created_by` int DEFAULT NULL,
  `context_definition_last_modified_by` int DEFAULT NULL,
  `context_definition_deleted_at` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_definition_id`),
  UNIQUE KEY `center_group_hierarchy_level` (`context_definition_level`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `context_definition_ibfk_1` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_global`;
CREATE TABLE `context_global` (
  `context_global_id` int NOT NULL AUTO_INCREMENT,
  `context_global_track_number` varchar(100) NOT NULL,
  `context_global_name` varchar(100) NOT NULL,
  `context_global_description` longtext NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int NOT NULL,
  `context_global_created_date` date NOT NULL,
  `context_global_created_by` int NOT NULL,
  `context_global_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `context_global_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`context_global_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  CONSTRAINT `context_global_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `context_global_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_global_user`;
CREATE TABLE `context_global_user` (
  `context_global_user_id` int NOT NULL AUTO_INCREMENT,
  `context_global_user_track_number` varchar(100) NOT NULL,
  `context_global_user_name` varchar(100) NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_context_global_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_global_user_is_active` int NOT NULL DEFAULT '1',
  `context_global_user_created_date` date NOT NULL,
  `context_global_user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `context_global_user_created_by` int NOT NULL,
  `context_global_user_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`context_global_user_id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `fk_context_global_id` (`fk_context_global_id`),
  CONSTRAINT `context_global_user_ibfk_1` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `context_global_user_ibfk_2` FOREIGN KEY (`fk_context_global_id`) REFERENCES `context_global` (`context_global_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_region`;
CREATE TABLE `context_region` (
  `context_region_id` int NOT NULL AUTO_INCREMENT,
  `context_region_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `context_region_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `context_region_description` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `fk_context_definition_id` int NOT NULL,
  `fk_context_global_id` int NOT NULL,
  `context_region_created_date` date DEFAULT NULL,
  `context_region_created_by` int DEFAULT NULL,
  `context_region_last_modified_date` timestamp NULL DEFAULT NULL,
  `context_region_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_region_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_context_global_id` (`fk_context_global_id`),
  CONSTRAINT `context_region_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `context_region_ibfk_3` FOREIGN KEY (`fk_context_global_id`) REFERENCES `context_global` (`context_global_id`),
  CONSTRAINT `context_region_ibfk_5` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `context_region_user`;
CREATE TABLE `context_region_user` (
  `context_region_user_id` int NOT NULL AUTO_INCREMENT,
  `context_region_user_track_number` varchar(100) DEFAULT NULL,
  `context_region_user_name` varchar(100) DEFAULT NULL,
  `fk_context_region_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_designation_id` int NOT NULL,
  `context_region_user_is_active` int NOT NULL,
  `context_region_user_created_by` int NOT NULL,
  `context_region_user_created_date` date DEFAULT '0000-00-00',
  `context_region_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_region_user_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`context_region_user_id`),
  KEY `fk_context_region_id` (`fk_context_region_id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `fk_designation_id` (`fk_designation_id`),
  CONSTRAINT `context_region_user_ibfk_1` FOREIGN KEY (`fk_context_region_id`) REFERENCES `context_region` (`context_region_id`),
  CONSTRAINT `context_region_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `context_region_user_ibfk_3` FOREIGN KEY (`fk_designation_id`) REFERENCES `designation` (`designation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `contra_account`;
CREATE TABLE `contra_account` (
  `contra_account_id` int NOT NULL AUTO_INCREMENT,
  `contra_account_track_number` varchar(100) NOT NULL,
  `contra_account_name` varchar(100) NOT NULL,
  `contra_account_code` varchar(20) NOT NULL,
  `contra_account_description` varchar(100) NOT NULL,
  `fk_voucher_type_account_id` int NOT NULL,
  `fk_voucher_type_effect_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `contra_account_created_date` date DEFAULT NULL,
  `contra_account_created_by` int DEFAULT NULL,
  `contra_account_last_modified_by` int DEFAULT NULL,
  `contra_account_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`contra_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_voucher_type_account_id` (`fk_voucher_type_account_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `contra_account_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `contra_account_ibfk_2` FOREIGN KEY (`fk_voucher_type_account_id`) REFERENCES `voucher_type_account` (`voucher_type_account_id`),
  CONSTRAINT `contra_account_ibfk_4` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `country_currency`;
CREATE TABLE `country_currency` (
  `country_currency_id` int NOT NULL AUTO_INCREMENT,
  `country_currency_name` varchar(100) NOT NULL,
  `country_currency_track_number` varchar(100) NOT NULL,
  `country_currency_code` varchar(10) NOT NULL,
  `fk_account_system_id` int NOT NULL DEFAULT '0',
  `country_currency_created_by` int NOT NULL,
  `country_currency_created_date` date NOT NULL,
  `country_currency_last_modified_by` int NOT NULL,
  `country_currency_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`country_currency_id`),
  UNIQUE KEY `country_currency_code` (`country_currency_code`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `country_currency_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `currency_conversion`;
CREATE TABLE `currency_conversion` (
  `currency_conversion_id` int NOT NULL AUTO_INCREMENT,
  `currency_conversion_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `currency_conversion_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `currency_conversion_month` date DEFAULT NULL,
  `fk_country_currency_id` int NOT NULL,
  `currency_conversion_rate` decimal(50,2) DEFAULT NULL COMMENT 'How many USD equals 1 local currency unit',
  `currency_conversion_created_date` date DEFAULT NULL,
  `currency_conversion_created_by` int DEFAULT NULL,
  `currency_conversion_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `currency_conversion_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`currency_conversion_id`),
  KEY `fk_country_currency_id` (`fk_country_currency_id`),
  CONSTRAINT `currency_conversion_ibfk_1` FOREIGN KEY (`fk_country_currency_id`) REFERENCES `country_currency` (`country_currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='The base rate is always USD';


DROP TABLE IF EXISTS `currency_conversion_detail`;
CREATE TABLE `currency_conversion_detail` (
  `currency_conversion_detail_id` int NOT NULL AUTO_INCREMENT,
  `currency_conversion_detail_name` varchar(100) NOT NULL,
  `currency_conversion_detail_track_number` varchar(100) NOT NULL,
  `fk_currency_conversion_id` int NOT NULL,
  `fk_country_currency_id` int NOT NULL,
  `currency_conversion_detail_rate` double(10,2) NOT NULL,
  `currency_conversion_detail_created_by` int NOT NULL,
  `currency_conversion_detail_created_date` date NOT NULL,
  `currency_conversion_detail_last_modified_by` int NOT NULL,
  `currency_conversion_detail_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`currency_conversion_detail_id`),
  KEY `fk_currency_conversion_id` (`fk_currency_conversion_id`),
  KEY `fk_country_currency_id` (`fk_country_currency_id`),
  CONSTRAINT `currency_conversion_detail_ibfk_1` FOREIGN KEY (`fk_currency_conversion_id`) REFERENCES `currency_conversion` (`currency_conversion_id`),
  CONSTRAINT `currency_conversion_detail_ibfk_2` FOREIGN KEY (`fk_country_currency_id`) REFERENCES `country_currency` (`country_currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `custom_financial_year`;
CREATE TABLE `custom_financial_year` (
  `custom_financial_year_id` int NOT NULL AUTO_INCREMENT,
  `custom_financial_year_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `custom_financial_year_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `fk_office_id` int NOT NULL,
  `custom_financial_year_start_month` int NOT NULL DEFAULT '7',
  `custom_financial_year_reset_date` date DEFAULT NULL,
  `custom_financial_year_is_active` int NOT NULL DEFAULT '1',
  `custom_financial_year_is_default` int NOT NULL DEFAULT '1',
  `custom_financial_year_created_date` date DEFAULT NULL,
  `custom_financial_year_created_by` int DEFAULT NULL,
  `custom_financial_year_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `custom_financial_year_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`custom_financial_year_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `custom_financial_year_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `dashboard`;
CREATE TABLE `dashboard` (
  `dashboard_id` int NOT NULL AUTO_INCREMENT,
  `dashboard_name` varchar(100) DEFAULT NULL,
  `dashboard_type` enum('stacked-bar-100','stacked-bar') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `dashboard_data` json DEFAULT NULL,
  `dashboard_created_date` date DEFAULT NULL,
  `dashboard_created_by` int DEFAULT NULL,
  `dashboard_last_modified_by` int DEFAULT NULL,
  `dashboard_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`dashboard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `dashboard_change`;
CREATE TABLE `dashboard_change` (
  `dashboard_change_id` int NOT NULL AUTO_INCREMENT,
  `dashboard_change_name` varchar(100) NOT NULL,
  `dashboard_change_track_number` varchar(100) NOT NULL,
  `fk_office_id` int NOT NULL,
  `dashboard_change_date` date NOT NULL,
  `dashboard_change_month` date NOT NULL,
  `dashboard_change_status` int NOT NULL DEFAULT '1',
  `dashboard_change_created_date` date DEFAULT NULL,
  `dashboard_change_created_by` int DEFAULT NULL,
  `dashboard_change_last_modified_by` int DEFAULT NULL,
  `dashboard_change_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`dashboard_change_id`),
  UNIQUE KEY `composite_key` (`fk_office_id`,`dashboard_change_month`,`dashboard_change_status`),
  CONSTRAINT `dashboard_change_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `department`;
CREATE TABLE `department` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `department_track_number` varchar(100) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_description` longtext NOT NULL,
  `fk_context_definition_id` int NOT NULL DEFAULT '0',
  `department_is_active` int NOT NULL DEFAULT '1',
  `department_created_date` date NOT NULL,
  `department_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `department_created_by` int NOT NULL,
  `department_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`department_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `department_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `department_user`;
CREATE TABLE `department_user` (
  `department_user_id` int NOT NULL AUTO_INCREMENT,
  `department_user_track_number` varchar(100) NOT NULL,
  `department_user_name` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `fk_user_id` int NOT NULL,
  `fk_department_id` int NOT NULL,
  `department_user_created_date` date DEFAULT NULL,
  `department_user_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department_user_created_by` int DEFAULT NULL,
  `department_user_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`department_user_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `department_user_ibfk_1` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `designation`;
CREATE TABLE `designation` (
  `designation_id` int NOT NULL AUTO_INCREMENT,
  `designation_track_number` varchar(100) NOT NULL,
  `designation_name` varchar(100) NOT NULL,
  `fk_context_definition_id` int NOT NULL,
  `designation_created_date` date NOT NULL,
  `designation_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `designation_created_by` int NOT NULL,
  `designation_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`designation_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_center_group_hierarchy_id` (`fk_context_definition_id`),
  CONSTRAINT `designation_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `designation_ibfk_3` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `email_template`;
CREATE TABLE `email_template` (
  `email_template_id` int NOT NULL AUTO_INCREMENT,
  `email_template_track_number` varchar(100) NOT NULL,
  `email_template_name` varchar(100) NOT NULL,
  `email_template_subject` longtext NOT NULL,
  `email_template_body` longtext NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `fk_permission_label_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `email_template_created_by` int DEFAULT NULL,
  `email_template_created_date` date DEFAULT NULL,
  `email_template_last_modified_by` int DEFAULT NULL,
  `email_template_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`email_template_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_permission_label_id` (`fk_permission_label_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `email_template_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `email_template_ibfk_2` FOREIGN KEY (`fk_permission_label_id`) REFERENCES `permission_label` (`permission_label_id`),
  CONSTRAINT `email_template_ibfk_3` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `event_track_number` varchar(100) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `fk_approve_item_id` int NOT NULL,
  `event_action` int NOT NULL COMMENT '1 = data, 2 = access',
  `event_json_string` longtext NOT NULL,
  `fk_user_id` int NOT NULL,
  `event_created_by` int NOT NULL,
  `event_created_date` date NOT NULL,
  `event_last_modified_by` int NOT NULL,
  `event_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `event_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `expense_account`;
CREATE TABLE `expense_account` (
  `expense_account_id` int NOT NULL AUTO_INCREMENT,
  `expense_account_track_number` varchar(100) DEFAULT NULL,
  `expense_account_name` varchar(100) DEFAULT NULL,
  `expense_account_description` varchar(100) DEFAULT NULL,
  `expense_account_code` varchar(10) DEFAULT NULL,
  `expense_account_is_admin` int DEFAULT NULL,
  `fk_expense_vote_heads_category_id` int DEFAULT NULL,
  `expense_account_is_medical_rembursable` int DEFAULT '0',
  `expense_account_is_active` int DEFAULT NULL,
  `expense_account_is_budgeted` int DEFAULT NULL,
  `fk_income_account_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `expense_account_created_date` date DEFAULT NULL,
  `expense_account_last_modified_date` date DEFAULT NULL,
  `expense_account_created_by` int DEFAULT NULL,
  `expense_account_last_modified_by` int DEFAULT NULL,
  PRIMARY KEY (`expense_account_id`),
  KEY `fk_expense_account_income_account_idx` (`fk_income_account_id`),
  KEY `fk_expense_vote_heads_category_id` (`fk_expense_vote_heads_category_id`),
  CONSTRAINT `expense_account_ibfk_1` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `expense_account_ibfk_2` FOREIGN KEY (`fk_expense_vote_heads_category_id`) REFERENCES `expense_vote_heads_category` (`expense_vote_heads_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds the expense accounts';


DROP TABLE IF EXISTS `expense_account_office_association`;
CREATE TABLE `expense_account_office_association` (
  `expense_account_office_association_id` int NOT NULL AUTO_INCREMENT,
  `expense_account_office_association_name` varchar(100) NOT NULL,
  `expense_account_office_association_track_number` varchar(100) NOT NULL,
  `fk_expense_account_id` int NOT NULL,
  `fk_office_id` int NOT NULL,
  `expense_account_office_association_is_active` int NOT NULL DEFAULT '1',
  `expense_account_office_association_created_date` date NOT NULL,
  `expense_account_office_association_created_by` int NOT NULL,
  `expense_account_office_association_last_modified_by` int NOT NULL,
  `expense_account_office_association_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`expense_account_office_association_id`),
  KEY `fk_expense_account_id` (`fk_expense_account_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `expense_account_office_association_ibfk_1` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`),
  CONSTRAINT `expense_account_office_association_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `expense_vote_heads_category`;
CREATE TABLE `expense_vote_heads_category` (
  `expense_vote_heads_category_id` int NOT NULL AUTO_INCREMENT,
  `expense_vote_heads_category_track_number` varchar(100) NOT NULL,
  `expense_vote_heads_category_name` varchar(100) NOT NULL,
  `expense_vote_heads_category_description` longtext NOT NULL,
  `fk_funding_stream_id` int NOT NULL,
  `expense_vote_heads_category_code` enum('cognitive','spriritual','social_emotional','physical','administration','gifts','non_compassion','ongoing_interventions','individual_interventions','depreciation','payroll_liability') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `expense_vote_heads_category_is_active` int NOT NULL DEFAULT '1',
  `expense_vote_heads_category_created_date` date NOT NULL,
  `expense_vote_heads_category_created_by` int NOT NULL,
  `expense_vote_heads_category_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expense_vote_heads_category_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`expense_vote_heads_category_id`),
  KEY `fk_funding_stream_id` (`fk_funding_stream_id`),
  CONSTRAINT `expense_vote_heads_category_ibfk_1` FOREIGN KEY (`fk_funding_stream_id`) REFERENCES `funding_stream` (`funding_stream_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `financial_report`;
CREATE TABLE `financial_report` (
  `financial_report_id` int NOT NULL AUTO_INCREMENT,
  `financial_report_track_number` varchar(100) NOT NULL,
  `financial_report_name` varchar(100) NOT NULL,
  `financial_report_month` date NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_budget_id` int DEFAULT NULL,
  `financial_report_statement_date` date DEFAULT NULL,
  `financial_report_submitted_date` date DEFAULT NULL,
  `financial_report_approved_date` date DEFAULT NULL,
  `financial_report_is_submitted` int NOT NULL DEFAULT '0',
  `closing_fund_balance_data` json DEFAULT NULL,
  `month_fund_balance_report_data` json DEFAULT NULL,
  `closing_project_balance_data` json DEFAULT NULL,
  `closing_total_cash_balance_data` json DEFAULT NULL,
  `closing_cash_balance_data` json DEFAULT NULL,
  `closing_bank_balance_data` json DEFAULT NULL,
  `closing_total_statement_balance_data` json DEFAULT NULL,
  `closing_statement_balance_data` json DEFAULT NULL,
  `closing_outstanding_cheques_data` json DEFAULT NULL,
  `closing_cleared_cheques_data` json DEFAULT NULL,
  `closing_transit_deposit_data` json DEFAULT NULL,
  `closing_cleared_transit_deposit_data` json DEFAULT NULL,
  `closing_expense_report_data` json DEFAULT NULL,
  `closing_overdue_cheques_data` json DEFAULT NULL,
  `closing_overdue_deposit_data` json DEFAULT NULL,
  `month_vouchers` json DEFAULT NULL,
  `to_date_financial_ratios` json DEFAULT NULL,
  `financial_report_is_reconciled` int DEFAULT NULL,
  `financial_report_created_date` date DEFAULT NULL,
  `financial_report_created_by` int DEFAULT NULL,
  `financial_report_last_modified_by` int DEFAULT NULL,
  `financial_report_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `financial_report_approvers` json DEFAULT NULL,
  PRIMARY KEY (`financial_report_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_budget_id` (`fk_budget_id`),
  CONSTRAINT `financial_report_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `financial_report_ibfk_2` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `financial_report_uploaded_statements`;
CREATE TABLE `financial_report_uploaded_statements` (`fk_financial_report_id` int, `count_of_uploaded_statements` bigint);


DROP TABLE IF EXISTS `funder`;
CREATE TABLE `funder` (
  `funder_id` int NOT NULL AUTO_INCREMENT,
  `funder_track_number` varchar(100) DEFAULT NULL,
  `funder_name` varchar(45) DEFAULT NULL,
  `funder_description` varchar(45) DEFAULT NULL,
  `fk_account_system_id` int DEFAULT NULL,
  `funder_created_date` date DEFAULT NULL,
  `funder_last_modified_date` date DEFAULT NULL,
  `funder_created_by` int DEFAULT NULL,
  `funder_last_modified_by` int DEFAULT NULL,
  `funder_deleted_at` datetime DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`funder_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `funder_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds donor (funders) bio-information';


DROP TABLE IF EXISTS `funding_status`;
CREATE TABLE `funding_status` (
  `funding_status_id` int NOT NULL AUTO_INCREMENT,
  `funding_status_track_number` varchar(100) DEFAULT NULL,
  `funding_status_name` varchar(100) DEFAULT NULL,
  `funding_status_is_active` int DEFAULT NULL,
  `fk_account_system_id` int DEFAULT NULL,
  `funding_status_created_date` date DEFAULT NULL,
  `funding_status_created_by` int DEFAULT NULL,
  `funding_status_last_modified_by` int DEFAULT NULL,
  `funding_status_last_modified_date` date DEFAULT NULL,
  `funding_status_is_available` int NOT NULL DEFAULT '0',
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`funding_status_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `funding_status_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `funding_stream`;
CREATE TABLE `funding_stream` (
  `funding_stream_id` int NOT NULL AUTO_INCREMENT,
  `funding_stream_name` varchar(100) NOT NULL,
  `funding_stream_track_number` varchar(100) NOT NULL,
  `funding_stream_code` varchar(50) NOT NULL,
  `funding_stream_created_date` date NOT NULL,
  `funding_stream_created_by` int NOT NULL,
  `funding_stream_last_modified_by` int NOT NULL,
  `funding_stream_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`funding_stream_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `funds_transfer`;
CREATE TABLE `funds_transfer` (
  `funds_transfer_id` int NOT NULL AUTO_INCREMENT,
  `funds_transfer_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `funds_transfer_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fk_office_id` int NOT NULL,
  `funds_transfer_source_account_id` int NOT NULL,
  `funds_transfer_target_account_id` int NOT NULL,
  `funds_transfer_source_project_allocation_id` int NOT NULL,
  `funds_transfer_target_project_allocation_id` int NOT NULL,
  `funds_transfer_type` int NOT NULL COMMENT '1 => ''income_transfer'', 2 => ''expense_transfer''',
  `funds_transfer_amount` decimal(50,2) NOT NULL,
  `funds_transfer_description` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `fk_voucher_id` int DEFAULT NULL,
  `funds_transfer_deleted_at` date DEFAULT NULL,
  `fk_status_id` int NOT NULL,
  `funds_transfer_created_date` date NOT NULL,
  `funds_transfer_created_by` int NOT NULL,
  `funds_transfer_last_modified_by` int NOT NULL,
  `funds_transfer_last_modified_date` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `funds_transfer_approvers` json DEFAULT NULL,
  PRIMARY KEY (`funds_transfer_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `funds_transfer_source_project_allocation_id` (`funds_transfer_source_project_allocation_id`),
  KEY `funds_transfer_target_project_allocation_id` (`funds_transfer_target_project_allocation_id`),
  CONSTRAINT `funds_transfer_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `funds_transfer_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `funds_transfer_ibfk_3` FOREIGN KEY (`funds_transfer_source_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `gift_completion`;
CREATE TABLE `gift_completion` (
  `gift_completion_id` int NOT NULL AUTO_INCREMENT,
  `gift_completion_track_number` varchar(100) NOT NULL,
  `gift_completion_name` varchar(100) NOT NULL,
  `gift_completion_gift_number` varchar(20) NOT NULL,
  `gift_completion_gift_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `gift_completion_income_amount` decimal(50,2) NOT NULL DEFAULT '0.00',
  `gift_completion_expense_amount` decimal(50,3) NOT NULL DEFAULT '0.000',
  `gift_completion_income_date` date DEFAULT NULL,
  `gift_completion_expense_date` date DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `gift_completion_participant_number` varchar(20) NOT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `gift_completion_created_date` date DEFAULT NULL,
  `gift_completion_created_by` int DEFAULT NULL,
  `gift_completion_last_modified_by` int DEFAULT NULL,
  `gift_completion_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gift_completion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `gift_expense_accounts`;
CREATE TABLE `gift_expense_accounts` (`expense_account_id` int, `expense_account_name` varchar(100), `expense_account_code` varchar(10), `category_name` varchar(100), `funding_stream_name` varchar(100), `funding_stream_code` varchar(50), `account_system_id` int);


DROP VIEW IF EXISTS `gift_expense_transactions`;
CREATE TABLE `gift_expense_transactions` (`voucher_id` int, `voucher_number` int, `voucher_date` date, `office_id` int, `expense_account_id` int, `voucher_detail_total_cost` decimal(65,2), `status_id` int);


DROP VIEW IF EXISTS `gift_income_accounts`;
CREATE TABLE `gift_income_accounts` (`income_account_id` int, `income_account_name` varchar(100), `income_account_code` varchar(10), `category_name` varchar(100), `funding_stream_name` varchar(100), `funding_stream_code` varchar(50), `account_system_id` int);


DROP VIEW IF EXISTS `gift_income_transactions`;
CREATE TABLE `gift_income_transactions` (`voucher_id` int, `voucher_number` int, `voucher_date` date, `office_id` int, `income_account_id` int, `voucher_detail_total_cost` decimal(65,2), `status_id` int);


DROP TABLE IF EXISTS `gifts_reconciliation`;
CREATE TABLE `gifts_reconciliation` (
  `gifts_reconciliation_id` int NOT NULL AUTO_INCREMENT,
  `gifts_reconciliation_track_number` varchar(100) NOT NULL,
  `gifts_reconciliation_name` varchar(100) NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_voucher_id` int NOT NULL,
  `gifts_reconciliation_month` date NOT NULL,
  `gifts_reconciliation_participant_number` varchar(20) NOT NULL,
  `gifts_reconciliation_gift_number` varchar(20) NOT NULL,
  `gifts_reconciliation_transaction_type` int NOT NULL DEFAULT '2' COMMENT '1=income, 2=expense',
  `gifts_reconciliation_amount` decimal(50,2) NOT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `gifts_reconciliation_created_date` date DEFAULT NULL,
  `gifts_reconciliation_created_by` int DEFAULT NULL,
  `gifts_reconciliation_last_modified_by` int DEFAULT NULL,
  `gifts_reconciliation_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gifts_reconciliation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `health_facility`;
CREATE TABLE `health_facility` (
  `health_facility_id` int NOT NULL AUTO_INCREMENT,
  `health_facility_name` longtext NOT NULL,
  `health_facility_track_number` varchar(100) NOT NULL,
  `health_facility_type` varchar(50) NOT NULL COMMENT 'private, public, missionary',
  `fk_account_system_id` int NOT NULL,
  `support_docs_needed` tinyint NOT NULL DEFAULT '0' COMMENT '0=No need for support docs, 1=needs support docs',
  `health_facility_created_by` int NOT NULL,
  `health_facility_created_date` date NOT NULL,
  `health_facility_last_modified_by` int NOT NULL,
  `health_facility_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`health_facility_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `health_facility_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `history`;
CREATE TABLE `history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `fk_approve_item_id` int DEFAULT NULL,
  `fk_user_id` int DEFAULT NULL,
  `history_action` int DEFAULT NULL COMMENT '1-update, 2 - delete',
  `history_current_body` longtext,
  `history_updated_body` longtext,
  `history_created_date` date DEFAULT NULL,
  `history_created_by` int DEFAULT NULL,
  `history_last_modified_by` int DEFAULT NULL,
  `history_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `history_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `history_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `income_account`;
CREATE TABLE `income_account` (
  `income_account_id` int NOT NULL AUTO_INCREMENT,
  `income_account_track_number` varchar(100) NOT NULL,
  `income_account_name` varchar(100) NOT NULL,
  `income_account_description` varchar(100) DEFAULT NULL,
  `income_account_code` varchar(10) DEFAULT NULL,
  `income_account_reconciliation_is_required` int DEFAULT NULL COMMENT '0 - reconciliation not required, 1 - reconciliation required',
  `income_account_is_active` int DEFAULT NULL,
  `fk_income_vote_heads_category_id` int DEFAULT NULL,
  `income_account_is_budgeted` int DEFAULT NULL,
  `income_account_is_donor_funded` int DEFAULT NULL,
  `fk_account_system_id` int DEFAULT NULL,
  `income_account_created_date` date DEFAULT NULL,
  `income_account_last_modified_date` date DEFAULT NULL,
  `income_account_created_by` int DEFAULT NULL,
  `income_account_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`income_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_income_vote_heads_category_id` (`fk_income_vote_heads_category_id`),
  CONSTRAINT `income_account_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `income_account_ibfk_3` FOREIGN KEY (`fk_income_vote_heads_category_id`) REFERENCES `income_vote_heads_category` (`income_vote_heads_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table contains the income accounts. ';


DROP TABLE IF EXISTS `income_vote_heads_category`;
CREATE TABLE `income_vote_heads_category` (
  `income_vote_heads_category_id` int NOT NULL AUTO_INCREMENT,
  `income_vote_heads_category_track_number` varchar(100) NOT NULL,
  `income_vote_heads_category_name` varchar(100) NOT NULL,
  `income_vote_heads_category_description` longtext NOT NULL,
  `fk_funding_stream_id` int NOT NULL,
  `income_vote_heads_category_code` enum('support','    gifts','    non_compassion','    ongoing_intervention','    individual_intervention') DEFAULT NULL,
  `income_vote_heads_category_is_active` int NOT NULL DEFAULT '1',
  `income_vote_heads_category_created_date` date NOT NULL,
  `income_vote_heads_category_created_by` int NOT NULL,
  `income_vote_heads_category_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `income_vote_heads_category_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`income_vote_heads_category_id`),
  KEY `fk_funding_stream_id` (`fk_funding_stream_id`),
  CONSTRAINT `income_vote_heads_category_ibfk_1` FOREIGN KEY (`fk_funding_stream_id`) REFERENCES `funding_stream` (`funding_stream_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `item_reason`;
CREATE TABLE `item_reason` (
  `item_reason_id` int NOT NULL AUTO_INCREMENT,
  `item_reason_track_number` varchar(100) NOT NULL,
  `item_reason_name` varchar(100) NOT NULL,
  `item_reason_is_active` int NOT NULL DEFAULT '1',
  `fk_approve_item_id` int NOT NULL,
  `item_reason_is_default` int DEFAULT '0',
  `item_reason_created_by` int DEFAULT NULL,
  `item_reason_created_date` date DEFAULT NULL,
  `item_reason_last_modified_by` int DEFAULT NULL,
  `item_reason_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`item_reason_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  CONSTRAINT `item_reason_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `journal`;
CREATE TABLE `journal` (
  `journal_id` int NOT NULL AUTO_INCREMENT,
  `journal_track_number` varchar(100) NOT NULL,
  `journal_name` varchar(100) NOT NULL,
  `journal_month` date NOT NULL,
  `fk_office_id` int NOT NULL,
  `journal_created_date` date DEFAULT NULL,
  `journal_created_by` int DEFAULT NULL,
  `journal_last_modified_by` int DEFAULT NULL,
  `journal_last_modified_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`journal_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `journal_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `language`;
CREATE TABLE `language` (
  `language_id` int NOT NULL AUTO_INCREMENT,
  `language_track_number` varchar(100) NOT NULL,
  `language_name` varchar(100) DEFAULT NULL,
  `language_code` varchar(10) DEFAULT NULL,
  `language_is_default` int DEFAULT '0',
  `language_created_date` date DEFAULT NULL,
  `language_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `language_deleted_at` date DEFAULT NULL,
  `language_created_by` int DEFAULT NULL,
  `language_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `language_phrase`;
CREATE TABLE `language_phrase` (
  `language_phrase_id` int NOT NULL AUTO_INCREMENT,
  `fk_language_id` int DEFAULT NULL,
  `fk_account_system_id` int DEFAULT NULL,
  `language_phrase_data` longtext CHARACTER SET utf8 COLLATE utf8_swedish_ci,
  `language_phrase_created_date` date DEFAULT NULL,
  `language_phrase_created_by` int DEFAULT NULL,
  `language_phrase_last_modified_by` int DEFAULT NULL,
  `language_phrase_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`language_phrase_id`),
  KEY `fk_language_id_idx` (`fk_language_id`),
  KEY `fk_account_system_id_idx` (`fk_account_system_id`),
  CONSTRAINT `fk_language_id` FOREIGN KEY (`fk_language_id`) REFERENCES `language` (`language_id`),
  CONSTRAINT `language_phrase_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `language_untranslated_phrase`;
CREATE TABLE `language_untranslated_phrase` (
  `language_untranslated_phrase_id` int NOT NULL,
  `phrase` int NOT NULL,
  `translation` int NOT NULL,
  `language_untranslated_phrase_created_date` datetime DEFAULT NULL,
  `language_untranslated_phrase_created_by` int DEFAULT NULL,
  `language_untranslated_phrase_last_modified_by` int DEFAULT NULL,
  `language_untranslated_phrase_last_modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `translation` (`translation`),
  CONSTRAINT `language_untranslated_phrase_ibfk_1` FOREIGN KEY (`translation`) REFERENCES `translation` (`translation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `learning`;
CREATE TABLE `learning` (
  `learning_id` int NOT NULL AUTO_INCREMENT,
  `learning_name` varchar(200) NOT NULL,
  `learning_track_number` varchar(200) NOT NULL,
  `learning_created_date` date DEFAULT NULL,
  `learning_created_by` int DEFAULT NULL,
  `learning_last_modified_by` int DEFAULT NULL,
  `learning_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`learning_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `mail_log`;
CREATE TABLE `mail_log` (
  `mail_log_id` int NOT NULL AUTO_INCREMENT,
  `mail_log_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `mail_log_name` varchar(100) NOT NULL,
  `mail_log_recipients` longtext NOT NULL,
  `mail_log_sending_status` int NOT NULL DEFAULT '0',
  `mail_log_send_attempts` int NOT NULL DEFAULT '0',
  `mail_log_message` longtext NOT NULL,
  `mail_log_created_by` int DEFAULT NULL,
  `mail_log_created_date` date DEFAULT NULL,
  `mail_log_last_modified_by` int DEFAULT NULL,
  `mail_log_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`mail_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `medical_claim`;
CREATE TABLE `medical_claim` (
  `medical_claim_id` int NOT NULL AUTO_INCREMENT,
  `medical_claim_name` longtext NOT NULL,
  `medical_claim_track_number` varchar(100) NOT NULL,
  `fk_office_id` int NOT NULL,
  `medical_beneficiary_number` varchar(100) DEFAULT NULL,
  `medical_claim_incident_id` varchar(50) NOT NULL,
  `medical_claim_count` int NOT NULL,
  `medical_claim_treatment_date` date NOT NULL,
  `medical_claim_diagnosis` longtext NOT NULL,
  `medical_claim_amount_spent` decimal(50,2) NOT NULL,
  `medical_claim_caregiver_contribution` decimal(50,2) NOT NULL,
  `medical_claim_amount_reimbursed` decimal(50,2) NOT NULL,
  `medical_claim_govt_insurance_number` varchar(100) NOT NULL,
  `medical_claim_facility` varchar(255) NOT NULL,
  `support_documents_need_flag` tinyint NOT NULL DEFAULT '0',
  `fk_health_facility_id` int NOT NULL,
  `fk_medical_claim_type_id` int DEFAULT NULL,
  `fk_voucher_id` int NOT NULL,
  `fk_attachment_id` int DEFAULT NULL,
  `medical_claim_created_by` int NOT NULL,
  `medical_claim_created_date` date NOT NULL,
  `medical_claim_last_modified_by` int NOT NULL,
  `medical_claim_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`medical_claim_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_health_facility_id` (`fk_health_facility_id`),
  KEY `fk_medical_claim_type_id` (`fk_medical_claim_type_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `medical_claim_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `medical_claim_ibfk_3` FOREIGN KEY (`fk_health_facility_id`) REFERENCES `health_facility` (`health_facility_id`),
  CONSTRAINT `medical_claim_ibfk_5` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `medical_claim_admin_setting`;
CREATE TABLE `medical_claim_admin_setting` (
  `medical_claim_admin_setting_id` int NOT NULL AUTO_INCREMENT,
  `medical_claim_admin_setting_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `medical_claim_admin_setting_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `medical_claim_admin_setting_created_by` int NOT NULL,
  `medical_claim_admin_setting_created_date` date NOT NULL,
  `medical_claim_admin_setting_last_modified_by` int NOT NULL,
  `medical_claim_admin_setting_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  PRIMARY KEY (`medical_claim_admin_setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `medical_claim_setting`;
CREATE TABLE `medical_claim_setting` (
  `medical_claim_setting_id` int NOT NULL AUTO_INCREMENT,
  `medical_claim_setting_name` varchar(50) NOT NULL COMMENT '1=percentage_caregiver_contribution,2= percentage_caregiver_relief, 3=valid_claiming_days, 4=medical_claiming_expense_accounts, 5=minimum_claimable_amount',
  `fk_medical_claim_admin_setting_id` int NOT NULL DEFAULT '0',
  `medical_claim_setting_track_number` varchar(100) NOT NULL,
  `medical_claim_setting_value` varchar(100) NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `medical_claim_setting_created_by` int NOT NULL,
  `medical_claim_setting_created_date` date NOT NULL,
  `medical_claim_setting_last_modified_by` int NOT NULL,
  `medical_claim_setting_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`medical_claim_setting_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `medical_claim_setting_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `menu_id` int NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(100) DEFAULT NULL,
  `menu_derivative_controller` varchar(100) DEFAULT NULL,
  `menu_is_active` int NOT NULL DEFAULT '1',
  `menu_created_date` date DEFAULT NULL,
  `menu_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_created_by` int DEFAULT NULL,
  `menu_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `menu_derivative_controller` (`menu_derivative_controller`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `menu_user_order`;
CREATE TABLE `menu_user_order` (
  `menu_user_order_id` int NOT NULL AUTO_INCREMENT,
  `fk_user_id` int NOT NULL,
  `menu_user_order_track_number` varchar(100) DEFAULT NULL,
  `menu_user_order_name` varchar(100) DEFAULT NULL,
  `fk_menu_id` int DEFAULT NULL,
  `menu_user_order_is_favorite` int DEFAULT '0',
  `menu_user_order_is_active` int NOT NULL DEFAULT '1',
  `menu_user_order_level` int NOT NULL DEFAULT '1',
  `menu_user_order_priority_item` int NOT NULL DEFAULT '1',
  `menu_user_order_created_date` date DEFAULT NULL,
  `menu_user_order_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_user_order_created_by` int DEFAULT NULL,
  `menu_user_order_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`menu_user_order_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `menu_user_order_is_favorite` (`menu_user_order_is_favorite`),
  CONSTRAINT `menu_user_order_ibfk_1` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE,
  CONSTRAINT `menu_user_order_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `message_track_number` varchar(100) DEFAULT NULL,
  `message_name` varchar(100) DEFAULT NULL,
  `fk_approve_item_id` int DEFAULT NULL,
  `message_record_key` int DEFAULT NULL,
  `message_created_by` int DEFAULT NULL,
  `message_last_modified_by` int DEFAULT NULL,
  `message_created_date` date DEFAULT NULL,
  `message_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_deleted_date` date DEFAULT NULL,
  `message_is_thread_open` int DEFAULT '1',
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  CONSTRAINT `message_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `message_detail`;
CREATE TABLE `message_detail` (
  `message_detail_id` int NOT NULL AUTO_INCREMENT,
  `message_detail_track_number` varchar(100) NOT NULL,
  `message_detail_name` varchar(100) NOT NULL,
  `fk_user_id` int DEFAULT NULL,
  `message_detail_content` longtext,
  `fk_message_id` int DEFAULT NULL,
  `message_detail_readers` json DEFAULT NULL,
  `message_detail_created_date` datetime DEFAULT NULL,
  `message_detail_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_detail_deleted_date` date DEFAULT NULL,
  `message_detail_created_by` int DEFAULT NULL,
  `message_detail_last_modified_by` int DEFAULT NULL,
  `message_detail_is_reply` int DEFAULT '0',
  `message_detail_replied_message_key` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`message_detail_id`),
  KEY `fk_message_detail_message1_idx` (`fk_message_id`),
  CONSTRAINT `fk_message_detail_message1` FOREIGN KEY (`fk_message_id`) REFERENCES `message` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `month`;
CREATE TABLE `month` (
  `month_id` int NOT NULL AUTO_INCREMENT,
  `month_track_number` varchar(100) NOT NULL,
  `month_number` int NOT NULL,
  `month_name` varchar(50) NOT NULL,
  `month_order` int NOT NULL DEFAULT '0',
  `fk_status_id` int NOT NULL,
  `month_created_by` int NOT NULL,
  `month_last_modified_by` int NOT NULL,
  `month_created_date` date NOT NULL,
  `month_last_modified_date` date NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`month_id`),
  UNIQUE KEY `month_number` (`month_number`),
  UNIQUE KEY `month_order` (`month_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `month_cash_recipient_sum_amount`;
CREATE TABLE `month_cash_recipient_sum_amount` (`fk_office_id` int, `voucher_month` varchar(10), `recipient_office_bank_id` int, `recipient_office_cash_id` int, `fk_status_id` int, `source_office_cash_id` int, `source_office_bank_id` int, `voucher_type_effect_code` varchar(50), `voucher_type_account_code` varchar(10), `amount` decimal(65,2));


DROP VIEW IF EXISTS `monthly_gift_expense_transactions`;
CREATE TABLE `monthly_gift_expense_transactions` (`office_id` int, `expense_account_id` int, `voucher_date` varchar(10), `voucher_detail_total_cost` decimal(65,2));


DROP VIEW IF EXISTS `monthly_gift_income_transactions`;
CREATE TABLE `monthly_gift_income_transactions` (`office_id` int, `income_account_id` int, `voucher_date` varchar(10), `voucher_detail_total_cost` decimal(65,2));


DROP VIEW IF EXISTS `monthly_sum_expense_per_center`;
CREATE TABLE `monthly_sum_expense_per_center` (`fk_office_id` int, `voucher_month` varchar(10), `fk_office_bank_id` int, `fk_status_id` int, `fk_expense_account_id` int, `amount` decimal(65,2));


DROP VIEW IF EXISTS `monthly_sum_income_expense_per_center`;
CREATE TABLE `monthly_sum_income_expense_per_center` (`fk_office_id` int, `voucher_month` varchar(10), `fk_office_bank_id` int, `fk_status_id` int, `income_account_id` int, `amount` decimal(65,2));


DROP VIEW IF EXISTS `monthly_sum_income_per_center`;
CREATE TABLE `monthly_sum_income_per_center` (`fk_office_id` int, `voucher_month` varchar(10), `fk_office_bank_id` int, `project_allocation_id` int, `fk_project_id` int, `fk_status_id` int, `income_account_id` int, `amount` decimal(65,2));


DROP VIEW IF EXISTS `monthly_sum_transactions_by_account_effect`;
CREATE TABLE `monthly_sum_transactions_by_account_effect` (`fk_office_id` int, `voucher_month` varchar(10), `fk_office_bank_id` int, `fk_office_cash_id` int, `fk_status_id` int, `voucher_type_effect_code` varchar(50), `voucher_type_account_code` varchar(10), `amount` decimal(65,2));


DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `notification_track_number` varchar(100) DEFAULT NULL,
  `notification_title` varchar(255) DEFAULT NULL,
  `notification_message` longtext,
  `notification_status` int NOT NULL DEFAULT '0' COMMENT ' 0 - Unread, 1 - Read',
  `notification_created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notification_read_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notification_recipient_user_id` json DEFAULT NULL,
  `notification_sender_user_id` int DEFAULT NULL,
  `fk_approval_id` json DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `notification_table_name` varchar(50) DEFAULT NULL,
  `notification_table_id` int DEFAULT NULL,
  `fk_user_id` int DEFAULT NULL,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `office`;
CREATE TABLE `office` (
  `office_id` int NOT NULL AUTO_INCREMENT,
  `office_track_number` varchar(100) DEFAULT NULL,
  `office_name` longtext NOT NULL,
  `office_description` longtext NOT NULL,
  `office_code` varchar(45) NOT NULL,
  `fk_context_definition_id` int NOT NULL,
  `office_start_date` date NOT NULL,
  `office_end_date` date DEFAULT NULL,
  `office_is_active` int NOT NULL DEFAULT '1',
  `office_is_suspended` int DEFAULT '0',
  `office_is_readonly` int NOT NULL DEFAULT '0',
  `fk_account_system_id` int NOT NULL DEFAULT '1',
  `fk_country_currency_id` int NOT NULL,
  `office_created_by` int NOT NULL,
  `office_created_date` date NOT NULL,
  `office_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `office_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`office_id`),
  UNIQUE KEY `office_code` (`office_code`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_country_currency_id` (`fk_country_currency_id`),
  CONSTRAINT `office_ibfk_1` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `office_ibfk_4` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `office_ibfk_6` FOREIGN KEY (`fk_country_currency_id`) REFERENCES `country_currency` (`country_currency_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table list all the remote sites for the organization';


DROP TABLE IF EXISTS `office_accrual`;
CREATE TABLE `office_accrual` (
  `office_accrual_id` int NOT NULL AUTO_INCREMENT,
  `office_accrual_track_number` longtext NOT NULL,
  `office_accrual_name` varchar(200) NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_accrual_account_id` int NOT NULL,
  `office_accrual_is_active` int NOT NULL DEFAULT '1',
  `office_accrual_created_by` int DEFAULT NULL,
  `office_accrual_created_date` date DEFAULT NULL,
  `office_accrual_last_modified_by` int DEFAULT NULL,
  `office_accrual_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`office_accrual_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_accrual_account_id` (`fk_accrual_account_id`),
  CONSTRAINT `office_accrual_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `office_accrual_ibfk_2` FOREIGN KEY (`fk_accrual_account_id`) REFERENCES `accrual_account` (`accrual_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `office_bank`;
CREATE TABLE `office_bank` (
  `office_bank_id` int NOT NULL AUTO_INCREMENT,
  `office_bank_track_number` varchar(100) DEFAULT NULL,
  `office_bank_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `office_bank_account_number` varchar(100) DEFAULT NULL,
  `fk_office_id` int DEFAULT NULL,
  `fk_bank_id` int DEFAULT NULL,
  `office_bank_chequebook_size` int NOT NULL,
  `office_bank_book_exemption_expiry_date` date DEFAULT NULL,
  `office_bank_is_active` int DEFAULT '1',
  `office_bank_is_default` int DEFAULT '1',
  `office_bank_start_date` date DEFAULT NULL,
  `office_bank_closure_date` date DEFAULT NULL,
  `office_bank_created_date` date DEFAULT NULL,
  `office_bank_created_by` int DEFAULT NULL,
  `office_bank_last_modified_date` timestamp NULL DEFAULT NULL,
  `office_bank_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`office_bank_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_bank_id` (`fk_bank_id`),
  CONSTRAINT `office_bank_ibfk_3` FOREIGN KEY (`fk_bank_id`) REFERENCES `bank` (`bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `office_bank_ibfk_4` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_bank_project_allocation`;
CREATE TABLE `office_bank_project_allocation` (
  `office_bank_project_allocation_id` int NOT NULL AUTO_INCREMENT,
  `office_bank_project_allocation_name` varchar(100) NOT NULL,
  `office_bank_project_allocation_track_number` varchar(100) NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_project_allocation_id` int NOT NULL,
  `office_bank_project_allocation_created_date` date DEFAULT NULL,
  `office_bank_project_allocation_created_by` int DEFAULT NULL,
  `office_bank_project_allocation_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `office_bank_project_allocation_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`office_bank_project_allocation_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `office_bank_project_allocation_ibfk_3` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`) ON DELETE CASCADE,
  CONSTRAINT `office_bank_project_allocation_ibfk_4` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_cash`;
CREATE TABLE `office_cash` (
  `office_cash_id` int NOT NULL AUTO_INCREMENT,
  `office_cash_name` varchar(100) NOT NULL,
  `office_cash_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `office_cash_is_active` int NOT NULL DEFAULT '1',
  `office_cash_created_by` int NOT NULL,
  `office_cash_created_date` date NOT NULL,
  `office_cash_last_modified_by` int NOT NULL,
  `office_cash_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`office_cash_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `office_cash_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_group`;
CREATE TABLE `office_group` (
  `office_group_id` int NOT NULL AUTO_INCREMENT,
  `office_group_track_number` varchar(100) NOT NULL,
  `office_group_name` varchar(100) NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `office_group_created_by` int NOT NULL,
  `office_group_created_date` date NOT NULL,
  `office_group_last_modified_by` int NOT NULL,
  `office_group_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`office_group_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `office_group_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `office_group_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_group_association`;
CREATE TABLE `office_group_association` (
  `office_group_association_id` int NOT NULL AUTO_INCREMENT,
  `office_group_association_name` varchar(100) NOT NULL,
  `office_group_association_track_number` varchar(100) NOT NULL,
  `fk_office_group_id` int NOT NULL,
  `fk_office_id` int NOT NULL,
  `office_group_association_is_lead` int NOT NULL DEFAULT '0',
  `office_group_association_created_by` int NOT NULL,
  `office_group_association_created_date` date NOT NULL,
  `office_group_association_last_modified_by` int NOT NULL,
  `office_group_association_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`office_group_association_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_office_group_id` (`fk_office_group_id`),
  CONSTRAINT `office_group_association_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `office_group_association_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `office_group_association_ibfk_4` FOREIGN KEY (`fk_office_group_id`) REFERENCES `office_group` (`office_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_user`;
CREATE TABLE `office_user` (
  `office_user_id` int NOT NULL AUTO_INCREMENT,
  `office_user_track_number` varchar(100) NOT NULL,
  `office_user_name` varchar(100) NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `office_user_is_active` int NOT NULL DEFAULT '1',
  `office_user_created_date` date DEFAULT NULL,
  `office_user_created_by` int NOT NULL,
  `office_user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `office_user_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`office_user_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `office_user_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `office_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `offices_missing_last_month_financial_report`;
CREATE TABLE `offices_missing_last_month_financial_report` (`office_id` int, `office_code` varchar(45), `office_name` longtext, `cluster_name` longtext, `fk_account_system_id` int);


DROP TABLE IF EXISTS `opening_accrual_balance`;
CREATE TABLE `opening_accrual_balance` (
  `opening_accrual_balance_id` int NOT NULL AUTO_INCREMENT,
  `opening_accrual_balance_name` varchar(100) NOT NULL,
  `opening_accrual_balance_track_number` longtext NOT NULL,
  `fk_system_opening_balance_id` int NOT NULL,
  `opening_accrual_balance_account` enum('receivables','payables','prepayments','depreciation','payroll_liability') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `opening_accrual_balance_amount` decimal(50,2) NOT NULL,
  `opening_accrual_balance_effect` enum('debit','credit') NOT NULL DEFAULT 'debit',
  `opening_accrual_balance_created_date` date DEFAULT NULL,
  `opening_accrual_balance_created_by` int DEFAULT NULL,
  `opening_accrual_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opening_accrual_balance_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_accrual_balance_id`),
  KEY `fk_system_opening_balanace_id` (`fk_system_opening_balance_id`),
  CONSTRAINT `opening_accrual_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `opening_allocation_balance`;
CREATE TABLE `opening_allocation_balance` (
  `opening_allocation_balance_id` int NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int NOT NULL,
  `opening_allocation_balance_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `opening_allocation_balance_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fk_project_allocation_id` int DEFAULT NULL,
  `opening_allocation_balance_amount` decimal(10,2) NOT NULL,
  `opening_allocation_balance_created_date` date DEFAULT NULL,
  `opening_allocation_balance_created_by` int DEFAULT NULL,
  `opening_allocation_balance_last_modified_by` int DEFAULT NULL,
  `opening_allocation_balance_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_allocation_balance_id`),
  KEY `system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `opening_allocation_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_allocation_balance_ibfk_2` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_bank_balance`;
CREATE TABLE `opening_bank_balance` (
  `opening_bank_balance_id` int NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int NOT NULL,
  `opening_bank_balance_track_number` varchar(100) NOT NULL,
  `opening_bank_balance_name` varchar(100) NOT NULL,
  `opening_bank_balance_amount` decimal(50,2) DEFAULT NULL,
  `opening_bank_balance_statement_amount` decimal(50,2) DEFAULT NULL,
  `opening_bank_balance_statement_date` date DEFAULT NULL,
  `opening_bank_balance_is_reconciled` int NOT NULL DEFAULT '0',
  `fk_office_bank_id` int NOT NULL,
  `opening_bank_balance_created_date` date NOT NULL,
  `opening_bank_balance_created_by` int NOT NULL,
  `opening_bank_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opening_bank_balance_last_modified_by` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_bank_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_cash_balance`;
CREATE TABLE `opening_cash_balance` (
  `opening_cash_balance_id` int NOT NULL AUTO_INCREMENT,
  `opening_cash_balance_track_number` varchar(100) NOT NULL,
  `opening_cash_balance_name` varchar(100) NOT NULL,
  `fk_system_opening_balance_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_office_cash_id` int NOT NULL,
  `opening_cash_balance_amount` decimal(10,2) NOT NULL,
  `opening_cash_balance_created_date` date DEFAULT NULL,
  `opening_cash_balance_created_by` int DEFAULT NULL,
  `opening_cash_balance_last_modified_by` int DEFAULT NULL,
  `opening_cash_balance_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_cash_balance_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_office_cash_id` (`fk_office_cash_id`),
  CONSTRAINT `opening_cash_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_cash_balance_ibfk_3` FOREIGN KEY (`fk_office_cash_id`) REFERENCES `office_cash` (`office_cash_id`),
  CONSTRAINT `opening_cash_balance_ibfk_4` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_deposit_transit`;
CREATE TABLE `opening_deposit_transit` (
  `opening_deposit_transit_id` int NOT NULL AUTO_INCREMENT,
  `opening_deposit_transit_track_number` varchar(100) NOT NULL,
  `opening_deposit_transit_name` varchar(100) NOT NULL,
  `fk_system_opening_balance_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `opening_deposit_transit_date` date NOT NULL,
  `opening_deposit_transit_description` longtext NOT NULL,
  `opening_deposit_transit_amount` decimal(10,2) NOT NULL,
  `opening_deposit_transit_is_cleared` int NOT NULL DEFAULT '0',
  `opening_deposit_transit_cleared_date` date DEFAULT NULL,
  `opening_deposit_transit_created_date` date DEFAULT NULL,
  `opening_deposit_transit_created_by` int DEFAULT NULL,
  `opening_deposit_transit_last_modified_by` int DEFAULT NULL,
  `opening_deposit_transit_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_deposit_transit_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  CONSTRAINT `opening_deposit_transit_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_fund_balance`;
CREATE TABLE `opening_fund_balance` (
  `opening_fund_balance_id` int NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int NOT NULL,
  `opening_fund_balance_track_number` varchar(100) NOT NULL,
  `opening_fund_balance_name` varchar(100) NOT NULL,
  `fk_income_account_id` int DEFAULT NULL,
  `fk_office_bank_id` int NOT NULL,
  `fk_project_id` int DEFAULT NULL,
  `opening_fund_balance_opening` decimal(20,2) DEFAULT NULL,
  `opening_fund_balance_income` decimal(20,2) DEFAULT NULL,
  `opening_fund_balance_expense` decimal(50,2) NOT NULL,
  `opening_fund_balance_amount` decimal(50,2) NOT NULL,
  `opening_fund_balance_created_date` date DEFAULT NULL,
  `opening_fund_balance_created_by` int DEFAULT NULL,
  `opening_fund_balance_last_modified_by` int DEFAULT NULL,
  `opening_fund_balance_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_fund_balance_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_project_id` (`fk_project_id`),
  CONSTRAINT `opening_fund_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_fund_balance_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`),
  CONSTRAINT `opening_fund_balance_ibfk_4` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `opening_fund_balance_ibfk_5` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_outstanding_cheque`;
CREATE TABLE `opening_outstanding_cheque` (
  `opening_outstanding_cheque_id` int NOT NULL AUTO_INCREMENT,
  `opening_outstanding_cheque_name` varchar(100) NOT NULL,
  `opening_outstanding_cheque_track_number` varchar(100) NOT NULL,
  `opening_outstanding_cheque_description` longtext NOT NULL,
  `opening_outstanding_cheque_date` date DEFAULT NULL,
  `fk_system_opening_balance_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `opening_outstanding_cheque_number` varchar(50) NOT NULL,
  `opening_outstanding_cheque_amount` decimal(10,2) NOT NULL,
  `opening_outstanding_cheque_is_cleared` int NOT NULL DEFAULT '0',
  `opening_outstanding_cheque_bounced_flag` int NOT NULL DEFAULT '0',
  `opening_outstanding_cheque_cleared_date` date DEFAULT NULL,
  `opening_outstanding_cheque_created_date` date DEFAULT NULL,
  `opening_outstanding_cheque_created_by` int DEFAULT NULL,
  `opening_outstanding_cheque_last_modified_by` int DEFAULT NULL,
  `opening_outstanding_cheque_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`opening_outstanding_cheque_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `opening_outstanding_cheque_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_outstanding_cheque_ibfk_2` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_outstanding_cheque_ibfk_4` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `overdue_transit_deposit`;
CREATE TABLE `overdue_transit_deposit` (`office_id` int, `office_code` varchar(45), `office_name` longtext, `voucher_date` date, `voucher_type_effect_code` varchar(50), `voucher_cleared` int, `amount` decimal(65,2));


DROP TABLE IF EXISTS `page_view`;
CREATE TABLE `page_view` (
  `page_view_id` int NOT NULL AUTO_INCREMENT,
  `page_view_track_number` varchar(100) NOT NULL,
  `page_view_name` varchar(100) NOT NULL,
  `page_view_description` longtext NOT NULL,
  `fk_menu_id` int NOT NULL,
  `page_view_is_default` int NOT NULL DEFAULT '0' COMMENT 'System Admin default ',
  `page_view_created_date` date NOT NULL,
  `page_view_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_created_by` int NOT NULL,
  `page_view_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`page_view_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  CONSTRAINT `page_view_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `page_view_ibfk_3` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `page_view_condition`;
CREATE TABLE `page_view_condition` (
  `page_view_condition_id` int NOT NULL AUTO_INCREMENT,
  `page_view_condition_track_number` varchar(100) NOT NULL,
  `page_view_condition_name` varchar(100) DEFAULT NULL,
  `page_view_condition_field` varchar(100) NOT NULL,
  `page_view_condition_operator` varchar(50) NOT NULL,
  `page_view_condition_value` varchar(100) NOT NULL,
  `fk_page_view_id` int NOT NULL,
  `page_view_condition_created_date` date NOT NULL,
  `page_view_condition_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_condition_created_by` int NOT NULL,
  `page_view_condition_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`page_view_condition_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_page_view_id` (`fk_page_view_id`),
  CONSTRAINT `page_view_condition_ibfk_3` FOREIGN KEY (`fk_page_view_id`) REFERENCES `page_view` (`page_view_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `page_view_role`;
CREATE TABLE `page_view_role` (
  `page_view_role_id` int NOT NULL AUTO_INCREMENT,
  `page_view_role_track_number` varchar(100) NOT NULL,
  `page_view_role_name` varchar(100) DEFAULT NULL,
  `page_view_role_is_default` int DEFAULT '0',
  `fk_page_view_id` int DEFAULT NULL,
  `fk_role_id` int DEFAULT NULL,
  `page_view_role_created_date` date NOT NULL,
  `page_view_role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_role_created_by` int NOT NULL,
  `page_view_role_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`page_view_role_id`),
  KEY `fk_page_view_id` (`fk_page_view_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `page_view_role_ibfk_1` FOREIGN KEY (`fk_page_view_id`) REFERENCES `page_view` (`page_view_id`),
  CONSTRAINT `page_view_role_ibfk_2` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`),
  CONSTRAINT `page_view_role_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `pca_plan_lock`;
CREATE TABLE `pca_plan_lock` (
  `pca_plan_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_plan_lock_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'lock',
  `pca_plan_lock_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pca_plan_lock_last_created_date` date DEFAULT NULL,
  UNIQUE KEY `annual_plan_strategy_id` (`pca_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='lock, unlock';


DROP TABLE IF EXISTS `pca_strategy`;
CREATE TABLE `pca_strategy` (
  `pca_strategy_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fk_office_id` int NOT NULL,
  `pca_strategy_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_track_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_plan_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_plan_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_objective_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_objective_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_intervention_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_intervention_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `pca_strategy_start_date` date DEFAULT NULL,
  `pca_strategy_end_date` date DEFAULT NULL,
  `pca_strategy_created_date` date DEFAULT NULL,
  `pca_strategy_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pca_strategy_created_by` int DEFAULT NULL,
  `pca_strategy_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`pca_strategy_id`,`pca_strategy_intervention_id`,`pca_strategy_plan_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `pca_strategy_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission` (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_track_number` varchar(100) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` longtext NOT NULL,
  `permission_is_active` int NOT NULL,
  `fk_permission_label_id` int NOT NULL,
  `permission_type` int NOT NULL DEFAULT '1' COMMENT 'Type 1 = Page Access, 2 = Field Access',
  `permission_field` varchar(100) NOT NULL,
  `permission_is_global` int NOT NULL DEFAULT '1',
  `fk_menu_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `permission_created_date` date DEFAULT NULL,
  `permission_created_by` int DEFAULT NULL,
  `permission_deleted_at` date DEFAULT NULL,
  `permission_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_last_modified_by` int DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  KEY `fk_permission_label_id` (`fk_permission_label_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  CONSTRAINT `permission_ibfk_2` FOREIGN KEY (`fk_permission_label_id`) REFERENCES `permission_label` (`permission_label_id`),
  CONSTRAINT `permission_ibfk_3` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permission_label`;
CREATE TABLE `permission_label` (
  `permission_label_id` int NOT NULL AUTO_INCREMENT,
  `permission_label_track_number` varchar(100) NOT NULL,
  `permission_label_name` varchar(100) NOT NULL,
  `permission_label_description` varchar(100) NOT NULL,
  `permission_label_depth` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `permission_label_created_date` date NOT NULL,
  `permission_label_created_by` int NOT NULL,
  `permission_label_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_label_last_modified_by` int NOT NULL,
  PRIMARY KEY (`permission_label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permission_template`;
CREATE TABLE `permission_template` (
  `permission_template_id` int NOT NULL AUTO_INCREMENT,
  `permission_template_track_number` varchar(100) NOT NULL,
  `permission_template_name` varchar(100) NOT NULL,
  `fk_role_group_id` int NOT NULL,
  `fk_permission_id` int NOT NULL,
  `permission_template_is_active` int NOT NULL DEFAULT '1',
  `permission_template_created_date` date NOT NULL,
  `permission_template_created_by` int NOT NULL,
  `permission_template_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_template_last_modified_by` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  PRIMARY KEY (`permission_template_id`),
  KEY `fk_role_group_id` (`fk_role_group_id`),
  KEY `fk_permission_id` (`fk_permission_id`),
  CONSTRAINT `permission_template_ibfk_2` FOREIGN KEY (`fk_role_group_id`) REFERENCES `role_group` (`role_group_id`),
  CONSTRAINT `permission_template_ibfk_5` FOREIGN KEY (`fk_permission_id`) REFERENCES `permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `project_id` int NOT NULL AUTO_INCREMENT,
  `project_track_number` varchar(100) DEFAULT NULL,
  `project_name` longtext,
  `project_code` varchar(10) NOT NULL,
  `project_description` longtext,
  `project_start_date` date DEFAULT NULL,
  `project_end_date` date DEFAULT NULL,
  `fk_funder_id` int NOT NULL,
  `project_cost` decimal(50,2) DEFAULT '0.00',
  `fk_funding_status_id` int DEFAULT NULL,
  `project_is_default` int DEFAULT '0',
  `project_created_by` int DEFAULT NULL,
  `project_last_modified_by` int DEFAULT NULL,
  `project_created_date` date DEFAULT NULL,
  `project_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  KEY `fk_funder_id` (`fk_funder_id`),
  KEY `fk_funding_status_id` (`fk_funding_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A project is a single funded proposal that need to be implemented and reported as a unit. It''s related to single funder ';


DROP TABLE IF EXISTS `project_allocation`;
CREATE TABLE `project_allocation` (
  `project_allocation_id` int NOT NULL AUTO_INCREMENT,
  `project_allocation_track_number` varchar(100) DEFAULT NULL,
  `fk_project_id` int DEFAULT NULL,
  `project_allocation_name` longtext,
  `project_allocation_amount` int DEFAULT '0',
  `project_allocation_is_active` int DEFAULT '1',
  `fk_office_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `project_allocation_extended_end_date` date DEFAULT NULL,
  `project_allocation_created_date` date DEFAULT NULL,
  `project_allocation_last_modified_date` varchar(45) DEFAULT NULL,
  `project_allocation_created_by` int DEFAULT NULL,
  `project_allocation_last_modified_by` int DEFAULT NULL,
  PRIMARY KEY (`project_allocation_id`),
  KEY `fk_project_id` (`fk_project_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `project_allocation_ibfk_3` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `project_allocation_ibfk_4` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_allocation_detail`;
CREATE TABLE `project_allocation_detail` (
  `project_allocation_detail_id` int NOT NULL,
  `project_allocation_detail_track_number` varchar(100) NOT NULL,
  `project_allocation_detail_name` varchar(100) NOT NULL,
  `fk_project_allocation_id` int NOT NULL,
  `project_allocation_detail_month` date NOT NULL,
  `project_allocation_detail_amount` decimal(10,2) NOT NULL,
  `project_allocation_detail_created_date` date DEFAULT NULL,
  `project_allocation_detail_created_by` int DEFAULT NULL,
  `project_allocation_detail_last_modified_by` int DEFAULT NULL,
  `project_allocation_detail_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `project_allocation_detail_ibfk_1` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP VIEW IF EXISTS `project_codes`;
CREATE TABLE `project_codes` (`project_name` longtext, `project_start_date` date, `project_end_date` date, `fk_account_system_id` int, `project_id` int);


DROP TABLE IF EXISTS `project_cost_proportion`;
CREATE TABLE `project_cost_proportion` (
  `project_cost_proportion_id` int NOT NULL,
  `voucher_detail_id` int DEFAULT NULL,
  `amount` varchar(45) DEFAULT NULL,
  `created_by` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  `last_modified_by` varchar(45) DEFAULT NULL,
  `last_modified_date` varchar(45) DEFAULT NULL,
  `center_project_allocation_id` int DEFAULT NULL,
  `project_cost_proportion_created_date` date DEFAULT NULL,
  `project_cost_proportion_created_by` int DEFAULT NULL,
  `project_cost_proportion_last_modified_by` int DEFAULT NULL,
  `project_cost_proportion_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`project_cost_proportion_id`),
  KEY `voucher_detail_id` (`voucher_detail_id`),
  CONSTRAINT `project_cost_proportion_ibfk_1` FOREIGN KEY (`voucher_detail_id`) REFERENCES `voucher_detail` (`voucher_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_income_account`;
CREATE TABLE `project_income_account` (
  `project_income_account_id` int NOT NULL AUTO_INCREMENT,
  `project_income_account_name` varchar(100) NOT NULL,
  `project_income_account_track_number` varchar(100) NOT NULL,
  `fk_project_id` int NOT NULL,
  `fk_income_account_id` int NOT NULL,
  `project_income_account_created_date` date NOT NULL,
  `project_income_account_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `project_income_account_created_by` int NOT NULL,
  `project_income_account_last_modified_by` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  PRIMARY KEY (`project_income_account_id`),
  KEY `fk_project_id` (`fk_project_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  CONSTRAINT `project_income_account_ibfk_3` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE,
  CONSTRAINT `project_income_account_ibfk_4` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_request_type`;
CREATE TABLE `project_request_type` (
  `project_request_type_id` int NOT NULL AUTO_INCREMENT,
  `project_request_type_track_number` varchar(100) NOT NULL,
  `project_request_type_name` varchar(100) NOT NULL,
  `fk_project_id` int NOT NULL,
  `fk_request_type_id` int NOT NULL,
  `project_request_type_created_by` int NOT NULL,
  `project_request_type_created_date` date NOT NULL,
  `project_request_type_last_modified_by` int NOT NULL,
  `project_request_type_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  PRIMARY KEY (`project_request_type_id`),
  KEY `fk_request_type_id` (`fk_request_type_id`),
  KEY `fk_project_id` (`fk_project_id`),
  CONSTRAINT `project_request_type_ibfk_2` FOREIGN KEY (`fk_request_type_id`) REFERENCES `request_type` (`request_type_id`),
  CONSTRAINT `project_request_type_ibfk_3` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `queue_backlog`;
CREATE TABLE `queue_backlog` (
  `queue_backlog_id` int NOT NULL AUTO_INCREMENT,
  `queue_backlog_body` longtext NOT NULL,
  `queue_backlog_is_pulled` int NOT NULL DEFAULT '0',
  `queue_backlog_is_received` int NOT NULL DEFAULT '0',
  `queue_backlog_created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_backlog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `reconciliation`;
CREATE TABLE `reconciliation` (
  `reconciliation_id` int NOT NULL AUTO_INCREMENT,
  `reconciliation_track_number` varchar(100) NOT NULL,
  `reconciliation_name` varchar(100) NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_office_bank_id` int NOT NULL,
  `reconciliation_statement_balance` decimal(50,2) NOT NULL DEFAULT '0.00',
  `reconciliation_is_correct` int NOT NULL DEFAULT '1',
  `fk_status_id` int DEFAULT NULL,
  `reconciliation_suspense_amount` decimal(50,2) DEFAULT NULL,
  `reconciliation_created_by` int DEFAULT NULL,
  `reconciliation_created_date` date DEFAULT NULL,
  `reconciliation_last_modified_by` int DEFAULT NULL,
  `reconciliation_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`reconciliation_id`),
  KEY `fk_reconciliation_center1_idx` (`fk_financial_report_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `reconciliation_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `reconciliation_ibfk_3` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `reimbursement_app_type`;
CREATE TABLE `reimbursement_app_type` (
  `reimbursement_app_type_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_app_type_name` varchar(100) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `reimbursement_app_type_is_active` int NOT NULL DEFAULT '0',
  `reimbursement_app_type_created_date` date DEFAULT NULL,
  `reimbursement_app_type_created_by` int DEFAULT '1',
  `reimbursement_app_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reimbursement_app_type_last_modified_by` int DEFAULT '1',
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`reimbursement_app_type_id`),
  KEY `reimbursement_app_type_created_by` (`reimbursement_app_type_created_by`),
  KEY `reimbursement_app_type_last_modified_by` (`reimbursement_app_type_last_modified_by`),
  CONSTRAINT `reimbursement_app_type_ibfk_1` FOREIGN KEY (`reimbursement_app_type_created_by`) REFERENCES `user` (`user_id`),
  CONSTRAINT `reimbursement_app_type_ibfk_2` FOREIGN KEY (`reimbursement_app_type_last_modified_by`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `reimbursement_claim`;
CREATE TABLE `reimbursement_claim` (
  `reimbursement_claim_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_claim_name` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `reimbursement_claim_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `fk_office_id` int NOT NULL,
  `fk_reimbursement_app_type_id` int NOT NULL DEFAULT '1',
  `fk_reimbursement_funding_type_id` int NOT NULL DEFAULT '1',
  `fk_context_cluster_id` int NOT NULL DEFAULT '1',
  `reimbursement_claim_beneficiary_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `reimbursement_claim_incident_id` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `reimbursement_claim_count` int NOT NULL,
  `reimbursement_claim_treatment_date` date NOT NULL,
  `reimbursement_claim_diagnosis` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `reimbursement_claim_amount_spent` decimal(50,2) NOT NULL,
  `reimbursement_claim_caregiver_contribution` decimal(50,2) NOT NULL,
  `reimbursement_claim_amount_reimbursed` decimal(50,2) NOT NULL,
  `reimbursement_claim_govt_insurance_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `reimbursement_claim_facility` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `support_documents_need_flag` tinyint NOT NULL DEFAULT '0',
  `fk_health_facility_id` int NOT NULL,
  `fk_voucher_detail_id` int NOT NULL,
  `fk_attachment_id` int DEFAULT NULL,
  `reimbursement_claim_created_by` int NOT NULL,
  `reimbursement_claim_created_date` date NOT NULL,
  `reimbursement_claim_last_modified_by` int NOT NULL,
  `reimbursement_claim_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`reimbursement_claim_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_health_facility_id` (`fk_health_facility_id`),
  KEY `fk_voucher_id` (`fk_voucher_detail_id`),
  KEY `fk_reimbursement_app_type_id` (`fk_reimbursement_app_type_id`),
  KEY `fk_reimbursement_funding_type_id` (`fk_reimbursement_funding_type_id`),
  KEY `fk_context_cluster_id` (`fk_context_cluster_id`),
  CONSTRAINT `reimbursement_claim_ibfk_1` FOREIGN KEY (`fk_reimbursement_app_type_id`) REFERENCES `reimbursement_app_type` (`reimbursement_app_type_id`),
  CONSTRAINT `reimbursement_claim_ibfk_2` FOREIGN KEY (`fk_reimbursement_funding_type_id`) REFERENCES `reimbursement_funding_type` (`reimbursement_funding_type_id`),
  CONSTRAINT `reimbursement_claim_ibfk_3` FOREIGN KEY (`fk_context_cluster_id`) REFERENCES `context_cluster` (`context_cluster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `reimbursement_comment`;
CREATE TABLE `reimbursement_comment` (
  `reimbursement_comment_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_comment_detail` longtext NOT NULL,
  `reimbursement_comment_track_number` varchar(100) NOT NULL,
  `fk_reimbursement_claim_id` int NOT NULL,
  `reimbursement_comment_created_date` date NOT NULL,
  `reimbursement_comment_created_by` int DEFAULT NULL,
  `reimbursement_comment_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reimbursement_comment_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `reimbursement_comment_last_modified_by` int DEFAULT NULL,
  `reimbursement_comment_last_modified_date` date DEFAULT NULL,
  PRIMARY KEY (`reimbursement_comment_id`),
  KEY `fk_reimbursement_id` (`fk_reimbursement_claim_id`),
  CONSTRAINT `reimbursement_comment_ibfk_2` FOREIGN KEY (`fk_reimbursement_claim_id`) REFERENCES `reimbursement_claim` (`reimbursement_claim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `reimbursement_diagnosis_type`;
CREATE TABLE `reimbursement_diagnosis_type` (
  `reimbursement_diagnosis_type_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_diagnosis_type_name` varchar(100) NOT NULL,
  `reimbursement_diagnosis_type_is_active` tinyint NOT NULL DEFAULT '1',
  `reimbursement_diagnosis_type_created_date` date DEFAULT NULL,
  `reimbursement_diagnosis_type_created_by` int DEFAULT '1',
  `reimbursement_diagnosis_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reimbursement_diagnosis_type_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`reimbursement_diagnosis_type_id`),
  KEY `reimbursement_diagnosis_type_created_by` (`reimbursement_diagnosis_type_created_by`),
  KEY `reimbursement_diagnosis_type_last_modified_by` (`reimbursement_diagnosis_type_last_modified_by`),
  CONSTRAINT `reimbursement_diagnosis_type_ibfk_1` FOREIGN KEY (`reimbursement_diagnosis_type_created_by`) REFERENCES `user` (`user_id`),
  CONSTRAINT `reimbursement_diagnosis_type_ibfk_2` FOREIGN KEY (`reimbursement_diagnosis_type_last_modified_by`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `reimbursement_funding_type`;
CREATE TABLE `reimbursement_funding_type` (
  `reimbursement_funding_type_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_funding_type_name` varchar(100) NOT NULL,
  `reimbursement_funding_type_is_active` tinyint NOT NULL DEFAULT '1',
  `reimbursement_funding_type_created_date` date DEFAULT NULL,
  `reimbursement_funding_type_created_by` int DEFAULT '1',
  `reimbursement_funding_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reimbursement_funding_type_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`reimbursement_funding_type_id`),
  KEY `reimbursement_funding_type_created_by` (`reimbursement_funding_type_created_by`),
  KEY `reimbursement_funding_type_last_modified_by` (`reimbursement_funding_type_last_modified_by`),
  CONSTRAINT `reimbursement_funding_type_ibfk_1` FOREIGN KEY (`reimbursement_funding_type_created_by`) REFERENCES `user` (`user_id`),
  CONSTRAINT `reimbursement_funding_type_ibfk_2` FOREIGN KEY (`reimbursement_funding_type_last_modified_by`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `reimbursement_illiness_category`;
CREATE TABLE `reimbursement_illiness_category` (
  `reimbursement_illiness_category_id` int NOT NULL AUTO_INCREMENT,
  `reimbursement_illiness_category_name` varchar(100) NOT NULL,
  `reimbursement_illiness_category_is_active` tinyint NOT NULL DEFAULT '1',
  `fk_reimbursement_diagnosis_type_id` tinyint NOT NULL,
  `reimbursement_illiness_category_created_date` date DEFAULT NULL,
  `reimbursement_illiness_category_created_by` int DEFAULT NULL,
  `reimbursement_illiness_category_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reimbursement_illiness_category_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`reimbursement_illiness_category_id`),
  KEY `reimbursement_illiness_category_created_by` (`reimbursement_illiness_category_created_by`),
  KEY `reimbursement_illiness_category_last_modified_by` (`reimbursement_illiness_category_last_modified_by`),
  CONSTRAINT `reimbursement_illiness_category_ibfk_1` FOREIGN KEY (`reimbursement_illiness_category_created_by`) REFERENCES `user` (`user_id`),
  CONSTRAINT `reimbursement_illiness_category_ibfk_2` FOREIGN KEY (`reimbursement_illiness_category_last_modified_by`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `request`;
CREATE TABLE `request` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `request_track_number` varchar(100) DEFAULT NULL,
  `request_name` varchar(100) DEFAULT NULL,
  `fk_request_type_id` int DEFAULT '1',
  `fk_status_id` int DEFAULT '0',
  `fk_office_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `request_description` varchar(100) DEFAULT NULL,
  `fk_department_id` int NOT NULL,
  `request_is_fully_vouched` int NOT NULL DEFAULT '0',
  `request_created_date` date DEFAULT NULL,
  `request_created_by` varchar(45) DEFAULT NULL,
  `request_last_modified_by` varchar(45) DEFAULT NULL,
  `request_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request_deleted_at` date DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `fk_request_type_id` (`fk_request_type_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_department_id` (`fk_department_id`),
  CONSTRAINT `request_ibfk_1` FOREIGN KEY (`fk_request_type_id`) REFERENCES `request_type` (`request_type_id`),
  CONSTRAINT `request_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `request_ibfk_3` FOREIGN KEY (`fk_department_id`) REFERENCES `department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_conversion`;
CREATE TABLE `request_conversion` (
  `request_conversion_id` int NOT NULL AUTO_INCREMENT,
  `request_conversion_name` varchar(100) NOT NULL,
  `request_conversion_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `conversion_status_id` int NOT NULL,
  `request_conversion_created_date` date DEFAULT NULL,
  `request_conversion_created_by` int DEFAULT NULL,
  `request_conversion_last_modified_by` int DEFAULT NULL,
  `request_conversion_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`request_conversion_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `conversion_status_id` (`conversion_status_id`),
  CONSTRAINT `request_conversion_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `request_conversion_ibfk_2` FOREIGN KEY (`conversion_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_detail`;
CREATE TABLE `request_detail` (
  `request_detail_id` int NOT NULL AUTO_INCREMENT,
  `request_detail_track_number` varchar(100) DEFAULT NULL,
  `request_detail_name` varchar(100) DEFAULT NULL,
  `fk_request_id` int DEFAULT NULL,
  `request_detail_description` varchar(45) DEFAULT NULL,
  `request_detail_quantity` int DEFAULT NULL,
  `request_detail_unit_cost` decimal(10,2) DEFAULT NULL,
  `request_detail_total_cost` decimal(10,2) DEFAULT NULL,
  `fk_expense_account_id` int DEFAULT NULL,
  `fk_project_allocation_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `request_detail_conversion_set` int DEFAULT '0',
  `fk_voucher_id` int NOT NULL DEFAULT '0',
  `request_detail_created_date` date DEFAULT NULL,
  `request_detail_created_by` int DEFAULT NULL,
  `request_detail_last_modified_by` int DEFAULT NULL,
  `request_detail_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_detail_id`),
  KEY `fk_request_detail_request1_idx` (`fk_request_id`),
  KEY `fk_request_detail_expense_account1_idx` (`fk_expense_account_id`),
  CONSTRAINT `fk_request_detail_expense_account1` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`),
  CONSTRAINT `fk_request_detail_request1` FOREIGN KEY (`fk_request_id`) REFERENCES `request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_type`;
CREATE TABLE `request_type` (
  `request_type_id` int NOT NULL AUTO_INCREMENT,
  `request_type_track_number` varchar(100) NOT NULL,
  `request_type_name` varchar(100) NOT NULL,
  `request_type_is_active` int NOT NULL DEFAULT '1',
  `fk_account_system_id` int NOT NULL DEFAULT '1',
  `request_type_created_date` date DEFAULT NULL,
  `request_type_created_by` int DEFAULT NULL,
  `request_type_last_modified_by` int DEFAULT NULL,
  `request_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`request_type_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `request_type_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_type_department`;
CREATE TABLE `request_type_department` (
  `request_type_department_id` int NOT NULL AUTO_INCREMENT,
  `request_type_department_track_number` varchar(100) NOT NULL,
  `request_type_department_name` varchar(100) NOT NULL,
  `fk_request_type_id` int NOT NULL,
  `fk_department_id` int NOT NULL,
  `request_type_department_created_by` int NOT NULL,
  `request_type_department_created_date` date NOT NULL,
  `request_type_department_last_modified_by` int NOT NULL,
  `request_type_department_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  PRIMARY KEY (`request_type_department_id`),
  KEY `fk_request_type_id` (`fk_request_type_id`),
  KEY `fk_department_id` (`fk_department_id`),
  CONSTRAINT `request_type_department_ibfk_1` FOREIGN KEY (`fk_request_type_id`) REFERENCES `request_type` (`request_type_id`),
  CONSTRAINT `request_type_department_ibfk_2` FOREIGN KEY (`fk_department_id`) REFERENCES `department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_track_number` varchar(100) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `role_shortname` varchar(50) DEFAULT NULL,
  `role_description` longtext,
  `role_is_active` int DEFAULT '1',
  `role_is_new_status_default` int DEFAULT '0',
  `role_is_department_strict` int DEFAULT '0',
  `fk_context_definition_id` int DEFAULT '0',
  `fk_account_system_id` int DEFAULT NULL,
  `role_template_id` int DEFAULT NULL,
  `role_created_by` int DEFAULT NULL,
  `role_created_date` date DEFAULT NULL,
  `role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_last_modified_by` varchar(45) DEFAULT NULL,
  `role_deleted_at` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  CONSTRAINT `role_ibfk_3` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `role_ibfk_4` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_group`;
CREATE TABLE `role_group` (
  `role_group_id` int NOT NULL AUTO_INCREMENT,
  `role_group_name` varchar(100) NOT NULL,
  `role_group_track_number` varchar(100) NOT NULL,
  `role_group_description` longtext NOT NULL,
  `role_group_is_active` int NOT NULL DEFAULT '1',
  `fk_account_system_id` int NOT NULL,
  `role_group_created_date` date NOT NULL,
  `role_group_created_by` int NOT NULL,
  `role_group_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_group_last_modified_by` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int NOT NULL,
  `fk_context_definition_id` int DEFAULT NULL,
  PRIMARY KEY (`role_group_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `role_group_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_group_association`;
CREATE TABLE `role_group_association` (
  `role_group_association_id` int NOT NULL AUTO_INCREMENT,
  `role_group_association_name` varchar(100) NOT NULL,
  `role_group_association_track_number` varchar(100) NOT NULL,
  `fk_role_group_id` int NOT NULL,
  `fk_role_id` int NOT NULL,
  `role_group_association_is_active` int DEFAULT '1',
  `role_group_association_created_date` date DEFAULT NULL,
  `role_group_association_created_by` int DEFAULT NULL,
  `role_group_association_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role_group_association_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`role_group_association_id`),
  KEY `fk_role_group_id` (`fk_role_group_id`),
  KEY `fk_role_id` (`fk_role_id`),
  CONSTRAINT `role_group_association_ibfk_1` FOREIGN KEY (`fk_role_group_id`) REFERENCES `role_group` (`role_group_id`),
  CONSTRAINT `role_group_association_ibfk_3` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE `role_permission` (
  `role_permission_id` int NOT NULL AUTO_INCREMENT,
  `role_permission_track_number` varchar(100) NOT NULL,
  `role_permission_name` varchar(100) NOT NULL,
  `role_permission_is_active` int NOT NULL DEFAULT '1',
  `fk_role_id` int NOT NULL,
  `fk_permission_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `role_permission_created_date` date DEFAULT NULL,
  `role_permission_created_by` int DEFAULT NULL,
  `role_permission_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role_permission_last_modified_by` int DEFAULT NULL,
  PRIMARY KEY (`role_permission_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_permission_id` (`fk_permission_id`),
  CONSTRAINT `role_permission_ibfk_6` FOREIGN KEY (`fk_permission_id`) REFERENCES `permission` (`permission_id`),
  CONSTRAINT `role_permission_ibfk_8` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `role_user_id` int NOT NULL AUTO_INCREMENT,
  `role_user_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role_user_name` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `fk_role_id` int NOT NULL,
  `fk_user_id` int NOT NULL,
  `role_user_is_active` int NOT NULL DEFAULT '1',
  `role_user_expiry_date` date DEFAULT NULL,
  `role_user_created_date` date NOT NULL,
  `role_user_created_by` int NOT NULL,
  `role_user_last_modified_by` int NOT NULL,
  `role_user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`role_user_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `role_user_ibfk_1` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`),
  CONSTRAINT `role_user_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `scheduled_task`;
CREATE TABLE `scheduled_task` (
  `scheduled_task_id` int NOT NULL AUTO_INCREMENT,
  `scheduled_task_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_track_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_minute` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_hour` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_day_of_month` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_month` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_day_of_week` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_task_is_active` int NOT NULL DEFAULT '1',
  `scheduled_task_last_run` datetime DEFAULT NULL,
  `scheduled_task_next_run` datetime DEFAULT NULL,
  `scheduled_task_created_date` date DEFAULT NULL,
  `scheduled_task_created_by` int DEFAULT NULL,
  `scheduled_task_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scheduled_task_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`scheduled_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `setting_created_date` date DEFAULT NULL,
  `setting_created_by` int DEFAULT NULL,
  `setting_last_modified_by` int DEFAULT NULL,
  `setting_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `type` varchar(31) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'string',
  `context` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP VIEW IF EXISTS `stale_cheques`;
CREATE TABLE `stale_cheques` (`office_id` int, `office_code` varchar(45), `office_name` longtext, `voucher_cheque_number` varchar(50), `voucher_date` date, `voucher_type_effect_code` varchar(50), `voucher_cleared` int, `amount` decimal(65,2));


DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int NOT NULL AUTO_INCREMENT,
  `status_track_number` varchar(100) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `status_button_label` varchar(100) DEFAULT NULL,
  `status_decline_button_label` varchar(100) DEFAULT NULL,
  `status_signatory_label` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fk_approval_flow_id` int NOT NULL,
  `status_approval_sequence` int NOT NULL,
  `status_backflow_sequence` int NOT NULL,
  `status_approval_direction` int NOT NULL COMMENT '1-straight jumps, 0 - return jumps, -1 - reverse jump',
  `status_is_requiring_approver_action` int NOT NULL DEFAULT '1',
  `status_created_date` date NOT NULL,
  `status_created_by` int NOT NULL,
  `status_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_last_modified_by` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `status_deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  KEY `fk_approval_flow_id` (`fk_approval_flow_id`),
  CONSTRAINT `status_ibfk_5` FOREIGN KEY (`fk_approval_flow_id`) REFERENCES `approval_flow` (`approval_flow_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `status_role`;
CREATE TABLE `status_role` (
  `status_role_id` int NOT NULL AUTO_INCREMENT,
  `status_role_track_number` varchar(100) NOT NULL,
  `status_role_name` varchar(100) NOT NULL,
  `fk_role_id` int NOT NULL,
  `fk_status_id` int NOT NULL,
  `status_role_status_id` int NOT NULL,
  `status_role_is_active` int NOT NULL DEFAULT '1',
  `status_role_created_by` int NOT NULL,
  `status_role_created_date` date NOT NULL,
  `status_role_last_modified_by` int NOT NULL,
  `status_role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`status_role_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `status_role_status_id` (`status_role_status_id`),
  CONSTRAINT `status_role_ibfk_4` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `status_role_ibfk_6` FOREIGN KEY (`status_role_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `strategic_objectives`;
CREATE TABLE `strategic_objectives` (
  `strategic_objectives_id` int NOT NULL AUTO_INCREMENT,
  `strategic_objectives_name` varchar(100) DEFAULT NULL,
  `strategic_objectives_track_number` varchar(100) DEFAULT NULL,
  `strategic_objectives_created_date` date DEFAULT NULL,
  `strategic_objectives_created_by` int DEFAULT NULL,
  `strategic_objectives_last_modified_by` int DEFAULT NULL,
  `strategic_objectives_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`strategic_objectives_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP VIEW IF EXISTS `sum_income_and_expense_per_project`;
CREATE TABLE `sum_income_and_expense_per_project` (`voucher_month` date, `account_system_id` int, `office_id` int, `office_code` varchar(45), `income_account_id` int, `income_account_code` varchar(10), `expense_account_id` int, `project_id` int, `office_bank_id` int, `amount` decimal(65,2));


DROP TABLE IF EXISTS `system_opening_balance`;
CREATE TABLE `system_opening_balance` (
  `system_opening_balance_id` int NOT NULL AUTO_INCREMENT,
  `system_opening_balance_track_number` varchar(100) NOT NULL,
  `system_opening_balance_name` longtext NOT NULL,
  `fk_office_id` int NOT NULL,
  `month` date NOT NULL,
  `system_opening_balance_created_date` date DEFAULT NULL,
  `system_opening_balance_created_by` int DEFAULT NULL,
  `system_opening_balance_last_modified_by` int DEFAULT NULL,
  `system_opening_balance_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`system_opening_balance_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `system_opening_balance_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `systems`;
CREATE TABLE `systems` (
  `systems_id` bigint NOT NULL AUTO_INCREMENT,
  `systems_created_date` date DEFAULT NULL,
  `systems_created_by` int DEFAULT NULL,
  `systems_last_modified_by` int DEFAULT NULL,
  `systems_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`systems_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `translation`;
CREATE TABLE `translation` (
  `translation_id` int NOT NULL AUTO_INCREMENT,
  `language_phrase_id` int DEFAULT NULL,
  `language_id` int DEFAULT NULL,
  `translate` longtext,
  `created_date` date DEFAULT NULL,
  `last_modified_date` date DEFAULT NULL,
  `deleted_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `last_modified_by` int DEFAULT NULL,
  `translation_created_date` date DEFAULT NULL,
  `translation_created_by` int DEFAULT NULL,
  `translation_last_modified_by` int DEFAULT NULL,
  `translation_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`translation_id`),
  KEY `fk_translation_language1_idx` (`language_id`),
  KEY `fk_translation_language_phrase1_idx` (`language_phrase_id`),
  CONSTRAINT `fk_translation_language1` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`),
  CONSTRAINT `fk_translation_language_phrase1` FOREIGN KEY (`language_phrase_id`) REFERENCES `language_phrase` (`language_phrase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `unique_identifier`;
CREATE TABLE `unique_identifier` (
  `unique_identifier_id` int NOT NULL AUTO_INCREMENT,
  `unique_identifier_name` varchar(100) NOT NULL,
  `unique_identifier_track_number` varchar(50) NOT NULL,
  `unique_identifier_is_active` int NOT NULL DEFAULT '1',
  `fk_account_system_id` int NOT NULL,
  `unique_identifier_created_date` date NOT NULL,
  `unique_identifier_created_by` int NOT NULL,
  `unique_identifier_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unique_identifier_last_modified_by` int NOT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `unique_identifier_deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`unique_identifier_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `unique_identifier_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `untranslated_phrase`;
CREATE TABLE `untranslated_phrase` (
  `untranslated_phrase_id` int NOT NULL AUTO_INCREMENT,
  `fk_language_id` int NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `phrase` varchar(200) NOT NULL,
  `phrase_translation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `untranslated_phrase_created_date` datetime DEFAULT NULL,
  `untranslated_phrase_created_by` int DEFAULT NULL,
  `untranslated_phrase_last_modified_by` int DEFAULT NULL,
  `untranslated_phrase_last_modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`untranslated_phrase_id`),
  KEY `language_id` (`fk_language_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `untranslated_phrase_ibfk_1` FOREIGN KEY (`fk_language_id`) REFERENCES `language` (`language_id`),
  CONSTRAINT `untranslated_phrase_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_track_number` varchar(100) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_firstname` varchar(100) NOT NULL,
  `user_lastname` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `fk_context_definition_id` int NOT NULL,
  `user_is_context_manager` int NOT NULL DEFAULT '0',
  `user_is_system_admin` int NOT NULL DEFAULT '0',
  `fk_language_id` int DEFAULT NULL COMMENT 'User''s default language',
  `fk_country_currency_id` int DEFAULT NULL,
  `user_is_active` int NOT NULL DEFAULT '1',
  `fk_role_id` int DEFAULT NULL,
  `fk_account_system_id` int DEFAULT NULL,
  `user_password` varchar(100) NOT NULL,
  `user_first_time_login` smallint NOT NULL DEFAULT '0',
  `user_last_login_time` timestamp NULL DEFAULT NULL,
  `user_access_count` int NOT NULL DEFAULT '0',
  `md5_migrate` smallint NOT NULL DEFAULT '0',
  `user_employment_date` date DEFAULT NULL,
  `user_unique_identifier` varchar(50) DEFAULT NULL,
  `fk_unique_identifier_id` int DEFAULT NULL,
  `user_personal_data_consent_date` date DEFAULT NULL,
  `user_personal_data_consent_content` longtext,
  `user_is_switchable` int NOT NULL DEFAULT '1',
  `user_created_date` date NOT NULL,
  `user_created_by` int DEFAULT NULL,
  `user_self_created` int DEFAULT '0' COMMENT '1=account was added by user himself or herself',
  `user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_last_modifed_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `user_last_modified_by` int DEFAULT NULL,
  `user_approvers` json DEFAULT NULL,
  `user_password_reset_token` json DEFAULT NULL,
  `user_deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_unique_identifier` (`user_unique_identifier`,`fk_unique_identifier_id`,`fk_account_system_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_language_id` (`fk_language_id`),
  KEY `fk_country_currency_id` (`fk_country_currency_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  FULLTEXT KEY `search_text` (`user_firstname`,`user_lastname`,`user_email`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`),
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `user_ibfk_4` FOREIGN KEY (`fk_country_currency_id`) REFERENCES `country_currency` (`country_currency_id`),
  CONSTRAINT `user_ibfk_5` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `user_ibfk_6` FOREIGN KEY (`fk_language_id`) REFERENCES `language` (`language_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user_account_activation`;
CREATE TABLE `user_account_activation` (
  `user_account_activation_id` int NOT NULL AUTO_INCREMENT,
  `user_account_activation_name` varchar(50) NOT NULL,
  `user_account_activation_track_number` varchar(50) NOT NULL,
  `fk_user_id` int NOT NULL,
  `user_type` int NOT NULL DEFAULT '1' COMMENT 'i.e. FCP staff; PF Staff, Mop Staff; Country Admin; Other National Office Staff',
  `user_works_for` varchar(100) DEFAULT NULL,
  `user_activator_ids` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Store the office ID e.g. cluster if FCP or Country if PF or Mob user account is created',
  `user_account_activation_created_date` date DEFAULT NULL,
  `user_account_activation_modified_date` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `user_account_activation_created_by` int NOT NULL DEFAULT '0',
  `user_reporting_context_id` int DEFAULT NULL,
  `deleted_at` date DEFAULT NULL,
  `user_account_activation_reject_reason` longtext,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `user_account_activation_last_modified_by` int DEFAULT NULL,
  PRIMARY KEY (`user_account_activation_id`),
  KEY `fk_user_id` (`fk_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;


DROP TABLE IF EXISTS `variance_comment`;
CREATE TABLE `variance_comment` (
  `variance_comment_id` int NOT NULL AUTO_INCREMENT,
  `variance_comment_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `variance_comment_track_number` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `fk_budget_id` int NOT NULL,
  `fk_financial_report_id` int NOT NULL,
  `fk_expense_account_id` int NOT NULL,
  `variance_comment_text` longtext CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `variance_comment_created_date` date DEFAULT NULL,
  `variance_comment_created_by` int DEFAULT NULL,
  `variance_comment_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `variance_comment_last_modified_by` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`variance_comment_id`),
  KEY `fk_budget_id` (`fk_budget_id`),
  KEY `fk_expense_account_id` (`fk_expense_account_id`),
  KEY `fk_financial_report_id` (`fk_financial_report_id`),
  CONSTRAINT `variance_comment_ibfk_1` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`),
  CONSTRAINT `variance_comment_ibfk_2` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`),
  CONSTRAINT `variance_comment_ibfk_4` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `variance_note`;
CREATE TABLE `variance_note` (
  `variance_note_id` int NOT NULL AUTO_INCREMENT,
  `reconciliation_id` int DEFAULT NULL,
  `expense_account_id` int DEFAULT NULL,
  `created_by` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  `last_modified_date` varchar(45) DEFAULT NULL,
  `last_modified_by` varchar(45) DEFAULT NULL,
  `variance_note_detail` longtext,
  `variance_note_created_date` date DEFAULT NULL,
  `variance_note_created_by` int DEFAULT NULL,
  `variance_note_last_modified_by` int DEFAULT NULL,
  `variance_note_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`variance_note_id`),
  KEY `fk_variance_comment_reconciliation1_idx` (`reconciliation_id`),
  KEY `fk_variance_comment_expense_account1_idx` (`expense_account_id`),
  CONSTRAINT `fk_variance_comment_expense_account1` FOREIGN KEY (`expense_account_id`) REFERENCES `expense_account` (`expense_account_id`),
  CONSTRAINT `fk_variance_comment_reconciliation1` FOREIGN KEY (`reconciliation_id`) REFERENCES `reconciliation` (`reconciliation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher`;
CREATE TABLE `voucher` (
  `voucher_id` int NOT NULL AUTO_INCREMENT,
  `voucher_track_number` varchar(50) DEFAULT NULL,
  `voucher_name` longtext,
  `voucher_number` int DEFAULT NULL,
  `fk_office_id` int NOT NULL,
  `voucher_account_system_code` varchar(5) DEFAULT NULL,
  `voucher_date` date DEFAULT NULL,
  `fk_voucher_type_id` int DEFAULT NULL,
  `fk_cheque_book_id` int DEFAULT NULL,
  `voucher_cleared` int DEFAULT '0',
  `voucher_cleared_month` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  `fk_office_bank_id` int DEFAULT NULL,
  `fk_office_cash_id` int DEFAULT NULL,
  `voucher_cheque_number` varchar(50) DEFAULT NULL,
  `voucher_transaction_cleared_date` date DEFAULT NULL,
  `voucher_transaction_cleared_month` date DEFAULT NULL,
  `voucher_vendor` longtext,
  `voucher_vendor_address` longtext,
  `voucher_description` longtext,
  `voucher_allow_edit` int DEFAULT '0',
  `voucher_is_reversed` int DEFAULT '0',
  `voucher_reversal_from` int NOT NULL DEFAULT '0',
  `voucher_reversal_to` int NOT NULL DEFAULT '0',
  `voucher_cleared_from` int NOT NULL DEFAULT '0',
  `voucher_cleared_to` int NOT NULL DEFAULT '0',
  `voucher_refunding_to` json DEFAULT NULL,
  `voucher_created_by` int DEFAULT NULL,
  `voucher_created_date` date DEFAULT NULL,
  `voucher_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `voucher_last_modified_by` int DEFAULT NULL,
  `voucher_approvers` json DEFAULT NULL,
  `voucher_details` json DEFAULT NULL,
  `voucher_revisions` json DEFAULT NULL,
  `voucher_uploads` json DEFAULT NULL,
  PRIMARY KEY (`voucher_id`),
  KEY `fk_voucher_type_id` (`fk_voucher_type_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `voucher_reversal_to` (`voucher_reversal_to`),
  KEY `voucher_reversal_from` (`voucher_reversal_from`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `voucher_date` (`voucher_date`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_cheque_book_id` (`fk_cheque_book_id`),
  KEY `voucher_cleared_to` (`voucher_cleared_to`),
  KEY `voucher_cleared_from` (`voucher_cleared_from`),
  CONSTRAINT `voucher_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `voucher_ibfk_2` FOREIGN KEY (`fk_voucher_type_id`) REFERENCES `voucher_type` (`voucher_type_id`),
  CONSTRAINT `voucher_ibfk_3` FOREIGN KEY (`fk_cheque_book_id`) REFERENCES `cheque_book` (`cheque_book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This holds transactions ';


DELIMITER ;;

CREATE TRIGGER `update_voucher_number` BEFORE INSERT ON `voucher` FOR EACH ROW
BEGIN 
    SET NEW.voucher_number = ( 
        SELECT CONCAT( 
            DATE_FORMAT(NEW.voucher_date, '%y%m'), 
            LPAD( 
                (SELECT COUNT(*) + 1  
                FROM voucher  
                WHERE fk_office_id = NEW.fk_office_id  
                AND DATE_FORMAT(voucher_date, '%y%m') = DATE_FORMAT(NEW.voucher_date, '%y%m') 
                ), 3, '0' 
            ) 
        ) 
    ); 
END;;

CREATE TRIGGER `before_voucher_update` BEFORE UPDATE ON `voucher` FOR EACH ROW
BEGIN
    -- Insert the old data as JSON into the audit table
    INSERT INTO audit (origin_table,record_id, original_data, updated_data, action_taken, created_at)
    VALUES (
        'voucher', 
        OLD.voucher_id,
        JSON_OBJECT(
            'voucher_id', OLD.voucher_id,
            'voucher_track_number', OLD.voucher_track_number,
            'voucher_name', OLD.voucher_name,
            'voucher_number', OLD.voucher_number,
            'fk_office_id', OLD.fk_office_id,
            'voucher_date', OLD.voucher_date,
            'fk_voucher_type_id', OLD.fk_voucher_type_id,
            'fk_cheque_book_id', OLD.fk_cheque_book_id,
            'voucher_cleared', OLD.voucher_cleared,
            'voucher_cleared_month', OLD.voucher_cleared_month,
            'fk_approval_id', OLD.fk_approval_id,
            'fk_status_id', OLD.fk_status_id,
            'fk_office_bank_id', OLD.fk_office_bank_id,
            'fk_office_cash_id', OLD.fk_office_cash_id,
            'voucher_cheque_number', OLD.voucher_cheque_number,
            'voucher_transaction_cleared_date', OLD.voucher_transaction_cleared_date,
            'voucher_transaction_cleared_month', OLD.voucher_transaction_cleared_month,
            'voucher_vendor', OLD.voucher_vendor,
            'voucher_vendor_address', OLD.voucher_vendor_address,
            'voucher_description', OLD.voucher_description,
            'voucher_allow_edit', OLD.voucher_allow_edit,
            'voucher_is_reversed', OLD.voucher_is_reversed,
            'voucher_reversal_from', OLD.voucher_reversal_from,
            'voucher_reversal_to', OLD.voucher_reversal_to,
            'voucher_refunding_to', OLD.voucher_refunding_to,
            'voucher_created_by', OLD.voucher_created_by,
            'voucher_created_date', OLD.voucher_created_date,
            'voucher_last_modified_date', OLD.voucher_last_modified_date,
            'voucher_last_modified_by', OLD.voucher_last_modified_by
        ),
       JSON_OBJECT(
            'voucher_id', NEW.voucher_id,
            'voucher_track_number', NEW.voucher_track_number,
            'voucher_name', NEW.voucher_name,
            'voucher_number', NEW.voucher_number,
            'fk_office_id', NEW.fk_office_id,
            'voucher_date', NEW.voucher_date,
            'fk_voucher_type_id', NEW.fk_voucher_type_id,
            'fk_cheque_book_id', NEW.fk_cheque_book_id,
            'voucher_cleared', NEW.voucher_cleared,
            'voucher_cleared_month', NEW.voucher_cleared_month,
            'fk_approval_id', NEW.fk_approval_id,
            'fk_status_id', NEW.fk_status_id,
            'fk_office_bank_id', NEW.fk_office_bank_id,
            'fk_office_cash_id', NEW.fk_office_cash_id,
            'voucher_cheque_number', NEW.voucher_cheque_number,
            'voucher_transaction_cleared_date', NEW.voucher_transaction_cleared_date,
            'voucher_transaction_cleared_month', NEW.voucher_transaction_cleared_month,
            'voucher_vendor', NEW.voucher_vendor,
            'voucher_vendor_address', NEW.voucher_vendor_address,
            'voucher_description', NEW.voucher_description,
            'voucher_allow_edit', NEW.voucher_allow_edit,
            'voucher_is_reversed', NEW.voucher_is_reversed,
            'voucher_reversal_from', NEW.voucher_reversal_from,
            'voucher_reversal_to', NEW.voucher_reversal_to,
            'voucher_refunding_to', NEW.voucher_refunding_to,
            'voucher_created_by', NEW.voucher_created_by,
            'voucher_created_date', NEW.voucher_created_date,
            'voucher_last_modified_date', NEW.voucher_last_modified_date,
            'voucher_last_modified_by', NEW.voucher_last_modified_by
        ),
        'BEFORE UPDATE',
        NOW()
    );
END;;

DELIMITER ;

DROP TABLE IF EXISTS `voucher_detail`;
CREATE TABLE `voucher_detail` (
  `voucher_detail_id` int NOT NULL AUTO_INCREMENT,
  `voucher_detail_track_number` varchar(100) DEFAULT NULL,
  `voucher_detail_name` longtext,
  `fk_voucher_id` int DEFAULT NULL,
  `voucher_detail_description` longtext,
  `voucher_detail_quantity` int DEFAULT NULL,
  `voucher_detail_unit_cost` decimal(50,2) DEFAULT NULL,
  `voucher_detail_total_cost` decimal(50,2) DEFAULT NULL,
  `voucher_detail_total_cost_usd` decimal(50,2) DEFAULT NULL,
  `fk_expense_account_id` int NOT NULL DEFAULT '0' COMMENT 'Can be income_account_id or expense_account_id depending on the selected voucher type',
  `fk_income_account_id` int NOT NULL DEFAULT '0',
  `fk_contra_account_id` int NOT NULL DEFAULT '0',
  `fk_approval_id` int NOT NULL DEFAULT '0',
  `fk_status_id` int NOT NULL DEFAULT '0',
  `fk_request_detail_id` int NOT NULL DEFAULT '0',
  `fk_project_allocation_id` int NOT NULL DEFAULT '0',
  `voucher_detail_last_modified_date` date DEFAULT NULL,
  `voucher_detail_last_modified_by` varchar(45) DEFAULT NULL,
  `voucher_detail_created_by` int DEFAULT NULL,
  `voucher_detail_created_date` date DEFAULT NULL,
  PRIMARY KEY (`voucher_detail_id`),
  KEY `fk_voucher_detail_voucher1_idx` (`fk_voucher_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `voucher_detail_ibfk_5` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `voucher_detail_ibfk_6` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DELIMITER ;;

CREATE TRIGGER `prevent_invalid_voucher_detail_insert` BEFORE INSERT ON `voucher_detail` FOR EACH ROW
BEGIN
    IF NEW.fk_income_account_id = 0 
       AND NEW.fk_expense_account_id = 0 
       AND NEW.fk_contra_account_id = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insert not allowed: All account IDs cannot be 0.';
    END IF;
END;;

CREATE TRIGGER `before_voucher_detail_update` BEFORE UPDATE ON `voucher_detail` FOR EACH ROW
BEGIN
    -- Insert the old data as JSON into the audit table
    INSERT INTO audit (origin_table,record_id, original_data, updated_data, action_taken, created_at)
    VALUES (
        'voucher_detail', 
        OLD.voucher_detail_id,
        JSON_OBJECT(
            'voucher_detail_id', OLD.voucher_detail_id,
            'voucher_detail_track_number', OLD.voucher_detail_track_number,
            'fk_voucher_id', OLD.fk_voucher_id,
            'voucher_detail_description', OLD.voucher_detail_description,
            'voucher_detail_quantity', OLD.voucher_detail_quantity,
            'voucher_detail_unit_cost', OLD.voucher_detail_unit_cost,
            'voucher_detail_total_cost', OLD.voucher_detail_total_cost,
            'fk_expense_account_id', OLD.fk_expense_account_id,
            'fk_income_account_id', OLD.fk_income_account_id,
            'fk_contra_account_id', OLD.fk_contra_account_id,
            'fk_approval_id', OLD.fk_approval_id,
            'fk_status_id', OLD.fk_status_id,
            'fk_request_detail_id', OLD.fk_request_detail_id,
            'fk_project_allocation_id', OLD.fk_project_allocation_id,
            'voucher_detail_last_modified_date', OLD.voucher_detail_last_modified_date,
            'voucher_detail_last_modified_by', OLD.voucher_detail_last_modified_by,
            'voucher_detail_created_by', OLD.voucher_detail_created_by,
            'voucher_detail_created_date', OLD.voucher_detail_created_date),
            JSON_OBJECT(
            'voucher_detail_id', NEW.voucher_detail_id,
            'voucher_detail_track_number', NEW.voucher_detail_track_number,
            'fk_voucher_id', NEW.fk_voucher_id,
            'voucher_detail_description', NEW.voucher_detail_description,
            'voucher_detail_quantity', NEW.voucher_detail_quantity,
            'voucher_detail_unit_cost', NEW.voucher_detail_unit_cost,
            'voucher_detail_total_cost', NEW.voucher_detail_total_cost,
            'fk_expense_account_id', NEW.fk_expense_account_id,
            'fk_income_account_id', NEW.fk_income_account_id,
            'fk_contra_account_id', NEW.fk_contra_account_id,
            'fk_approval_id', NEW.fk_approval_id,
            'fk_status_id', NEW.fk_status_id,
            'fk_request_detail_id', NEW.fk_request_detail_id,
            'fk_project_allocation_id', NEW.fk_project_allocation_id,
            'voucher_detail_last_modified_date', NEW.voucher_detail_last_modified_date,
            'voucher_detail_last_modified_by', NEW.voucher_detail_last_modified_by,
            'voucher_detail_created_by', NEW.voucher_detail_created_by,
            'voucher_detail_created_date', NEW.voucher_detail_created_date),
        'BEFORE UPDATE',
        NOW()
    );
END;;

DELIMITER ;

DROP TABLE IF EXISTS `voucher_signatory`;
CREATE TABLE `voucher_signatory` (
  `voucher_signatory_id` int NOT NULL AUTO_INCREMENT,
  `voucher_signatory_name` varchar(100) NOT NULL,
  `voucher_signatory_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int NOT NULL,
  `voucher_signatory_is_active` int NOT NULL DEFAULT '1',
  `voucher_signatory_created_date` date DEFAULT NULL,
  `voucher_signatory_created_by` int DEFAULT NULL,
  `voucher_signatory_last_modified_by` int DEFAULT NULL,
  `voucher_signatory_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`voucher_signatory_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `voucher_signatory_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type`;
CREATE TABLE `voucher_type` (
  `voucher_type_id` int NOT NULL AUTO_INCREMENT,
  `voucher_type_track_number` varchar(100) NOT NULL,
  `voucher_type_name` varchar(45) DEFAULT NULL,
  `voucher_type_is_active` int DEFAULT NULL,
  `voucher_type_abbrev` varchar(5) DEFAULT NULL,
  `fk_voucher_type_account_id` int DEFAULT NULL COMMENT 'Can be bank, cash or contra',
  `fk_voucher_type_effect_id` int DEFAULT NULL COMMENT 'Can be income or expense',
  `voucher_type_is_cheque_referenced` int DEFAULT '0',
  `voucher_type_is_hidden` int DEFAULT '0',
  `fk_account_system_id` int DEFAULT NULL,
  `voucher_type_expense_accounts` json DEFAULT NULL,
  `voucher_type_created_by` int DEFAULT NULL,
  `voucher_type_created_date` date DEFAULT NULL,
  `voucher_type_last_modified_by` int DEFAULT NULL,
  `voucher_type_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`voucher_type_id`),
  KEY `fk_voucher_type_voucher_type_transaction_effect1_idx` (`fk_voucher_type_effect_id`),
  KEY `voucher_type_account_id` (`fk_voucher_type_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `voucher_type_ibfk_1` FOREIGN KEY (`fk_voucher_type_account_id`) REFERENCES `voucher_type_account` (`voucher_type_account_id`),
  CONSTRAINT `voucher_type_ibfk_2` FOREIGN KEY (`fk_voucher_type_effect_id`) REFERENCES `voucher_type_effect` (`voucher_type_effect_id`),
  CONSTRAINT `voucher_type_ibfk_4` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type_account`;
CREATE TABLE `voucher_type_account` (
  `voucher_type_account_id` int NOT NULL AUTO_INCREMENT,
  `voucher_type_account_track_number` varchar(100) NOT NULL,
  `voucher_type_account_name` varchar(100) NOT NULL,
  `voucher_type_account_code` varchar(10) NOT NULL COMMENT 'cash or bank',
  `voucher_type_account_created_date` date DEFAULT NULL,
  `voucher_type_account_created_by` int DEFAULT NULL,
  `voucher_type_account_last_modified_by` int DEFAULT NULL,
  `voucher_type_account_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`voucher_type_account_id`),
  UNIQUE KEY `voucher_type_account_code` (`voucher_type_account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type_effect`;
CREATE TABLE `voucher_type_effect` (
  `voucher_type_effect_id` int NOT NULL AUTO_INCREMENT,
  `voucher_type_effect_track_number` varchar(100) NOT NULL,
  `voucher_type_effect_name` varchar(100) NOT NULL,
  `voucher_type_effect_code` varchar(50) NOT NULL,
  `voucher_type_effect_created_date` date DEFAULT NULL,
  `voucher_type_effect_created_by` int DEFAULT NULL,
  `voucher_type_effect_last_modified_by` int DEFAULT NULL,
  `voucher_type_effect_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`voucher_type_effect_id`),
  UNIQUE KEY `voucher_type_effect_code` (`voucher_type_effect_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `workplan`;
CREATE TABLE `workplan` (
  `workplan_id` int NOT NULL AUTO_INCREMENT,
  `workplan_track_number` varchar(100) DEFAULT NULL,
  `workplan_name` varchar(100) DEFAULT NULL,
  `fk_budget_id` int DEFAULT NULL,
  `workplan_description` longtext,
  `workplan_start_date` date DEFAULT NULL,
  `workplan_end_date` date DEFAULT NULL,
  `workplan_created_date` date DEFAULT NULL,
  `workplan_created_by` int DEFAULT NULL,
  `workplan_last_modified_date` date DEFAULT NULL,
  `workplan_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`workplan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `workplan_task`;
CREATE TABLE `workplan_task` (
  `workplan_task_id` int NOT NULL AUTO_INCREMENT,
  `fk_workplan_id` int NOT NULL,
  `workplan_task_track_number` varchar(100) NOT NULL,
  `workplan_task_name` varchar(100) NOT NULL,
  `workplan_task_description` longtext NOT NULL,
  `workplan_task_start_date` date NOT NULL,
  `workplan_taskend_date` date NOT NULL,
  `workplan_task_user` int NOT NULL,
  `workplan_task_status` int NOT NULL DEFAULT '1',
  `workplan_task_note` longtext NOT NULL,
  `workplan_task_created_date` date DEFAULT NULL,
  `workplan_task_created_by` int DEFAULT NULL,
  `workplan_task_last_modified_by` int DEFAULT NULL,
  `workplan_task_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`workplan_task_id`),
  KEY `fk_workplan_id` (`fk_workplan_id`),
  KEY `workplan_detail_task_user` (`workplan_task_user`),
  CONSTRAINT `workplan_task_ibfk_1` FOREIGN KEY (`fk_workplan_id`) REFERENCES `workplan` (`workplan_id`),
  CONSTRAINT `workplan_task_ibfk_2` FOREIGN KEY (`workplan_task_user`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `bank_to_bank_contra_contributions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `bank_to_bank_contra_contributions` AS select `v`.`fk_office_id` AS `office_id`,`v`.`fk_office_bank_id` AS `office_bank_id`,`v`.`voucher_date` AS `voucher_date`,`v`.`voucher_number` AS `voucher_number`,`vd`.`fk_income_account_id` AS `income_account_id`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost` from ((((`voucher_detail` `vd` join `voucher` `v` on((`vd`.`fk_voucher_id` = `v`.`voucher_id`))) join `voucher_type` `vt` on((`v`.`fk_voucher_type_id` = `vt`.`voucher_type_id`))) join `voucher_type_account` `vta` on((`vt`.`fk_voucher_type_account_id` = `vta`.`voucher_type_account_id`))) join `voucher_type_effect` `vte` on((`vt`.`fk_voucher_type_effect_id` = `vte`.`voucher_type_effect_id`))) where ((`vta`.`voucher_type_account_code` = 'bank') and (`vte`.`voucher_type_effect_code` = 'bank_to_bank_contra')) group by `v`.`fk_office_id`,`v`.`fk_office_bank_id`,`v`.`voucher_date`,`v`.`voucher_number`,`vd`.`fk_income_account_id`;

DROP TABLE IF EXISTS `bank_to_bank_contra_receipts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `bank_to_bank_contra_receipts` AS select `v`.`fk_office_id` AS `office_id`,`cra`.`fk_office_bank_id` AS `office_bank_id`,`v`.`voucher_date` AS `voucher_date`,`v`.`voucher_number` AS `voucher_number`,`vd`.`fk_income_account_id` AS `income_account_id`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost` from (((((`voucher_detail` `vd` join `voucher` `v` on((`vd`.`fk_voucher_id` = `v`.`voucher_id`))) join `cash_recipient_account` `cra` on((`v`.`voucher_id` = `cra`.`fk_voucher_id`))) join `voucher_type` `vt` on((`v`.`fk_voucher_type_id` = `vt`.`voucher_type_id`))) join `voucher_type_account` `vta` on((`vt`.`fk_voucher_type_account_id` = `vta`.`voucher_type_account_id`))) join `voucher_type_effect` `vte` on((`vt`.`fk_voucher_type_effect_id` = `vte`.`voucher_type_effect_id`))) where ((`vta`.`voucher_type_account_code` = 'bank') and (`vte`.`voucher_type_effect_code` = 'bank_to_bank_contra')) group by `v`.`fk_office_id`,`cra`.`fk_office_bank_id`,`v`.`voucher_date`,`v`.`voucher_number`,`vd`.`fk_income_account_id`;

DROP TABLE IF EXISTS `financial_report_uploaded_statements`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `financial_report_uploaded_statements` AS select `financial_report`.`financial_report_id` AS `fk_financial_report_id`,count(0) AS `count_of_uploaded_statements` from ((`attachment` join `reconciliation` on((`attachment`.`attachment_primary_id` = `reconciliation`.`reconciliation_id`))) join `financial_report` on((`reconciliation`.`fk_financial_report_id` = `financial_report`.`financial_report_id`))) where ((`attachment`.`fk_approve_item_id` = 66) and (`attachment`.`fk_attachment_type_id` = 3)) group by `attachment`.`attachment_primary_id`;

DROP TABLE IF EXISTS `gift_expense_accounts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `gift_expense_accounts` AS select `ea`.`expense_account_id` AS `expense_account_id`,`ea`.`expense_account_name` AS `expense_account_name`,`ea`.`expense_account_code` AS `expense_account_code`,`ivc`.`income_vote_heads_category_name` AS `category_name`,`fs`.`funding_stream_name` AS `funding_stream_name`,`fs`.`funding_stream_code` AS `funding_stream_code`,`ia`.`fk_account_system_id` AS `account_system_id` from (((`expense_account` `ea` join `income_account` `ia` on((`ea`.`fk_income_account_id` = `ia`.`income_account_id`))) join `income_vote_heads_category` `ivc` on((`ia`.`fk_income_vote_heads_category_id` = `ivc`.`income_vote_heads_category_id`))) join `funding_stream` `fs` on((`ivc`.`fk_funding_stream_id` = `fs`.`funding_stream_id`))) where (`fs`.`funding_stream_code` = 'gift');

DROP TABLE IF EXISTS `gift_expense_transactions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `gift_expense_transactions` AS select `v`.`voucher_id` AS `voucher_id`,`v`.`voucher_number` AS `voucher_number`,`v`.`voucher_date` AS `voucher_date`,`v`.`fk_office_id` AS `office_id`,`gea`.`expense_account_id` AS `expense_account_id`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost`,`v`.`fk_status_id` AS `status_id` from ((`voucher` `v` join `voucher_detail` `vd` on((`v`.`voucher_id` = `vd`.`fk_voucher_id`))) join `gift_expense_accounts` `gea` on((`vd`.`fk_expense_account_id` = `gea`.`expense_account_id`))) where ((`v`.`voucher_date` >= ((last_day(now()) + interval 1 day) - interval 3 month)) and (`vd`.`voucher_detail_total_cost` > 0)) group by `v`.`voucher_id`,`gea`.`expense_account_id`;

DROP TABLE IF EXISTS `gift_income_accounts`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `gift_income_accounts` AS select `ia`.`income_account_id` AS `income_account_id`,`ia`.`income_account_name` AS `income_account_name`,`ia`.`income_account_code` AS `income_account_code`,`ivc`.`income_vote_heads_category_name` AS `category_name`,`fs`.`funding_stream_name` AS `funding_stream_name`,`fs`.`funding_stream_code` AS `funding_stream_code`,`ia`.`fk_account_system_id` AS `account_system_id` from ((`income_account` `ia` join `income_vote_heads_category` `ivc` on((`ia`.`fk_income_vote_heads_category_id` = `ivc`.`income_vote_heads_category_id`))) join `funding_stream` `fs` on((`ivc`.`fk_funding_stream_id` = `fs`.`funding_stream_id`))) where (`fs`.`funding_stream_code` = 'gift');

DROP TABLE IF EXISTS `gift_income_transactions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `gift_income_transactions` AS select `v`.`voucher_id` AS `voucher_id`,`v`.`voucher_number` AS `voucher_number`,`v`.`voucher_date` AS `voucher_date`,`v`.`fk_office_id` AS `office_id`,`gia`.`income_account_id` AS `income_account_id`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost`,`v`.`fk_status_id` AS `status_id` from ((`voucher` `v` join `voucher_detail` `vd` on((`v`.`voucher_id` = `vd`.`fk_voucher_id`))) join `gift_income_accounts` `gia` on((`vd`.`fk_income_account_id` = `gia`.`income_account_id`))) where ((`v`.`voucher_date` >= ((last_day(now()) + interval 1 day) - interval 3 month)) and (`vd`.`voucher_detail_total_cost` > 0)) group by `v`.`voucher_id`,`gia`.`income_account_id`;

DROP TABLE IF EXISTS `month_cash_recipient_sum_amount`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `month_cash_recipient_sum_amount` AS select `voucher`.`fk_office_id` AS `fk_office_id`,date_format(`voucher`.`voucher_date`,'%Y-%m-01') AS `voucher_month`,`cash_recipient_account`.`fk_office_bank_id` AS `recipient_office_bank_id`,`cash_recipient_account`.`fk_office_cash_id` AS `recipient_office_cash_id`,`voucher`.`fk_status_id` AS `fk_status_id`,`voucher`.`fk_office_cash_id` AS `source_office_cash_id`,`voucher`.`fk_office_bank_id` AS `source_office_bank_id`,`voucher_type_effect`.`voucher_type_effect_code` AS `voucher_type_effect_code`,`voucher_type_account`.`voucher_type_account_code` AS `voucher_type_account_code`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from (((((`voucher_detail` join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `cash_recipient_account` on((`voucher`.`voucher_id` = `cash_recipient_account`.`fk_voucher_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) where (`voucher_type_effect`.`voucher_type_effect_code` in ('bank_to_bank_contra','cash_to_cash_contra')) group by `voucher`.`fk_office_id`,`voucher_month`,`cash_recipient_account`.`fk_office_bank_id`,`cash_recipient_account`.`fk_office_cash_id`,`voucher`.`fk_office_bank_id`,`voucher`.`fk_office_cash_id`,`voucher`.`fk_status_id`,`voucher_type_effect`.`voucher_type_effect_code`,`voucher_type_account`.`voucher_type_account_code`;

DROP TABLE IF EXISTS `monthly_gift_expense_transactions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_gift_expense_transactions` AS select `v`.`fk_office_id` AS `office_id`,`vd`.`fk_expense_account_id` AS `expense_account_id`,date_format(`v`.`voucher_date`,'%Y-%m-01') AS `voucher_date`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost` from (`voucher` `v` join `voucher_detail` `vd` on((`v`.`voucher_id` = `vd`.`fk_voucher_id`))) where `vd`.`fk_expense_account_id` in (select `gift_expense_accounts`.`expense_account_id` from `gift_expense_accounts`) group by `v`.`fk_office_id`,`vd`.`fk_expense_account_id`,month(`v`.`voucher_date`),year(`v`.`voucher_date`);

DROP TABLE IF EXISTS `monthly_gift_income_transactions`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_gift_income_transactions` AS select `v`.`fk_office_id` AS `office_id`,`vd`.`fk_income_account_id` AS `income_account_id`,date_format(`v`.`voucher_date`,'%Y-%m-01') AS `voucher_date`,sum(`vd`.`voucher_detail_total_cost`) AS `voucher_detail_total_cost` from (`voucher` `v` join `voucher_detail` `vd` on((`v`.`voucher_id` = `vd`.`fk_voucher_id`))) where `vd`.`fk_income_account_id` in (select `gift_income_accounts`.`income_account_id` from `gift_income_accounts`) group by `v`.`fk_office_id`,`vd`.`fk_income_account_id`,month(`v`.`voucher_date`),year(`v`.`voucher_date`);

DROP TABLE IF EXISTS `monthly_sum_expense_per_center`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_sum_expense_per_center` AS select `voucher`.`fk_office_id` AS `fk_office_id`,date_format(`voucher`.`voucher_date`,'%Y-%m-01') AS `voucher_month`,`voucher`.`fk_office_bank_id` AS `fk_office_bank_id`,`voucher`.`fk_status_id` AS `fk_status_id`,`voucher_detail`.`fk_expense_account_id` AS `fk_expense_account_id`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((`voucher_detail` join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) where (`voucher_type_effect`.`voucher_type_effect_code` = 'expense') group by `voucher`.`fk_office_id`,`voucher_month`,`voucher`.`fk_office_bank_id`,`voucher`.`fk_status_id`,`voucher_detail`.`fk_expense_account_id`;

DROP TABLE IF EXISTS `monthly_sum_income_expense_per_center`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_sum_income_expense_per_center` AS select `voucher`.`fk_office_id` AS `fk_office_id`,date_format(`voucher`.`voucher_date`,'%Y-%m-01') AS `voucher_month`,`voucher`.`fk_office_bank_id` AS `fk_office_bank_id`,`voucher`.`fk_status_id` AS `fk_status_id`,`income_account`.`income_account_id` AS `income_account_id`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((((`expense_account` join `voucher_detail` on((`expense_account`.`expense_account_id` = `voucher_detail`.`fk_expense_account_id`))) join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) join `income_account` on((`expense_account`.`fk_income_account_id` = `income_account`.`income_account_id`))) where ((`voucher_type_effect`.`voucher_type_effect_code` = 'expense') or (`voucher_type_effect`.`voucher_type_effect_code` = 'disbursements') or (`voucher_type_effect`.`voucher_type_effect_code` = 'prepayments')) group by `voucher`.`fk_office_id`,`voucher_month`,`voucher`.`fk_office_bank_id`,`voucher`.`fk_status_id`,`income_account`.`income_account_id`;

DROP TABLE IF EXISTS `monthly_sum_income_per_center`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_sum_income_per_center` AS select `voucher`.`fk_office_id` AS `fk_office_id`,date_format(`voucher`.`voucher_date`,'%Y-%m-01') AS `voucher_month`,`voucher`.`fk_office_bank_id` AS `fk_office_bank_id`,`project_allocation`.`project_allocation_id` AS `project_allocation_id`,`project_allocation`.`fk_project_id` AS `fk_project_id`,`voucher`.`fk_status_id` AS `fk_status_id`,`income_account`.`income_account_id` AS `income_account_id`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((((`income_account` join `voucher_detail` on((`income_account`.`income_account_id` = `voucher_detail`.`fk_income_account_id`))) join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) join `project_allocation` on((`project_allocation`.`project_allocation_id` = `voucher_detail`.`fk_project_allocation_id`))) where (((`voucher_type_account`.`voucher_type_account_code` = 'bank') and (`voucher_type_effect`.`voucher_type_effect_code` = 'income')) or ((`voucher_type_account`.`voucher_type_account_code` = 'accrual') and (`voucher_type_effect`.`voucher_type_effect_code` = 'payments'))) group by `voucher`.`fk_office_id`,`voucher_month`,`voucher`.`fk_office_bank_id`,`project_allocation`.`fk_project_id`,`project_allocation`.`project_allocation_id`,`voucher`.`fk_status_id`,`income_account`.`income_account_id`;

DROP TABLE IF EXISTS `monthly_sum_transactions_by_account_effect`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `monthly_sum_transactions_by_account_effect` AS select `voucher`.`fk_office_id` AS `fk_office_id`,date_format(`voucher`.`voucher_date`,'%Y-%m-01') AS `voucher_month`,`voucher`.`fk_office_bank_id` AS `fk_office_bank_id`,`voucher`.`fk_office_cash_id` AS `fk_office_cash_id`,`voucher`.`fk_status_id` AS `fk_status_id`,`voucher_type_effect`.`voucher_type_effect_code` AS `voucher_type_effect_code`,`voucher_type_account`.`voucher_type_account_code` AS `voucher_type_account_code`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((`voucher_detail` join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) group by `voucher`.`fk_office_id`,`voucher_month`,`voucher`.`fk_office_bank_id`,`voucher`.`fk_office_cash_id`,`voucher`.`fk_status_id`,`voucher_type_effect`.`voucher_type_effect_code`,`voucher_type_account`.`voucher_type_account_code`;

DROP TABLE IF EXISTS `offices_missing_last_month_financial_report`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `offices_missing_last_month_financial_report` AS select `office`.`office_id` AS `office_id`,`office`.`office_code` AS `office_code`,`office`.`office_name` AS `office_name`,`clusters`.`office_name` AS `cluster_name`,`office`.`fk_account_system_id` AS `fk_account_system_id` from (((`office` join `context_center` on((`office`.`office_id` = `context_center`.`fk_office_id`))) join `context_cluster` on((`context_center`.`fk_context_cluster_id` = `context_cluster`.`context_cluster_id`))) join (select `office`.`office_id` AS `office_id`,`office`.`office_name` AS `office_name` from `office` where (`office`.`fk_context_definition_id` = 2)) `clusters` on((`context_cluster`.`fk_office_id` = `clusters`.`office_id`))) where ((`office`.`fk_context_definition_id` = 1) and (`office`.`office_is_active` = 1) and `office`.`office_id` in (select `financial_report`.`fk_office_id` from `financial_report` where ((`financial_report`.`financial_report_month` = date_format((date_format(now(),'%Y-%m-01') - interval 1 month),'%Y-%m-01')) and (`financial_report`.`financial_report_is_submitted` = 1))) is false and (`office`.`office_start_date` < date_format(curdate(),'%Y-%m-01')));

DROP TABLE IF EXISTS `overdue_transit_deposit`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `overdue_transit_deposit` AS select `office`.`office_id` AS `office_id`,`office`.`office_code` AS `office_code`,`office`.`office_name` AS `office_name`,`voucher`.`voucher_date` AS `voucher_date`,`voucher_type_effect`.`voucher_type_effect_code` AS `voucher_type_effect_code`,`voucher`.`voucher_cleared` AS `voucher_cleared`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((((`voucher` join `voucher_detail` on((`voucher`.`voucher_id` = `voucher_detail`.`fk_voucher_id`))) join `office` on((`voucher`.`fk_office_id` = `office`.`office_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) join (select `financial_report`.`fk_office_id` AS `fk_office_id` from `financial_report` where ((`financial_report`.`financial_report_is_submitted` = 1) and (`financial_report`.`financial_report_month` = date_format((date_format(now(),'%Y-%m-01') - interval 1 month),'%Y-%m-01')))) `submitted_mfrs` on((`office`.`office_id` = `submitted_mfrs`.`fk_office_id`))) where ((`voucher`.`voucher_date` < date_format((curdate() - interval 1 month),'%Y-%m-01')) and (`voucher`.`voucher_cleared` = 0) and (`office`.`office_is_active` = 1) and (((`voucher_type_account`.`voucher_type_account_code` = 'bank') and (`voucher_type_effect`.`voucher_type_effect_code` = 'income')) or ((`voucher_type_account`.`voucher_type_account_code` = 'cash') and (`voucher_type_effect`.`voucher_type_effect_code` = 'cash_contra')))) group by `office`.`office_id`,`office`.`office_code`,`office`.`office_name`,`voucher`.`voucher_date`,`voucher_type_effect`.`voucher_type_effect_code`,`voucher`.`voucher_cleared` order by `voucher`.`voucher_date`;

DROP TABLE IF EXISTS `project_codes`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `project_codes` AS select distinct concat(`project`.`project_code`,' - ',`project`.`project_name`) AS `project_name`,`project`.`project_start_date` AS `project_start_date`,`project`.`project_end_date` AS `project_end_date`,`funder`.`fk_account_system_id` AS `fk_account_system_id`,`project`.`project_id` AS `project_id` from (`project` join `funder` on((`project`.`fk_funder_id` = `funder`.`funder_id`))) where ((`project`.`project_end_date` is not null) and (not((`project`.`project_end_date` like '0000-00-00'))));

DROP TABLE IF EXISTS `stale_cheques`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `stale_cheques` AS select `office`.`office_id` AS `office_id`,`office`.`office_code` AS `office_code`,`office`.`office_name` AS `office_name`,`voucher`.`voucher_cheque_number` AS `voucher_cheque_number`,`voucher`.`voucher_date` AS `voucher_date`,`voucher_type_effect`.`voucher_type_effect_code` AS `voucher_type_effect_code`,`voucher`.`voucher_cleared` AS `voucher_cleared`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((((`voucher` join `voucher_detail` on((`voucher`.`voucher_id` = `voucher_detail`.`fk_voucher_id`))) join `office` on((`voucher`.`fk_office_id` = `office`.`office_id`))) join `voucher_type` on((`voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`))) join `voucher_type_account` on((`voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`))) join `voucher_type_effect` on((`voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`))) join (select `financial_report`.`fk_office_id` AS `fk_office_id` from `financial_report` where ((`financial_report`.`financial_report_is_submitted` = 1) and (`financial_report`.`financial_report_month` in (date_format((date_format(now(),'%Y-%m-01') - interval 1 month),'%Y-%m-01'),date_format((date_format(now(),'%Y-%m-01') - interval 2 month),'%Y-%m-01'))))) `submitted_mfrs` on((`office`.`office_id` = `submitted_mfrs`.`fk_office_id`))) where ((`voucher`.`voucher_date` < date_format((curdate() - interval 6 month),'%Y-%m-01')) and (`voucher`.`voucher_cleared` = 0) and (`office`.`office_is_active` = 1) and (`voucher_type_account`.`voucher_type_account_code` = 'bank') and (`voucher_type_effect`.`voucher_type_effect_code` in ('expense','bank_contra'))) group by `office`.`office_id`,`office`.`office_code`,`office`.`office_name`,`voucher`.`voucher_cheque_number`,`voucher`.`voucher_date`,`voucher_type_effect`.`voucher_type_effect_code`,`voucher`.`voucher_cleared` order by `voucher`.`voucher_date`;

DROP TABLE IF EXISTS `sum_income_and_expense_per_project`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `sum_income_and_expense_per_project` AS select last_day(`voucher`.`voucher_date`) AS `voucher_month`,`office`.`fk_account_system_id` AS `account_system_id`,`voucher`.`fk_office_id` AS `office_id`,`office`.`office_code` AS `office_code`,`voucher_detail`.`fk_income_account_id` AS `income_account_id`,`income_account`.`income_account_code` AS `income_account_code`,`voucher_detail`.`fk_expense_account_id` AS `expense_account_id`,`project_allocation`.`fk_project_id` AS `project_id`,`voucher`.`fk_office_bank_id` AS `office_bank_id`,sum(`voucher_detail`.`voucher_detail_total_cost`) AS `amount` from ((((`voucher_detail` join `voucher` on((`voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`))) join `project_allocation` on((`voucher_detail`.`fk_project_allocation_id` = `project_allocation`.`project_allocation_id`))) join `income_account` on((`voucher_detail`.`fk_income_account_id` = `income_account`.`income_account_id`))) join `office` on((`voucher`.`fk_office_id` = `office`.`office_id`))) where (`voucher_detail`.`fk_income_account_id` > 0) group by `office`.`office_id`,`office`.`office_code`,`voucher_month`,`project_id`,`income_account`.`income_account_id`,`income_account`.`income_account_code`,`expense_account_id`,`office_bank_id` order by `voucher_month`,`account_system_id`,`office_id`,`income_account_id`,`expense_account_id`;

-- 2025-06-25 11:45:25
