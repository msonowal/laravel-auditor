<?php

namespace Msonowal\Audit\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Msonowal\Audit\Contracts\RepositoryContract;
use Msonowal\Audit\Exceptions\AuditActivityNotFoundException;
use Msonowal\Audit\Jobs\AuditActivityAddJob;
use Msonowal\Audit\Models\AuditActivityMoloquent;

class AuditServiceRepository extends AuditLogger implements RepositoryContract
{
    public const DEFAULT_LOG_NAME = 'default';

    /**
     * Adds a entry to the audit store.
     *
     * @param string $description
     *
     * @return AuditServiceRepository
     */
    public function add(string $description): self
    {
        $audit = $this->build($description);

        $attributes = $audit->getAttributes();

        if ($this->queue) {
            dispatch((new AuditActivityAddJob($attributes)));
        //TODO: $jobId to get it
        } else {
            self::create($attributes);
        }

        return $this;
    }

    /**
     * Changes the mode of recording in queue i.e. send it to background process.
     *
     * @param bool $flag
     *
     * @return AuditServiceRepository
     */
    public function queue(bool $flag = true): self
    {
        $this->queue = $flag;

        return $this;
    }

    /**
     * Returns the added Model that is added via add method.
     *
     * @return mix|AuditActivityMoloquent
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Maybe will make it private or protected as we might not want it to make entry directly.
     *
     * @param array $attributes
     *
     * @return void
     */
    public static function create(array $attributes)
    {
        $audit = AuditActivityMoloquent::create($attributes);
        self::fireAddedEvent($audit);

        return $audit;
    }

    protected static function fireAddedEvent(AuditActivityMoloquent $audit): void
    {
        event(new \Msonowal\Audit\Events\AuditAddedEvent($audit));
    }

    /**
     * @param Model $model
     *
     * @throws AuditActivityNotFoundException
     *
     * @return Collection
     */
    public function findByCauser($model): Collection
    {
        return AuditActivityMoloquent::causedBy($model)->get();
    }

    /**
     * Returns the query builder instance to retrive the records from the db engine.
     *
     * @return void
     */
    public function getQueryBuilder()
    {
        return \DB::connection(
            config('mongo-audit.connection_name')
        )->collection(
            config('mongo-audit.collection_name')
        );
    }

    /**
     * Retrieves all the records from the storage.
     *
     * @param array  $columns
     * @param string $orderBy
     * @param string $sortBy
     *
     * @return Collection
     */
    public function all(array $columns = ['*'], string $orderBy = 'id', string $sortBy = 'asc'): Collection
    {
        return $this->getQueryBuilder()
            ->get($columns);
    }

    /**
     * This will return all the records in paginated format.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function paginateArrayResults(int $perPage = 50, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->getQueryBuilder()
            ->paginate($perPage, $columns);
    }

    /**
     * This will transform the plain array attributes to model objects.
     *
     * @param LengthAwarePaginator $results
     *
     * @return LengthAwarePaginator
     */
    public function hydratePaginatedData($results): LengthAwarePaginator
    {
        $results = clone $results; //just a functional approach
        $hydrated = AuditActivityMoloquent::hydrate($results->getCollection()->toArray());

        $results->setCollection($hydrated);

        return $results;
    }

    /**
     * This will return all the model in pagianted object.
     *
     * @param int   $perPage
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 50, array $columns = ['*']): LengthAwarePaginator
    {
        //as mongo paginate not working from the model so we are using raw builder to retrieve the data then hydrate it as a model instance
        $results = $this->paginateArrayResults($perPage, $columns);

        $results = $this->hydratePaginatedData($results);

        return $results;
    }

    /**
     * This should return a Moloquent Object by id with the specified fields.
     *
     * @param \MongoDB\BSON\ObjectId $id
     * @param array                  $columns
     *
     * @return AuditActivityMoloquent
     */
    public function find($id, array $columns = ['*'])
    {
        return AuditActivityMoloquent::find($id, $columns);
    }

