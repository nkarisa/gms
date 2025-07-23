<?php

namespace App\Libraries\System;


class DatabaseLibrary
{
    public function truncateTables($tablesToTruncate)
    {
        
        $db = \Config\Database::connect();

        // Get the database platform (e.g., MySQLi, PostgreSQL)
        $dbPlatform = $db->getPlatform();

        // Disable foreign key checks based on the database platform
        if ($dbPlatform === 'MySQLi') {
            $db->query('SET FOREIGN_KEY_CHECKS = 0;');
        } elseif ($dbPlatform === 'Postgre') {
            $db->query('SET session_replication_role = replica;');
        } elseif ($dbPlatform === 'SQLite3') {
            $db->query('PRAGMA foreign_keys = OFF;');
        }

        foreach ($tablesToTruncate as $tableName) {
            if ($db->tableExists($tableName)) {
                $db->table($tableName)->truncate();
                // echo "Table '$tableName' truncated successfully.<br>";
            } else {
                echo "Table '$tableName' does not exist.<br>";
            }
        }

        // Re-enable foreign key checks based on the database platform
        if ($dbPlatform === 'MySQLi') {
            $db->query('SET FOREIGN_KEY_CHECKS = 1;');
        } elseif ($dbPlatform === 'Postgre') {
            $db->query('SET session_replication_role = origin;');
        } elseif ($dbPlatform === 'SQLite3') {
            $db->query('PRAGMA foreign_keys = ON;');
        }

        // echo "Finished truncating tables.";
    }
}