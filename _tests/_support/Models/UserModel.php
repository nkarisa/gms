<?php

namespace Tests\Support\Models;

use App\Models\Core\UserModel as UsersModel;
use CodeIgniter\Test\Fabricator;
use Faker\Generator;

class UserModel extends UsersModel
{
    
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_track_number',
        'user_name',
        'user_firstname',
        'user_lastname',
        'user_email',
        'fk_context_definition_id',
        'fk_language_id',
        'fk_country_currency_id',
        'fk_role_id',
        'fk_account_system_id',
        'user_password',
        'user_created_date',
        'fk_status_id'
    ];
    protected $useTimestamps = true;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'user_created_date';
    protected $updatedField = 'user_last_modified_date';
    protected $deletedField = 'user_deleted_at';

     // public function fake(Generator &$faker)
    // {
    //     return [
    //         'first'    => $faker->firstName(),
    //         'email'    => $faker->unique()->email(),
    //         'group_id' => $faker->optional()->passthrough(mt_rand(1, Fabricator::getCount('groups'))),
    //     ];
    // }

    // public function fake(Generator &$faker)
    // {
    //     $userLibrary = new \App\Libraries\Core\UserLibrary();
    //     $statusLibrary = new \App\Libraries\Core\StatusLibrary();

    //     return [
    //         'user_track_number'             => 'USER-12345',
    //         'user_name'                     => 'joedoe',
    //         'user_firstname'                => "Joe",
    //         'user_lastname'                 =>  "Doe",
    //         'user_email'                    => "joedoe@gmail.com",
    //         'fk_context_definition_id'      => 1,
    //         'fk_language_id'                => 1,
    //         'fk_country_currency_id'        => 3,
    //         'fk_role_id'                    => 34,
    //         'fk_account_system_id'          => 3,
    //         'user_password'                 => $userLibrary->passwordSalt('password'),
    //         'user_created_date'             => '2024-12-13',
    //         'fk_status_id'                  => $statusLibrary->initialItemStatus('user',3),
    //     ];
    // }
}
