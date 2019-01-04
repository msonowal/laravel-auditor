<?php

namespace Msonowal\Audit\Exceptions;

class CouldNotLogActivity extends \Exception
{
    public static function couldNotDetermineUser($id)
    {
        return new static("Could not determine a causer with identifier `{$id}`.");
    }

    public static function invalidAttribute($attribute)
    {
        return new static("Cannot log attribute `{$attribute}`. Can only log attributes of a model or a directly related model.");
    }
}