    /**
     * This should return a Moloquent Object by the $field and $value
     * with the specified fields.
     *
     * @param \MongoDB\BSON\ObjectId $id
     * @param array                  $columns
     *
     * @return Collection
     */
    public function findBy($field, $value, array $columns = ['*']): Collection
    {
        return AuditActivityMoloquent::where($field, $value)
                ->get($columns);
    }

    /**
     * This will return the Model given by $ObjectId/id.
     *
     * @param [type] $id
     *
     * @throws AuditActivityNotFoundException
     *
     * @return AuditActivityMoloquent
     */
    public function findOneOrFail($id): AuditActivityMoloquent
    {
        try {
            return AuditActivityMoloquent::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            throw AuditActivityNotFoundException::couldNotFindRecordById($id);
        }
    }

    /**
     * This will return the first object that mathces the criteria i.e. fiedl and value.
     *
     * @param [type] $field
     * @param [type] $value
     * @param array $columns
     *
     * @return AuditActivityMoloquent //maybe null //TODO: test return type
     */
    public function findOneBy($field, $value, array $columns = ['*']): ?AuditActivityMoloquent
    {
        return AuditActivityMoloquent::where($field, $value)
                ->first($columns);
    }

    /**
     * This will return the first matched object by given $field and $value
     * or throws an ModelNotFoundException.
     *
     * @param [type] $field
     * @param [type] $value
     * @param array $columns
     *
     * @throws Illuminate/Database/Eloquent/ModelNotFoundException
     *
     * @return AuditActivityMoloquent
     */
    public function findOneByOrFail($field, $value, array $columns = ['*']): AuditActivityMoloquent
    {
        return AuditActivityMoloquent::where($field, $value)
                ->firstOrFail($columns);
    }

    /**
     * Retrieves records by description that matches the given text.
     *
     * @param string $text
     *
     * @return Collection
     */
    public function search(string $text): Collection
    {
        if (!empty($text)) {
            return $this->getQueryBuilder()
                ->where('description', 'like', $text) //mongodb regex needs to be improved
                ->get();
        } else {
            return $this->all();
        }
    }

    /**
     * Performs an update to the model by id and update the attributes only
     * we can also pass additional options like upsert to true or any other options as native operations.
     *
     * @param array $data
     * @param [type] $id
     * @param array $options
     *
     * @return int returns the number of effected records
     */
    public function update(array $data, $id, array $options = []): int
    {
        return AuditActivityMoloquent::where('_id', $id)
                    ->update($data, $options);
    }

    // public function transform()
    // {

    // }

    /**
     * Deletes all the records that matches the given ids
     * and returns number of records deleted count.
     *
     * @param \MongoDB\BSON\ObjectId ...$ids
     *
     * @return int
     */
    public function delete(...$ids): int
    {
        return AuditActivityMoloquent::destroy($ids);
    }

    /**
     * Deletes the record that is older than specified days.
     *
     * @param string $logName
     * @param int    $maxAgeInDays
     * @param  string|null TODO: based on this we will decide the strategy
     *
     * @return int
     */
    public function deleteRecordsOlderThan(?string $logName = self::DEFAULT_LOG_NAME, int $maxAgeInDays = 365, ?string $type = null): int
    {
        //TODO: delete only if the limit of the logname exceeds some number of entries
        //i.e. will need to set to keep atleast 1,00,000
        //so if the entry falls beyond the above value and older than the maxAge then those criteria
        //records will be selected
        $cutOffDate = Carbon::now()->subDays($maxAgeInDays);

        $amountDeleted = AuditActivityMoloquent::where('created_at', '<', $cutOffDate)
            ->when(
                $logName !== null,
                function ($query) use ($logName) {
                    $query->inLog($logName);
                }
            )
            ->delete();

        return $amountDeleted;
    }
}
