<?php

namespace App\Libraries\Grants\Shreds;

use CodeIgniter\Database\BaseConnection;
use App\Libraries\Grants\AssetDepreciationLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Enums\{VoucherTypeEffectEnum};
use App\Libraries\Grants\VoucherLibrary;

/**
 * DepreciationVoucherLibrary class for handling all depreciation-related
 * voucher and account logic.
 *
 * This library centralizes database interactions and business logic
 * for generating, managing, and verifying depreciation vouchers.
 */
class DepreciationVoucherCreator
{
    /**
     * @var BaseConnection The database connection for read operations.
     */
    protected BaseConnection $readDb;

    /**
     * @var BaseConnection The database connection for write operations.
     */
    protected BaseConnection $writeDb;

    protected VoucherLibrary $voucherLibrary;
    protected VoucherCreator $voucherCreator;
    protected AssetDepreciationLibrary $assetDepreciationLibrary;
    protected StatusLibrary $statusLibrary;
    /**
     * Class constructor.
     *
     * @param BaseConnection $readDb The database connection instance for read operations.
     * @param BaseConnection $writeDb The database connection instance for write operations.
     */
    public function __construct(BaseConnection $readDb, BaseConnection $writeDb)
    {
        $this->readDb = $readDb;
        $this->writeDb = $writeDb;
        $this->voucherLibrary = new VoucherLibrary();
        $this->assetDepreciationLibrary = new AssetDepreciationLibrary();
        $this->statusLibrary = new StatusLibrary();
        $this->voucherCreator = new VoucherCreator($readDb, $writeDb);
    }

    /**
     * Gets the voucher type ID for depreciation. If it doesn't exist,
     * a new hidden voucher type is created automatically.
     *
     * @param int $accountSystemId The ID of the account system.
     * @return int The voucher type ID for depreciation.
     */
    private function getAccountSystemDepreciationVoucherTypeId(int $accountSystemId): int
    {
        $builder = $this->readDb->table('voucher_type');
        $builder->select(['voucher_type_id', 'voucher_type_name']);
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id = voucher_type.fk_voucher_type_effect_id');
        $builder->where([
            'voucher_type.fk_account_system_id' => $accountSystemId,
            'voucher_type_effect_code' => 'depreciation', // Assumed constant from VoucherTypeEffectEnum
            'voucher_type_is_active' => 1
        ]);
        
        $result = $builder->get();
        
        if ($result->getNumRows() > 0) {
            return (int)$result->getRow()->voucher_type_id;
        } else {
            // Create a hidden voucher type for depreciation
            $nameAndTrackNumber = $this->voucherLibrary->generateItemTrackNumberAndName('voucher_type');

            $depreciationVoucherTypeData = [
                'voucher_type_track_number' => $nameAndTrackNumber['voucher_type_track_number'],
                'voucher_type_name' => 'Depreciation Journal',
                'voucher_type_abbrev' => 'DEP',
                'voucher_type_is_active' => 1,
                'voucher_type_is_hidden' => 1,
                'voucher_type_is_cheque_referenced' => 0,
                'fk_account_system_id' => $accountSystemId,
                'fk_voucher_type_effect_id' => $this->getVoucherTypeEffectId('depreciation'), // Assumed constant from VoucherTypeEffectEnum
                'fk_voucher_type_account_id' => $this->getVoucherTypeAccountId('accrual'), // Assumed constant from VoucherTypeAccountEnum
            ];
            
            $this->writeDb->table('voucher_type')->insert($depreciationVoucherTypeData);
            return (int)$this->writeDb->insertID();
        }
    }

    /**
     * Gets the voucher type effect ID based on its code.
     *
     * @param string $effectCode The code of the voucher type effect.
     * @return int|null The voucher type effect ID, or null if not found.
     */
    private function getVoucherTypeEffectId(string $effectCode): ?int
    {
        $builder = $this->readDb->table('voucher_type_effect');
        $builder->select(['voucher_type_effect_id']);
        $builder->where(['voucher_type_effect_code' => $effectCode]);
        
        $result = $builder->get();
        
        if ($result->getNumRows() > 0) {
            return (int)$result->getRow()->voucher_type_effect_id;
        }
        
        return null;
    }
    
    /**
     * Gets the voucher type account ID based on its code.
     *
     * @param string $accountCode The code of the voucher type account.
     * @return int|null The voucher type account ID, or null if not found.
     */
    private function getVoucherTypeAccountId(string $accountCode): ?int
    {
        $builder = $this->readDb->table('voucher_type_account');
        $builder->select(['voucher_type_account_id']);
        $builder->where(['voucher_type_account_code' => $accountCode]);
        
        $result = $builder->get();
        
        if ($result->getNumRows() > 0) {
            return (int)$result->getRow()->voucher_type_account_id;
        }
        
        return null;
    }

