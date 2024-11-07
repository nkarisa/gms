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

        $this->table = 'grants';
    }

    public function getCountryCurrency()
    {

        $builder = $this->read_db->table('country_currency');
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

}