<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BankModel;

class BankLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface {
    protected $table;
    protected $bankModel;

    public array $lookUpTablesForeignKeyMappings = [
        'user' => [
            'bank_last_modified_by',
            'bank_created_by'
        ]
    ];

    function __construct()
    {
        parent::__construct();

        $this->bankModel = new BankModel();

        $this->table = 'bank';
    }

    // function pagePosition(){
    //     $widget['position_1']['view'][] =  "Hello World";
    //     return $widget;
    // }

    function detailTables(): array
    {
        return ['office_bank'];
    }

    // function columnAliases(): array{
    //     return ['bank_name' => 'My Banking'];
    // }


    function listTableVisibleColumns(): array {
        return ['bank_track_number', 'bank_name', 'bank_swift_code', 'bank_is_active','account_system_name','bank_last_modified_by'];
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray): mixed {
        switch($columnName){
            case "bank_last_modified_by":
                $columnValue = $rowArray['user_firstname'] . ' ' . $rowArray['user_lastname'];
                break;
            default:
                break;
        }
  
        return $columnValue;
      }


function  setDatatableSearching(\CodeIgniter\Database\BaseBuilder $builder, array $selectColumns, array $extraColumns = []){
    $extraColumns = ['user_firstname','user_lastname'];
    return parent::setDatatableSearching($builder, $selectColumns, $extraColumns);
}

}