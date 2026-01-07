<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewNotificationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:logs 
                            {--lines=50 : Number of lines to show from the end of the log}
                            {--follow : Follow the log file for real-time updates}
                            {--filter= : Filter log entries containing this text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View notification logs for debugging email and Pushover issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = storage_path('logs/notifications.log');
        
        if (! File::exists($logPath)) {
            $this->error('Notification log file does not exist yet.');
            $this->info('Send a test notification first to create the log file.');
            return 1;
        }
        
        $lines = (int) $this->option('lines');
        $follow = $this->option('follow');
        $filter = $this->option('filter');
        
        if ($follow) {
            $this->info('Following notification logs (Press Ctrl+C to stop)...');
            $this->info('Log file: ' . $logPath);
            $this->newLine();
            
            // Use tail -f to follow the log
            $command = "tail -f " . escapeshellarg($logPath);
            if ($filter) {
                $command .= " | grep " . escapeshellarg($filter);
            }
            
            passthru($command);
            return 0;
        }
        
        // Read the last N lines
        $this->info("Showing last {$lines} lines from notification logs:");
        $this->info('Log file: ' . $logPath);
        $this->newLine();
        
        $command = "tail -n {$lines} " . escapeshellarg($logPath);
        if ($filter) {
            $command .= " | grep " . escapeshellarg($filter);
        }
        
        $output = shell_exec($command);
        
        if (empty($output)) {
            if ($filter) {
                $this->warn("No log entries found matching filter: {$filter}");
            } else {
                $this->warn('No log entries found.');
            }
            return 0;
        }
        
        // Format and display the output
        $this->line($output);
        
        $this->newLine();
        $this->info('Tip: Use --follow to watch logs in real-time');
        $this->info('Tip: Use --filter="error" to show only errors');
        $this->info('Tip: Use --filter="Pushover" to show only Pushover logs');
        
        return 0;
    }
}
