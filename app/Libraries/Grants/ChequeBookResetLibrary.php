<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookResetModel;
class ChequeBookResetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ChequeBookResetModel();

        $this->table = 'grants';
    }


    function deactivateChequeBookReset($office_bank_id)
    {
        $chequeBookResetWriteBuilder = $this->write_db->table('cheque_book_reset');

        $chequeBookResetWriteBuilder->where(array('fk_office_bank_id' => $office_bank_id));
        $data['cheque_book_reset_is_active'] = 0;
        $chequeBookResetWriteBuilder->update($data);
    }
   
}