<?php

namespace Msonowal\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Msonowal\Audit\Repositories\AuditServiceRepository;

class AuditActivityAddJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $attributes;

    /**
     * Create a new job instance.
     */
    public function __construct(array $attributes)
    {
        // Log::debug('AuditActivityAddJob __construct START');
        $this->onQueue(config('system.queues.default'));
        $this->attributes = $attributes;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        // Log::debug('AuditActivityAddJob handle START');

        $audit = AuditServiceRepository::create($this->attributes);

        // Log::debug('AuditActivityAddJob handle END');
    }
}
