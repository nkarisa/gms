<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\RequestModel;
class RequestLibrary extends GrantsLibrary
{

    protected $table;
    protected $requestModel;

    function __construct()
    {
        parent::__construct();

        $this->requestModel = new RequestModel();

        $this->table = 'request';
    }


    function getOfficeRequestCount(){
        $builder = $this->read_db->table('request');
        $builder->whereIn('fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
        $get_office_request_count = $builder->get()->getNumRows();
    
        return $get_office_request_count;
      }
   
}