<?php 

namespace App\Libraries\System;

use CodeIgniter\Database\BaseConnection;

class ScheduledTasks {

    protected BaseConnection $read_db, $write_db;
    function __construct()
    {
        $this->read_db = \Config\Database::connect('read');
        $this->write_db = \Config\Database::connect('write');
    }


    public function getOfficeIds($cacheKey)
    {        
        // Retrieve the data from Redis
        $cachedIds = cache()->get($cacheKey);

        if ($cachedIds !== null) {
            // Data found in cache
            $count = count($cachedIds);
            return [
                'status' => 'success', 
                'source' => 'redis',
                'count' => $count,
                'data' => $cachedIds
            ];
        }

        return null;
        
    }
    
    public function cacheOfficeIds(\App\Libraries\Grants\Shreds\SchedulerGenerator $scheduledObject, string $cacheKey, $ttl = 300)
    {
        $offices = $scheduledObject->getActiveTransactingOffices();
        $success = false;

        $officeIds = [];
        if(!empty($offices)){
            $officeIds = array_column($offices, 'office_id');
            $transactingDates = array_map(
                fn($office) => 
                    $office['financial_report_is_submitted'] == 0
                    ? $office['financial_report_month']
                    : date('Y-m-01', strtotime('first day of previous month', strtotime($office['financial_report_month'])))
                , $offices
            ); 

            $cacheData = array_combine($officeIds, $transactingDates);

            $success = cache()->save($cacheKey, $cacheData, $ttl);
        }

        if ($success) {
            $message = "Successfully saved " . count($officeIds) . " office IDs to Redis under the key: '{$cacheKey}'. Data will expire in {$ttl} seconds.";
            return ['status' => 'success', 'message' => $message];
        } else {
            return ['status' => 'error', 'message' => 'Failed to save data to Redis. Check Redis connection.'];
        }
        
    }

    public function scheduler(\App\Libraries\Grants\Shreds\SchedulerGenerator $scheduledObject, $officeCacheKey): array|null{
        // Get All active FCPs
        $activeTransactingOfficesResponse = $this->getOfficeIds($officeCacheKey);
        
        if($activeTransactingOfficesResponse == null){
            return null;
        }
        
        foreach($activeTransactingOfficesResponse['data'] as $officeId => $transactingDate){
            $response = $scheduledObject->scheduledGenerator($officeId, $transactingDate);
        }

        return $response;
    }
}