<?php

namespace App\Entities\Grants;

use CodeIgniter\Entity\Entity;
use \Tatter\Relations\Traits\EntityTrait;
class Voucher extends Entity
{
    protected $datamap = [];
    protected $dates   = ['voucher_created_date', 'voucher_last_modified_date', 'voucher_deleted_date'];
    protected $casts   = [];
}
