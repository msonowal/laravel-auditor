<?php

namespace Msonowal\Audit\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Msonowal\Audit\Models\AuditActivityMoloquent;

class AuditAddedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AuditActivityMoloquent $model)
    {
        $this->model  =   $model;
    }
}
