<?php

namespace Msonowal\Audit\Commands;

use Illuminate\Console\Command;
use Msonowal\Audit\Repositories\AuditServiceRepository;

class CleanAuditActivityLogCommand extends Command
{
    protected $signature = 'audit:clean
                            {log? : (optional) The log name that will be cleaned.}
                            {--type=old : The strategy for deletion criteria.}';

    protected $description = 'Clean up old records from the audits activity log.';

    public function handle(AuditServiceRepository $auditor)
    {
        $this->comment('Cleaning audit activity logs...');

        $log = $this->argument('log');
        
        $type       =   $this->option('type');
        
        $maxAgeInDays = config('mongo-audit.delete_records_older_than_days');

        $auditor->deleteRecordsOlderThan($log, $maxAgeInDays, $type);

        // $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('Audit activity clean done!');
    }
}
