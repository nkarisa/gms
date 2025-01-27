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
        $cash_accounts=$builder->get('office_cash')->getResultArray();
  
        return $cash_accounts;
      }

   
}