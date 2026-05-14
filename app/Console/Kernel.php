<?php

namespace App\Console;

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
        $purgeDays = (int) config('zakat.retention.purge_days', 30);

        // Permanently delete trashed transactions and muzakki using the configured retention window
        $schedule->command('transactions:purge-trash', ['--days' => $purgeDays])->daily();
        $schedule->command('muzakki:purge-trash', ['--days' => $purgeDays])->daily();
        $schedule->command('audit-logs:purge')->daily();
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
