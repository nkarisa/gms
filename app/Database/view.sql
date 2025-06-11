DROP VIEW IF EXISTS `monthly_sum_income_per_center`;
CREATE VIEW monthly_sum_income_per_center AS SELECT voucher.fk_office_id, date_format(`voucher`.`voucher_date`,'%Y-%m-01') as voucher_month, voucher. fk_office_bank_id,project_allocation_id,fk_project_id,
voucher.fk_status_id, income_account_id, sum(`voucher_detail`.`voucher_detail_total_cost`) as amount
FROM income_account
JOIN voucher_detail ON `income_account`.`income_account_id` = `voucher_detail`.`fk_income_account_id`
JOIN voucher ON `voucher_detail`.`fk_voucher_id` = `voucher`.`voucher_id`
JOIN voucher_type ON `voucher`.`fk_voucher_type_id` = `voucher_type`.`voucher_type_id`
JOIN voucher_type_account ON `voucher_type`.`fk_voucher_type_account_id` = `voucher_type_account`.`voucher_type_account_id`
JOIN voucher_type_effect ON `voucher_type`.`fk_voucher_type_effect_id` = `voucher_type_effect`.`voucher_type_effect_id`
JOIN project_allocation ON project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id
WHERE (`voucher_type_account`.`voucher_type_account_code` = 'bank' AND `voucher_type_effect`.`voucher_type_effect_code` = 'income')
OR (`voucher_type_account`.`voucher_type_account_code` = 'accrual' AND `voucher_type_effect`.`voucher_type_effect_code` = 'payments')
GROUP BY `voucher`.`fk_office_id`,`voucher_month`,`voucher`.`fk_office_bank_id`,fk_project_id,project_allocation_id,`voucher`.`fk_status_id`,`income_account`.`income_account_id`;


DROP VIEW IF EXISTS `monthly_sum_income_expense_per_center`;
CREATE VIEW monthly_sum_income_expense_per_center AS SELECT fk_office_id,DATE_FORMAT(voucher_date, '%Y-%m-01') as voucher_month,fk_office_bank_id,voucher.fk_status_id as fk_status_id,income_account_id,sum(voucher_detail_total_cost) as amount FROM 
expense_account
JOIN voucher_detail ON expense_account.expense_account_id=voucher_detail.fk_expense_account_id
JOIN voucher ON voucher_detail.fk_voucher_id=voucher.voucher_id
JOIN voucher_type ON voucher.fk_voucher_type_id=voucher_type.voucher_type_id
JOIN voucher_type_account ON voucher_type.fk_voucher_type_account_id=voucher_type_account.voucher_type_account_id
JOIN voucher_type_effect ON voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id
JOIN income_account ON expense_account.fk_income_account_id=income_account.income_account_id
WHERE voucher_type_effect_code = 'expense' OR voucher_type_effect_code = 'disbursements' OR voucher_type_effect_code = 'prepayments'
GROUP BY fk_office_id, voucher_month,fk_office_bank_id, voucher.fk_status_id, income_account_id;