    /**
     * Gets office data by its ID.
     *
     * @param int $officeId The ID of the office.
     * @return array|null The office data as an array, or null if not found.
     */
    private function getOfficeById(int $officeId): ?array
    {
        $builder = $this->readDb->table('office');
        $builder->where('office_id', $officeId);
        $result = $builder->get();

        if ($result->getNumRows() > 0) {
            return $result->getRowArray();
        }

        return null;
    }

    /**
     * Gets the project allocation ID for depreciation based on office ID.
     * Assumes the 'project_allocation' will be created if it's missing.
     *
     * @param int $officeId The ID of the office.
     * @return int|null The project allocation ID, or null if not found and creation fails.
     */
    private function getDepreciationProjectAllocationId(int $officeId): ?int
    {
        $builder = $this->readDb->table('project_allocation');
        $builder->select('project_allocation_id');
        $builder->join('project','project.project_id=project_allocation.fk_project_id');
        $builder->join('project_income_account','project_income_account.fk_project_id=project.project_id');
        $builder->join('income_account','income_account.income_account_id=project_income_account.fk_income_account_id');
        $builder->join('expense_account','expense_account.fk_income_account_id=income_account.income_account_id');
        $builder->join('expense_vote_heads_category','expense_vote_heads_category.expense_vote_heads_category_id=expense_account.fk_expense_vote_heads_category_id');
        $builder->where('fk_office_id', $officeId);
        $builder->where('expense_vote_heads_category_code', 'depreciation'); // Assumed constant from VoucherTypeEffectEnum
        $result = $builder->get();

        if ($result->getNumRows() > 0) {
            return (int)$result->getRow()->project_allocation_id;
        } else {
            // TODO: Logic to create the project allocation if it is missing
            return null;
        }
    }

    /**
     * Gets the depreciation expense account ID based on the office account system.
     * Assumes the 'expense_account' will be created if it's missing.
     *
     * @param int $officeAccountSystemId The ID of the office's account system.
     * @return int|null The expense account ID, or null if not found and creation fails.
     */
    private function getDepreciationExpenseAccountId(int $officeAccountSystemId): ?int
    {
        $builder = $this->readDb->table('expense_account');
        $builder->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
        $builder->join('expense_vote_heads_category','expense_vote_heads_category.expense_vote_heads_category_id=expense_account.fk_expense_vote_heads_category_id');
        $builder->where('expense_vote_heads_category_code', 'depreciation'); // Assumed constant from VoucherTypeEffectEnum
        $builder->where('income_account.fk_account_system_id', $officeAccountSystemId);
        $result = $builder->get();

        if ($result->getNumRows() > 0) {
            return (int)$result->getRow()->expense_account_id;
        } else {
            // TODO: Logic to create the expense account if it is missing
            return null;
        }
    }

    /**
     * Updates the asset depreciation schedule with a voucher ID.
     *
     * @param array $assetDepreciationIds An array of asset depreciation IDs.
     * @param int $voucherId The ID of the voucher to link.
     * @return bool True on success, false on failure.
     */
    private function updateAssetDepreciationScheduleWithVoucherIds(array $assetDepreciationIds, int $voucherId): bool
    {
        $builder = $this->writeDb->table('asset_depreciation');
        $updateData['fk_voucher_id'] = $voucherId;
        $builder->whereIn('asset_depreciation_id', $assetDepreciationIds);
        $builder->update($updateData);

        return $this->writeDb->affectedRows() > 0;
    }

        /**
     * Checks if a depreciation expense voucher has already been created for a given month.
     *
     * @param int $officeId The ID of the office.
     * @param string $reportingMonth The reporting month in 'YYYY-MM-DD' format.
     * @return bool True if a voucher exists, false otherwise.
     */
    private function hasMonthDepreciationExpenseCreated(int $officeId, string $reportingMonth): bool
    {
        $builder = $this->readDb->table('voucher');

        $startOfMonthDate = date('Y-m-01', strtotime($reportingMonth));
        $endOfMonthDate = date('Y-m-t', strtotime($reportingMonth));

        $builder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->where('voucher_type_effect_code', VoucherTypeEffectEnum::DEPRECIATION->getCode()); // Assumed constant from VoucherTypeEffectEnum
        $builder->where('fk_office_id', $officeId);
        $builder->where('voucher_date >= ', $startOfMonthDate);
        $builder->where('voucher_date <= ', $endOfMonthDate);
        
        $countMonthDepreciationVouchers = $builder->countAllResults();

        return $countMonthDepreciationVouchers > 0;
    } 

