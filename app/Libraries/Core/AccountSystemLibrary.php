<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AccountSystemModel;
class AccountSystemLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AccountSystemModel();

        $this->table = 'account_system';
    }


    function getAccountSystems(){
        $builder = $this->read_db->table($this->table);
        $builder->select(array('account_system_id', 'account_system_code', 'account_system_name'));
        $account_systems = $builder->get()->getResultObject();
        return $account_systems;
      }

      function getAccountSystemIdByCode($account_system_code){
        $builder = $this->read_db->table('account_system');
        $builder->where('account_system_code', $account_system_code);
        $account_system_id = $builder->get()->getRow()->account_system_id;
        return $account_system_id;
      }
   
}