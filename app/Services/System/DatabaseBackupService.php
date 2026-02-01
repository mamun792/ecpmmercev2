<?php

namespace App\Services\System;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseBackupService
{
    /**
     * Generate a full SQL dump of the database
     *
     * @return string Path to the generated SQL file
     */
    public function generateBackup(): string
    {
        // Get ALL tables including those without prefixes
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$dbName}";
        
        $sqlDump = "-- Enterprise System Full Backup\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Database: {$dbName}\n";
        $sqlDump .= "-- PHP Version: " . PHP_VERSION . "\n\n";
        
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlDump .= "SET AUTOCOMMIT = 0;\n";
        $sqlDump .= "START TRANSACTION;\n";
        $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Skip views for now or handle them separately (SHOW FULL TABLES WHERE Table_type = 'BASE TABLE')
            // For completeness, we harvest everything SHOW TABLES gives us
            
            // Create Table Structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
            $sqlDump .= "\n\n-- --------------------------------------------------------\n";
            $sqlDump .= "-- Table structure for table `{$tableName}`\n";
            $sqlDump .= "-- --------------------------------------------------------\n\n";
            $sqlDump .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sqlDump .= $createTable->{'Create Table'} . ";\n\n";

            // Export Data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $sqlDump .= "-- Dumping data for table `{$tableName}`\n\n";
                
                // Chunk data into single INSERT statements for performance/readability
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns = array_keys($rowArray);
                    $values = array_map(function($value) {
                        if (is_null($value)) return 'NULL';
                        return DB::getPdo()->quote($value);
                    }, array_values($rowArray));

                    $sqlDump .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            
            Log::info("Backup: Logged table structure and data for {$tableName}");
        }

        $sqlDump .= "\nCOMMIT;\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = "full-infrastructure-backup-" . date("Y-m-d-H-i-s") . ".sql";
        $storagePath = storage_path('app/backups/');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filePath = $storagePath . $filename;
        file_put_contents($filePath, $sqlDump);

        Log::info("Database backup generated: {$filename}");
        
        return $filePath;
    }
}
