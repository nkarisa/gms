<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AccrualSeeder extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\AccrualVoucherTypeAccountSeeder');
        $this->call('App\Database\Seeds\AccrualVoucherTypeEffectSeeder');
        $this->call('App\Database\Seeds\AccrualAccountSystemSettingSeeder');
        // $this->call('App\Database\Seeds\AssetStatusSeeder');
        // $this->call('App\Database\Seeds\AssetCategorySeeder');
    }
}
