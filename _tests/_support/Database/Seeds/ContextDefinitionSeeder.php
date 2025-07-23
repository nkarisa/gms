<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;
class ContextDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $contextDefinitions = [
            [
                'context_definition_track_number'       => 'COON-4637',
                'context_definition_name'               => 'global',
                'context_definition_level'              => 6,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ],
            [
                'context_definition_track_number'       => 'COON-11285',
                'context_definition_name'               => 'region',
                'context_definition_level'              => 5,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ],
            [
                'context_definition_track_number'       => 'COON-42271',
                'context_definition_name'               => 'country',
                'context_definition_level'              => 4,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ],
            [
                'context_definition_track_number'       => 'COON-36220',
                'context_definition_name'               => 'cohort',
                'context_definition_level'              => 3,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ],
            [
                'context_definition_track_number'       => 'COON-24098',
                'context_definition_name'               => 'cluster',
                'context_definition_level'              => 2,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ],
            [
                'context_definition_track_number'       => 'COON-85304',
                'context_definition_name'               => 'center',
                'context_definition_level'              => 1,
                'context_definition_is_implementing'    => 1,
                'context_definition_is_active'          => 1,
                'context_definition_created_date'       => '2024-12-13',
                'context_definition_created_by'         => 1,
                'context_definition_last_modified_date' => '2024-12-13 06:22:53',
                'context_definition_last_modified_by'   => 1,
                'context_definition_deleted_at'         => '2024-12-13'
            ]
        ];

        $builder = $this->db->table('context_definition');

        foreach ($contextDefinitions as $contextDefinition) {
            $builder->insert($contextDefinition);
        }
    }
}
