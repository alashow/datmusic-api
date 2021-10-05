<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Console;

use App\Console\Commands\ExportAudioCacheCommand;
use App\Console\Commands\MinervaConfigureMeilisearchCommand;
use App\Console\Commands\MinervaMeilisearchIndexCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ExportAudioCacheCommand::class,
        MinervaMeilisearchIndexCommand::class,
        MinervaConfigureMeilisearchCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
    }
}
