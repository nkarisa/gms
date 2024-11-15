<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundsTransferModel;

class FundsTransferLibrary extends GrantsLibrary {
    protected $table;
    protected $fundsTransferModel;

    function __construct()
    {
        parent::__construct();

        $this->fundsTransferModel = new FundsTransferModel();

        $this->table = 'funds_transfer';
    }

    public function listTableVisibleColumns(): array
    {
        return [
            "funds_transfer_id",
            "funds_transfer_track_number",
            "office_name",
            "funds_transfer_type",
            "funds_transfer_description",
            "funds_transfer_amount",
            "status_name",
            'office_name',
        ];
    }

    function changeFieldType(): array{
        $change_field_type = array();
    
        $change_field_type['funds_transfer_type']['field_type'] = 'select';
        $change_field_type['funds_transfer_type']['options'] = ['1' => get_phrase('income_type'), '2' => get_phrase('expense_type')];
    
        return $change_field_type;
      }
}