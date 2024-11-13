<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CountryCurrencyModel;
class CountryCurrencyLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new CountryCurrencyModel();

        $this->table = 'country_currency';
    }

    public function getCountryCurrency()
    {

        $builder = $this->read_db->table($this->table);
        $builder->select(array('country_currency_id', 'country_currency_name'));

        if (!$this->session->system_admin) {
            $builder->where(array('fk_account_system_id' => $this->session->user_account_system_id));
        }

        $country_currency_id_and_names = $builder->get()->getResultArray();
        $curreny_ids = array_column($country_currency_id_and_names, 'country_currency_id');
        $curreny_names = array_column($country_currency_id_and_names, 'country_currency_name');
        $curreny_ids_and_names = array_combine($curreny_ids, $curreny_names);

        return $curreny_ids_and_names;
    }


    /**
   * get_country_currency(): returns currency id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function getCountryCurrencyByAccountSystemId(int $account_system_id): array
  {
    $builder = $this->read_db->table($this->table);
    $builder->select(['country_currency_id']);
    $builder->where(['fk_account_system_id' => $account_system_id]);
    $country_currency = $builder->get();

    $country_currency_id = 0;

    if($country_currency->getNumRows() > 0){
        $country_currency_id = $country_currency->getRow()->country_currency_id;
    }

    return compact('country_currency_id'); 

  }

     /**
   * get_country_currency_id() 
   * This method returns the currenncy of a country
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 03/08/2022
   */
  public function getCountryCurrencyId()
  {
    $builder = $this->read_db->table('country_currency');
    $builder->select(array('country_currency_id'));
    if (!$this->session->system_admin) {
        $builder->where(array('fk_account_system_id' => $this->session->user_account_system_id));
    }
    $country_currency_id =$builder->get()->getRow()->country_currency_id;  
    return  $country_currency_id;
  }

}