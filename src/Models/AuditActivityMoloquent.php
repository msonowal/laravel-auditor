<?php

namespace Msonowal\Audit\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Moloquent;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Msonowal\Audit\Contracts\AuditActivityContract;
use Jenssegers\Mongodb\Eloquent\HybridRelations;

class AuditActivityMoloquent extends Moloquent implements AuditActivityContract
{
    public $guarded = [];

    use HybridRelations;

    public function __construct(array $attributes = [])
    {
        $this->setDBConnectionName(
            config('mongo-audit.connection_name')
        );

        $this->setDBCollectionName(
            config('mongo-audit.collection_name')
        );

        parent::__construct($attributes);
    }

    public function setDBConnectionName(string $connectionName)
    {
        $this->connection = $connectionName;
    }

    public function setDBCollectionName(string $collectionName)
    {
        $this->collection = $collectionName;
    }

    public function subject(): MorphTo
    {
        if (config('mongo-audit.subject_returns_soft_deleted_models')) {
            return $this->morphTo()->withTrashed();
        }

        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getExtraProperty(string $propertyName)
    {
        return array_get($this->properties, $propertyName);
    }

    public function changes(): Collection
    {
        if (! is_array($this->properties)) {
            return new Collection();
        }

        return collect($this->properties)->only(['attributes', 'old']);
    }

    public function scopeInLog($query, ...$logNames)
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    public function scopeCausedBy($query, Model $causer)
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }
}
