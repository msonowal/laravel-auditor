<?php

use Msonowal\Audit\Repositories\AuditStatus;
use Msonowal\Audit\Repositories\AuditServiceRepository;

if (! function_exists('audit')) {
    function audit(string $logName = null): AuditServiceRepository
    {
        $defaultLogName = config('mongo-audit.default_log_name');

        $logStatus = app(AuditStatus::class);

        $defaultMode = config('mongo-audit.use_queue', true);

        return app(AuditServiceRepository::class)
            ->useLog($logName ?? $defaultLogName)
            ->setLogStatus($logStatus)
            ->queue($defaultMode);
    }
}
