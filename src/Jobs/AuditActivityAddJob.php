<?php

namespace Msonowal\Audit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Msonowal\Audit\Repositories\AuditServiceRepository;

class AuditActivityAddJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $attributes;

    /**
     * Create a new job instance.
     */
    public function __construct(array $attributes)
    {
        Log::debug('AuditActivityAddJob __construct START');
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
        Log::debug('AuditActivityAddJob handle START');

        $audit = AuditServiceRepository::create($this->attributes);

        Log::debug('AuditActivityAddJob handle END');
    }
}
