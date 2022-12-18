<?php

namespace App\Console;

use App\Console\Commands\DealsCheckVolume;
use App\Console\Commands\DealsCurl;
use App\Console\Commands\DealsSearchNewDeal;
use App\Console\Commands\TestStage1;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Http\Controllers\fgislk_bot\Deals;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        TestStage1::class,
        DealsCheckVolume::class,
        DealsCurl::class,
        DealsSearchNewDeal::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DealsCurl::class)->everySixHours()->withoutOverlapping();
        $schedule->command(DealsCheckVolume::class)->everyThreeHours()->withoutOverlapping();
        $schedule->command(DealsSearchNewDeal::class)->everyThreeHours()->withoutOverlapping();
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
