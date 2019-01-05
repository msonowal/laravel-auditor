<?php

namespace Msonowal\Audit\Repositories;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Msonowal\Audit\AuditServiceProvider;
use Msonowal\Audit\Contracts\AuditActivityContract;
use Msonowal\Audit\Exceptions\CouldNotLogActivity;

class AuditLogger
{
    use Macroable;

    /**
     * Whether to use queue for processing the events or not.
     *
     * @var bool
     */
    protected $queue;

    /**
     * Contains ip_address and user_agent details.
     *
     * @var array
     */
    protected $requestInfos = null;

    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    protected $logName = '';

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $performedOn;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $causedBy;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var string
     */
    protected $authDriver;

    /**
     * @var \Msonowal\Audit\Repositories\AuditStatus
     */
    protected $logStatus;

    /**
     * Initialize the logger object with the configuration.
     *
     * @param Request     $request
     * @param AuthManager $auth
     * @param Repository  $config
     * @param AuditStatus $logStatus
     */
    public function __construct(Request $request, AuthManager $auth, Repository $config, AuditStatus $logStatus)
    {
        $this->queue = $config['mongo-audit']['mode'] ?? true;

        $this->captureRequestInfos($request);

        $this->auth = $auth;

        $this->properties = [];

        $this->authDriver = $auth->getDefaultDriver();

        $this->causedBy = $auth->guard($this->authDriver)->user();

        $this->logName = $config['mongo-audit']['default_log_name'];

        $this->logEnabled = $config['mongo-audit']['enabled'] ?? true;

        $this->logStatus = $logStatus;
    }

    public function captureRequestInfos(Request $request): self
    {
        //i.e. only capturing if it is resolved that is made by user
        // and not from the automated systems or events or from a queue worker
        //default ip will be local and user agent will be Symfony/3.X
        //as the Symfony requets sets as default on initialization
        if (($request->header('User-Agent') != 'Symfony/3.X') || ($request->ip() != '127.0.0.1')) {
            $this->requestInfos = [
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->header('User-Agent'),
            ];
        }

        return $this;
    }

    public function setLogStatus(AuditStatus $logStatus): self
    {
        $this->logStatus = $logStatus;

        return $this;
    }

    public function performedOn(Model $model): self
    {
        $this->performedOn = $model;

        return $this;
    }

    public function on(Model $model): self
    {
        return $this->performedOn($model);
    }

    public function causedBy($modelOrId): self
    {
        if ($modelOrId === null) {
            return $this;
        }

        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;

        return $this;
    }

    public function by($modelOrId): self
    {
        return $this->causedBy($modelOrId);
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function withProperty(string $key, $value): self
    {
        $this->properties->put($key, $value);

        return $this;
    }

    public function useLog(string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    public function inLog(string $logName): self
    {
        return $this->useLog($logName);
    }

    public function enableLogging(): self
    {
        $this->logStatus->enable();

        return $this;
    }

    public function disableLogging(): self
    {
        $this->logStatus->disable();

        return $this;
    }

    /**
     * This builds/makes the Model structure for recording the event/activity.
     *
     * @param string $description
     *
     * @return AuditActivityContract|null
     */
    protected function build(string $description):? AuditActivityContract
    {
        if ($this->logStatus->disabled()) {
            return null;
        }

        $activity = AuditServiceProvider::getActivityModelInstance();

        if ($this->performedOn) {
            $activity->subject()->associate($this->performedOn);
        }

        if ($this->causedBy) {
            $activity->causer()->associate($this->causedBy);
        }

        $activity->properties = $this->properties;

        $activity->description = $this->replacePlaceholders($description, $activity);

        $activity->log_name = $this->logName;

        if (!is_null($this->requestInfos)) {
            $activity->request_infos = $this->requestInfos;
        }

        $attributes->created_at = Carbon::now(); //setting the timestamp here only for actual event time tracking

        return $activity;
    }

    protected function normalizeCauser($modelOrId): Model
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        $model = $this->auth->guard($this->authDriver)->getProvider()->retrieveById($modelOrId);

        if ($model) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($modelOrId);
    }

    protected function replacePlaceholders(string $description, AuditActivityContract $activity): string
    {
        return preg_replace_callback(
            '/:[a-z0-9._-]+/i',
            function ($match) use ($activity) {
                $match = $match[0];

                $attribute = (string) string($match)->between(':', '.');

                if (!in_array($attribute, ['subject', 'causer', 'properties'])) {
                    return $match;
                }

                $propertyName = substr($match, strpos($match, '.') + 1);

                $attributeValue = $activity->$attribute;

                if (is_null($attributeValue)) {
                    return $match;
                }

                $attributeValue = $attributeValue->toArray();

                return array_get($attributeValue, $propertyName, $match);
            },
            $description
        );
    }
}
