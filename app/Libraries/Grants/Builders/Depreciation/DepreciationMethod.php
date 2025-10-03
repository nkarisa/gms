<?php 

namespace App\Libraries\Grants\Builders\Depreciation;

interface DepreciationMethod {
    public function getDepreciationSchedule(): array;

}