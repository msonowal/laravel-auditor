<?php

namespace Msonowal\Audit\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

interface AuditActivityContract
{
    public function subject(): MorphTo;

    public function causer(): MorphTo;

    public function getExtraProperty(string $propertyName);

    public function changes(): Collection;

    public function scopeInLog($query, ...$logNames);

    public function scopeCausedBy($query, Model $causer);

    public function scopeForSubject($query, Model $subject);
}
