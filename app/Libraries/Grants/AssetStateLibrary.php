<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AssetStateModel;
class AssetStateLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $assetstatusModel;

    function __construct()
    {
        parent::__construct();

        $this->assetstatusModel = new AssetStateModel();

        $this->table = 'asset_state';
    }

    function singleFormAddVisibleColumns(): array
    {
        return [
            'asset_state_name',
            'asset_state_description',
            'asset_state_operation'
        ];
    }

    function editVisibleColumns(): array
    {
        return [
            'asset_state_name',
            'asset_state_description',
            'asset_state_is_active',
            'asset_state_operation'
        ];
    }

    function listTableVisibleColumns(): array {
        return [
            'asset_state_track_number',
            'asset_state_name',
            'asset_state_is_active',
            'asset_state_is_default',
            'asset_state_operation',
            'asset_state_created_date'
        ];
    }

    public function changeFieldType(): array {
        $change_field_type['asset_state_operation']['field_type'] = 'select';
        $change_field_type['asset_state_operation']['options'] = [
            'pending' => 'pending',
            'operational' => 'operational',
            'obselete' => 'obselete'
        ];

        return $change_field_type;
    }

   
}