    /**
     * Main method Generates a monthly depreciation voucher for a given office and reporting month.
     *
     * @param int $officeId The ID of the office.
     * @param string $reportingMonth The reporting month in 'YYYY-MM-DD' format.
     * @return array An associative array with the flag, message, and voucher ID.
     */
    public function generateMonthDepreciationVoucher(int $officeId, string $reportingMonth): array
    {
        
        $office = $this->getOfficeById($officeId);

        if (!$office) {
            return ['flag' => false, 'message' => 'Office not found.', 'voucherId' => 0];
        }

        try {
            // We'll wrap all database actions in a single transaction.
            $this->writeDb->transBegin();

            $hasMonthDepreciationExpenseCreated = $this->hasMonthDepreciationExpenseCreated($officeId, $reportingMonth);

            if ($hasMonthDepreciationExpenseCreated) {
                 return ['flag' => false, 'message' => 'Depreciation voucher already created for this month.', 'voucherId' => 0];
            }

            // The following methods would need to be implemented within your Voucher library
            // For now, we'll assume they are available.
            $voucherNumber = $this->voucherLibrary->getVoucherNumber($officeId);
            $vouchingDate = $this->voucherLibrary->getVoucherDate($officeId);
            
            $officeAccountSystemId = $office['fk_account_system_id'];
            $description = $office['office_code'] . ' ' . date('F Y', strtotime($reportingMonth)). ' '. get_phrase('depreciation_expense');
            
            $monthDepreciationExpense = $this->assetDepreciationLibrary->calculateMonthsDepreciationExpense($officeId, $reportingMonth);
            
            if ($monthDepreciationExpense['totalDepreciationExpense'] == 0) {
                return ['flag' => false, 'message' => 'Total depreciation expense is zero for the month.', 'voucherId' => 0];
            }
            
            $projectAllocationId = $this->getDepreciationProjectAllocationId($officeId);
            $depreciationExpenseAccountId = $this->getDepreciationExpenseAccountId($officeAccountSystemId);
            $voucherTypeId = $this->getAccountSystemDepreciationVoucherTypeId($officeAccountSystemId);

            $voucherData = [
                'fk_office_id' => $officeId,
                'voucher_date' => $vouchingDate,
                'voucher_number' => $voucherNumber,
                'fk_voucher_type_id' => $voucherTypeId,
                'voucher_vendor' => $office['office_code'],
                'voucher_vendor_address' => $office['office_code'],
                'voucher_description' => $description,
                'voucher_detail_quantity' => [1],
                'voucher_detail_description' => [$description],
                'voucher_detail_unit_cost' => [$monthDepreciationExpense['totalDepreciationExpense']],
                'voucher_detail_total_cost' => [$monthDepreciationExpense['totalDepreciationExpense']],
                'fk_project_allocation_id' => [$projectAllocationId],
                'voucher_detail_account' => [$depreciationExpenseAccountId]
            ];

            $fullyApprovedStatusId = $this->statusLibrary->getMaxApprovalStatusId('voucher',[],$officeAccountSystemId)[0];

            $result = $this->voucherCreator->insertVoucher($voucherData, $fullyApprovedStatusId);

            $voucherId = $result['headerId'];
            $assetDepreciationIds = $monthDepreciationExpense['assetDepreciationIds'];
            
            $this->updateAssetDepreciationScheduleWithVoucherIds($assetDepreciationIds, $voucherId);

            if ($this->writeDb->transStatus() === false || !$result['voucher_posting_condition']) {
                $this->writeDb->transRollback();
                return ['flag' => false,'message' => get_phrase('voucher_creation_failed'), 'voucherId' => 0];
            } else {
                $this->writeDb->transCommit();
                return ['flag' => true,'message' => get_phrase('voucher_creation_success'), 'voucherId' => $voucherId];
            }
        } catch (\Exception $e) {
            $this->writeDb->transRollback();
            log_message('error', 'Database error during voucher generation: ' . $e->getMessage());
            return ['flag' => false,'message' => 'Database error.', 'voucherId' => 0];
        } catch (\Exception $e) {
            $this->writeDb->transRollback();
            log_message('error', 'General error during voucher generation: ' . $e->getMessage());
            return ['flag' => false,'message' => 'An error occurred.', 'voucherId' => 0];
        }
    }   
    
}
