<?php

namespace App\Entities\Grants;

use CodeIgniter\Entity\Entity;

class VoucherDetail extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [];
}
