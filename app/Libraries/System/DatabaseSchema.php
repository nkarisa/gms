<?php 
namespace App\Libraries\System;

class DatabaseSchema {
    private static $instance = null;
    private $schema = [];

    private function __construct($db) {
        
        $tables = $db->listTables();
        $schema = [];
        foreach($tables as $table){
            $schema[$table]['lookup_tables'] = array_column($db->getForeignKeyData($table),'foreign_table_name');
            $schema[$table]['field_data'] = $db->getFieldData($table);
        }

        $this->schema = $schema;
    }

    public static function getInstance($db) {
        if (self::$instance === null) {
            self::$instance = new DatabaseSchema($db);
        }
        return self::$instance;
    }

    function getDatabaseSchema(){
        return $this->schema;
    }
}