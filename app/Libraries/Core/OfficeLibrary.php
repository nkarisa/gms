<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeModel;

class OfficeLibrary extends GrantsLibrary
{

    protected $table;
    protected $officeModel;

    function __construct()
    {
        parent::__construct();

        $this->officeModel = new OfficeModel();

        $this->table = 'office';
    }


    function getRecordOfficeId($table, $primary_key)
    {

      $lookup_tables = $this->lookupTables($table);
      $pk_field = $this->primaryKeyField($table);
  
      $office_id = 0;
  
      if (in_array('office', $lookup_tables)) {
        $builder = $this->read_db->table($table);
        $builder->where($pk_field, $primary_key);
        $office_id = $builder->get()->getRow()->fk_office_id;
      }
  
      return $office_id;
    }

}