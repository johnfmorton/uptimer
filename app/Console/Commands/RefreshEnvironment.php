<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh environment configuration after .env file changes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Refreshing environment configuration...');
        
        // Clear all caches
        $this->call('optimize:clear');
        
        // Re-cache configuration for performance (production only)
        if (app()->environment('production')) {
            $this->info('Re-caching configuration for production...');
            $this->call('config:cache');
        }
        
        $this->info('✅ Environment configuration refreshed successfully!');
        $this->line('');
        $this->line('Your .env changes are now active.');
        
        return self::SUCCESS;
    }
}