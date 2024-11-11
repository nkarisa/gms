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

        /**
   * get_countries(): return an array of account systems/countries
   * @author Onduso 
   * @access public 
   * @return array
   */
  public function getCountries(): array
  {

    $builder = $this->read_db->table($this->table);
    $builder->select(['account_system_id', 'account_system_code']);
    $builder->where(['account_system_is_active' => 1]);
    $countries = $builder->get()->getResultArray();

    $country_ids = array_column($countries, 'account_system_id');
    $country_code = array_column($countries, 'account_system_code');

    $country_ids_and_codes = array_combine($country_ids, $country_code);

    return $country_ids_and_codes;
  }
   
  /**
   * get_country_language(): returns language id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function getCountryLanguage(int $account_system_id):array
  {

    $builder = $this->read_db->table('account_system_language');

    $builder->select(['fk_language_id']);
    $builder->where(['fk_account_system_id' => $account_system_id]);
    $language = $builder->get();

    $language_id = 0;

    if($language->getNumRows() > 0){
      $language_id = $language->getRow()->fk_language_id;
    }

    return compact('language_id');
  }
}