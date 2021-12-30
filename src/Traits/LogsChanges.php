<?php

namespace Msonowal\Audit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Msonowal\Audit\AuditServiceProvider;
use Msonowal\Audit\Repositories\AuditServiceRepository;

trait LogsChanges
{
    use DetectChanges;
    use MorphManyWithoutCollection;

    protected $enableLoggingModelsEvents = true;

    protected $enableLoggingInQueue = true;

    protected static function bootLogsChanges()
    {
        static::eventsToBeRecorded()->each(
            function ($eventName) {
                return static::$eventName(
                    function (Model $model) use ($eventName) {
                        if (!$model->shouldLogEvent($eventName)) {
                            return;
                        }

                        $description = $model->getDescriptionForEvent($eventName);

                        $logName = $model->getLogNameToUse($eventName);

                        if ($description == '') {
                            return;
                        }

                        $attrs = $model->attributeValuesToBeLogged($eventName);

                        if ($model->isLogEmpty($attrs) && !$model->shouldSubmitEmptyLogs()) {
                            return;
                        }

                        app(AuditServiceRepository::class)
                            ->useLog($logName)
                            ->performedOn($model)
                            ->withProperties($attrs)
                            ->queue($model->enableLoggingInQueue)
                            ->add($description);
                    }
                );
            }
        );
    }

    public function shouldSubmitEmptyLogs(): bool
    {
        return !isset(static::$submitEmptyLogs) ? true : static::$submitEmptyLogs;
    }

    public function isLogEmpty($attrs): bool
    {
        return empty($attrs['attributes'] ?? []) && empty($attrs['old'] ?? []);
    }

    public function disableLogging()
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    public function enableLogging()
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }

    public function activities(): MorphMany
    {
        return $this->morphManyWithoutCollection(AuditServiceProvider::determineActivityModel(), 'subject');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return $eventName;
    }

    public function getLogNameToUse(): string
    {
        if (isset(static::$logName)) {
            return static::$logName;
        }

        return config('mongo-audit.default_log_name');
    }

    /*
     * Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded(): Collection
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect(
            [
                'created',
                'updated',
                'deleted',
            ]
        );

        if (collect(class_uses_recursive(static::class))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }

    public function attributesToBeIgnored(): array
    {
        if (!isset(static::$ignoreChangedAttributes)) {
            return [];
        }

        return static::$ignoreChangedAttributes;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        if (!$this->enableLoggingModelsEvents) {
            return false;
        }

        if (!in_array($eventName, ['created', 'updated'])) {
            return true;
        }

        if (Arr::has($this->getDirty(), 'deleted_at')) {
            if ($this->getDirty()['deleted_at'] === null) {
                return false;
            }
        }

        //do not log update event if only ignored attributes are changed
        return (bool) count(array_except($this->getDirty(), $this->attributesToBeIgnored()));
    }
}
