<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update scheduler heartbeat to indicate scheduler is running';

    /**
     * Execute the console command.
     *
     * Update the scheduler:heartbeat cache key with the current timestamp
     * to indicate that the Laravel scheduler is actively running.
     */
    public function handle(): int
    {
        Cache::put('scheduler:heartbeat', now()->timestamp, 90);
        
        return Command::SUCCESS;
    }
}
