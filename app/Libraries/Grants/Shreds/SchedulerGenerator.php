<?php 

namespace App\Libraries\Grants\Shreds;

interface SchedulerGenerator {
    public function scheduledGenerator(int $officeId, string $generationMonth):array;
    public function getActiveTransactingOffices(): array;
}