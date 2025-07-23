<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;
// use Faker\Factory;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userLibrary = new \App\Libraries\Core\UserLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $faker = \Faker\Factory::create();

        $users = [
            [
                'user_track_number'             => 'USER-12345',
                'user_name'                     => 'joedoe',
                'user_firstname'                => "Joe",
                'user_lastname'                 =>  "Doe",
                'user_email'                    => "joedoe@gmail.com",
                'fk_context_definition_id'      => 1,
                'fk_language_id'                => 1,
                'fk_country_currency_id'        => 3,
                'fk_role_id'                    => 34,
                'fk_account_system_id'          => 3,
                'user_password'                 => $userLibrary->passwordSalt('password'),
                'user_created_date'             => '2024-12-13',
                'fk_status_id'                  => $statusLibrary->initialItemStatus('user',3),
            ],
            [
                'user_track_number'             => 'USER-67890',
                'user_name'                     => 'hellebar',
                'user_firstname'                => "Hellen",
                'user_lastname'                 =>  "Bar",
                'user_email'                    => "hellenbar@gmail.com",
                'fk_context_definition_id'      => 1,
                'fk_language_id'                => 1,
                'fk_country_currency_id'        => 3,
                'fk_role_id'                    => 41,
                'fk_account_system_id'          => 3,
                'user_password'                 => $userLibrary->passwordSalt('password'),
                'user_created_date'             => '2024-12-13',
                'fk_status_id'                  => $statusLibrary->initialItemStatus('user',3),
            ],
            [
                'user_track_number'             => 'USER-111213',
                'user_name'                     => 'petersonfoo',
                'user_firstname'                => "Peterson",
                'user_lastname'                 =>  "Foo",
                'user_email'                    => "petersonfoo@gmail.com",
                'fk_context_definition_id'      => 2,
                'fk_language_id'                => 1,
                'fk_country_currency_id'        => 3,
                'fk_role_id'                    => 14,
                'fk_account_system_id'          => 3,
                'user_password'                 => $userLibrary->passwordSalt('password'),
                'user_created_date'             => '2024-12-13',
                'fk_status_id'                  => $statusLibrary->initialItemStatus('user',3),
            ]
        ];

        $builder = $this->db->table('user');

        foreach ($users as $user) {
            $builder->insert($user);
        }
    }
}
