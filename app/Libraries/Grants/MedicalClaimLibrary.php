<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\MedicalClaimModel;
class MedicalClaimLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new MedicalClaimModel();

        $this->table = 'grants';
    }

    function updateMedicalClaimAttachmentId(){
        
    }
   
}