<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\DesignationModel;
class DesignationLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new DesignationModel();

        $this->table = 'core';
    }


    function retrieveDesignations($context_definition_id){

        $builder = $this->read_db->table('designation');
        $builder->select(array('designation_id', 'designation_name'));
        $builder->where(['fk_context_definition_id'=>$context_definition_id]);
        $designations = $builder->get()->getResultArray();
    
        $designations_ids = array_column($designations,'designation_id');
    
        $designations_names = array_column($designations,'designation_name');
    
        $designations_ids_and_names = array_combine($designations_ids,$designations_names);
    
        return  $designations_ids_and_names;
       }
   
}