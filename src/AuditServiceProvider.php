<?php

namespace Msonowal\Audit;

use Illuminate\Database\Eloquent\Model;
use Msonowal\Audit\Commands\CleanAuditActivityLogCommand;
use Msonowal\Audit\Models\AuditActivityMoloquent;
use Msonowal\Audit\Repositories\AuditServiceRepository;
use Msonowal\Audit\Repositories\AuditStatus;

class AuditServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->publishes(
            [
                __DIR__.'/../config/mongo-audit.php' => config_path('mongo-audit.php'),
            ],
            'config'
        );

        $this->mergeConfigFrom(__DIR__.'/../config/mongo-audit.php', 'mongo-audit');

        if (!class_exists('AddAuditIndexesToCollection')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes(
                [
                    __DIR__.'/../migrations/add_audit_indexes_to_collection.php.stub' => database_path("/migrations/{$timestamp}_add_audit_indexes_to_collection.php"),
                ],
                'migrations'
            );
        }
    }

    public function register()
    {
        $this->app->singleton(AuditStatus::class);

        $this->app->bind(AuditServiceRepository::class);

        //only register if on cli to reduce application boot time
        if ($this->app->runningInConsole()) {
            $this->app->bind('command.audit:clean', CleanAuditActivityLogCommand::class);

            $this->commands(
                [
                    'command.audit:clean',
                ]
            );
        }
    }

    public static function determineActivityModel(): string
    {
        //TODO: can be made dynamic if the repository implementaions are updated
        return AuditActivityMoloquent::class;
    }

    public static function getActivityModelInstance(): Model
    {
        $activityModelClassName = self::determineActivityModel();

        return new $activityModelClassName();
    }
}
