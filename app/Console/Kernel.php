<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\ActivityModelsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('events:run')->dailyAt('06:00');
        $schedule->command('marketing:send-follow-ups')->dailyAt('07:00');
        $schedule->command('marketing:send-wo-estimate-date-emails')->dailyAt('07:20');
        $schedule->command('marketing:send-wo-file-emails')->everyTenMinutes();
        $schedule->command('db:backup')->dailyAt('00:00');
        $schedule->command('activitylog:clean')->dailyAt('01:00');
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
