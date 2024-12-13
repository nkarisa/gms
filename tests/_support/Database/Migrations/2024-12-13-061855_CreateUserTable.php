<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_track_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_firstname' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_lastname' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'fk_context_definition_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'user_is_context_manager' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'user_is_system_admin' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'fk_language_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => "User's default language",
            ],
            'fk_country_currency_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_is_active' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],
            'fk_role_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_account_system_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_password' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'user_first_time_login' => [
                'type' => 'SMALLINT',
                'default' => 0,
                'null' => false,
            ],
            'user_last_login_time' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => null,
            ],
            'user_access_count' => [
                'type' => 'INT',
                'default' => 0,
                'null' => false,
            ],
            'md5_migrate' => [
                'type' => 'SMALLINT',
                'default' => 0,
                'null' => false,
            ],
            'user_employment_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'user_unique_identifier' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'fk_unique_identifier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_personal_data_consent_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'user_personal_data_consent_content' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'user_is_switchable' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],
            'user_created_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'user_created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_self_created' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1=account was added by user himself or herself',
            ],
            'user_last_modified_date' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                // 'default' => 'CURRENT_TIMESTAMP',
            ],
            'user_last_modified_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_approval_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_status_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'user_approvers' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'user_password_reset_token' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'user_deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('user_id', true); // Primary Key
        // $this->forge->addUniqueKey(['user_unique_identifier', 'fk_unique_identifier_id', 'fk_account_system_id']);

        $this->forge->createTable('user');
    }

    public function down()
    {
        $this->forge->dropTable('user');
    }
}
