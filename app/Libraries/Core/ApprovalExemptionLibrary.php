<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ApprovalExemptionModel;
class ApprovalExemptionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $coreModel;
    public array $lookUpTablesForeignKeyMappings = [
        'status' => 'approval_exemption_status_id',
    ];

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new ApprovalExemptionModel();

        $this->table = 'core';
    }


    function editVisibleColumns(): array
    {
        return ['office_name','approval_exemption_name', 'approval_exemption_is_active'];
    }

    function listTableVisibleColumns(): array {
        $columns = [
          'approval_exemption_track_number',
          'approval_exemption_name',
          'approve_item_name',
          'office_name',
          'approval_exemption_is_active',
          'approval_exemption_created_date'
        ];
    
        return $columns;
    }

    function actionBeforeInsert($post_array): array
    {

        $post_array['header']['approval_exemption_status_id'] = hash_id($this->id, 'decode');

        $status_name = $this->read_db->table('status')
        ->where(array('status_id' => hash_id($this->id, 'decode')))
        ->get()->getRow()->status_name;

        $post_array['header']['approval_exemption_name'] = $status_name;

        return $post_array;
    }

    // function detailListQuery(): void
    // {
    //     $readBuilder = $this->read_db->table('approval_exemption');
    //     $readBuilder->join('office', 'office.office_id=approval_exemption.fk_office_id');
    //     $readBuilder->join('status', 'status.status_id=approval_exemption.approval_exemption_status_id');
    //     $readBuilder->where(array('approval_exemption_status_id' => hash_id($this->id, 'decode')));
    //     $result = $readBuilder->get()->getResultArray();
    // }

    function detailListTableVisibleColumns(): array
    {
        return ['approval_exemption_track_number', 'office_name', 'approval_exemption_is_active','approval_exemption_created_date'];
    }

    function multiSelectField(): string
    {
        return 'office';
    }

    function transactionValidateDuplicatesColumns(): array
    {
        return ['approval_exemption_status_id', 'fk_office_id'];
    }

    function singleFormAddVisibleColumns(): array
    {
        return ['office_name'];
    }

    function lookupValues(): array
    {
        $lookup_values = parent::lookupValues();
        $officeReadBuilder = $this->read_db->table('office');

        if(!$this->session->system_admin){
            $officeReadBuilder->where(['fk_account_system_id' => $this->session->user_account_system_id]);
        }

        $officeReadBuilder->select(array('office_id','office_name'));
        $officeReadBuilder->where(array('fk_context_definition_id' => 1, 'office_is_active' => 1));
        $officeReadBuilder->where('NOT EXISTS (SELECT * FROM approval_exemption WHERE approval_exemption.fk_office_id=office.office_id AND approval_exemption_status_id = '.hash_id($this->id, 'decode').')','',FALSE);
        $lookup_values['office'] = $officeReadBuilder->get()->getResultArray();

        return $lookup_values;
    }
   
}