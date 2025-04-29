<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OfficeCashModel;
class OfficeCashLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OfficeCashModel();

        $this->table = 'grants';
    }


    public function getActiveOfficeCashByOfficeId($office_id){
        $builder = $this->read_db->table('office_cash');
        $builder->select(['office_cash_id','office_cash_name']);
        $builder->where(['office_cash_is_active' => 1,'office_id'=>$office_id]);
        $builder->join('account_system','account_system.account_system_id=office_cash.fk_account_system_id');
        $builder->join('office','office.fk_account_system_id=account_system.account_system_id');
        $cash_accounts=$builder->get()->getResultArray();
  
        return $cash_accounts;
      }

        /**
   * get_active_office_cash(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return array
   * @param int $account_system_id
   */
    public function getActiveOfficeCash(int $account_system_id): array{
        $officeCashReadBuilder = $this->read_db->table('office_cash');
        $officeCashReadBuilder->select(['office_cash_id','office_cash_name']);
        $officeCashReadBuilder->where(['office_cash_is_active'=>1,'fk_account_system_id'=>$account_system_id]);
        $cash_accounts=$officeCashReadBuilder->get()->getResultArray();
  
        return $cash_accounts;
  
      }

      public function listTableVisibleColumns(): array {
        return [
          'office_cash_track_number',
          'office_cash_name',
          'office_cash_is_active',
          'account_system_name',
          'office_cash_created_date'
        ];
      }
   
}