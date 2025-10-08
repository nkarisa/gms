<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Tasks.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Tasks\Config\Tasks as BaseTasks;
use CodeIgniter\Tasks\Scheduler;
use App\Libraries\System\ScheduledTasks;

class Tasks extends BaseTasks
{
    /**
     * --------------------------------------------------------------------------
     * Should performance metrics be logged
     * --------------------------------------------------------------------------
     *
     * If true, will log the time it takes for each task to run.
     * Requires the settings table to have been created previously.
     */
    public bool $logPerformance = false;

    /**
     * --------------------------------------------------------------------------
     * Maximum performance logs
     * --------------------------------------------------------------------------
     *
     * The maximum number of logs that should be saved per Task.
     * Lower numbers reduced the amount of database required to
     * store the logs.
     */
    public int $maxLogsPerTask = 10;

    /**
     * Register any tasks within this method for the application.
     * Called by the TaskRunner.
     */
    public function init(Scheduler $schedule)
    {
        $tasksLibrary = new ScheduledTasks();

        // Tasks to run schedulers - Run every 5 minutes

        $schedule->call(static function () use($tasksLibrary) {
            // Create payrolls for all offices
            $payrollCreator = new \App\Libraries\Grants\Shreds\PayrollCreator(); // A type of SchedulerGenerator interface
            $response = $tasksLibrary->scheduler($payrollCreator, 'payrollOfficeIds');
            // log_message('info', json_encode($response));
        })->everyFiveMinutes()->named('autoCreatePayrolls');

        $schedule->call(static function () use($tasksLibrary) {
            // Create Depreciation vouchers
            $depreciationVoucherGenerator = new \App\Libraries\Grants\Shreds\DepreciationVoucherCreator(); // A type of SchedulerGenerator interface
            $response = $tasksLibrary->scheduler($depreciationVoucherGenerator, 'depreciationOfficeIds');
            // log_message('info', json_encode($response));
        })->everyFiveMinutes()->named('autoCreateDepreciationVouchers');

        // Tasks to dump Office Ids to Cache - Run every minute

        $schedule->call(static function() use($tasksLibrary) {
            $payrollCreator = new \App\Libraries\Grants\Shreds\PayrollCreator();
            $response = $tasksLibrary->cacheOfficeIds($payrollCreator, 'payrollOfficeIds');
            // log_message('info', json_encode($response));
        })->everyMinute()->named('cachePayrollOfficeIds');

        $schedule->call(static function() use($tasksLibrary) {
            $depreciationVoucherGenerator = new \App\Libraries\Grants\Shreds\DepreciationVoucherCreator();
            $response = $tasksLibrary->cacheOfficeIds($depreciationVoucherGenerator, 'depreciationOfficeIds');
            // log_message('info', json_encode($response));
        })->everyMinute()->named('cacheDepreciationOfficeIds');

    }
}
