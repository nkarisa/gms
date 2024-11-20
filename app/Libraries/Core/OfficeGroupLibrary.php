<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeGroupModel;
class OfficeGroupLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new OfficeGroupModel();

        $this->table = 'office_group';
    }

    public function checkIfOfficeIsOfficeGroupLead($office_id){

        $is_group_lead = false;

        $builder = $this->read_db->table("office_group");
        $builder->where(["fk_office_id"=>$office_id,'office_group_association_is_lead'=>1]);
        $builder->join('office_group_association','office_group_association.fk_office_group_id=office_group.office_group_id');
        $office_group_obj = $builder->get();

        if($office_group_obj->getNumRows() > 0){
            $is_group_lead = true;
        }

        return $is_group_lead;

    }
   
}