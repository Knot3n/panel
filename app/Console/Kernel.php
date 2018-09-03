<?php

namespace Pterodactyl\Console;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        Log::info('Called schedule checking command!');
//        $schedule->command('p:schedule:process')->everyMinute()->withoutOverlapping();
        $schedule->command('p:maintenance:clean-service-backups')->daily();
    }
}
