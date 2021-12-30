<?php

namespace Msonowal\Audit\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryContract
{
    public function all(array $columns = ['*'], string $orderBy = 'id', string $sortBy = 'asc');

    public function paginate(int $perPage = 15, array $columns = ['*']);

    public static function create(array $attributes);

    public function update(array $data, $id): int;

    public function delete(...$ids): int;

    public function find($id, array $columns = ['*']);

    public function findBy($field, $value, array $columns = ['*']);

    public function findOneOrFail($id);

    public function findOneBy($field, $value, array $columns = ['*']);

    public function findOneByOrFail($field, $value, array $columns = ['*']);

    public function paginateArrayResults(int $perPage = 50, array $columns = ['*']): LengthAwarePaginator;
}
