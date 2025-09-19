<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use Illuminate\Console\Command;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--days=365 : Number of days to keep audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit logs to maintain database performance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning up audit logs older than {$days} days...");
        
        $deletedCount = AuditService::cleanup($days);
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old audit log entries.");
        } else {
            $this->info("No old audit logs found to clean up.");
        }
        
        return Command::SUCCESS;
    }
}
