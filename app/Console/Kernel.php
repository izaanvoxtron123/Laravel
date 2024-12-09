<?php

namespace App\Console;

use App\Console\Commands\AssignOfficeIdInCustomers;
use App\Console\Commands\SyncRecordings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('unassignCustomersFromAgents')->lastDayOfMonth();
        $schedule->command('markOfflineInactiveUsers')->everyMinute();
        $schedule->command(SyncRecordings::class)->cron('0 */2 * * *');
        // $schedule->command(AssignOfficeIdInCustomers::class)->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
