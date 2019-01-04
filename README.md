# Record your audit activity logs in Mongo DB on your Laravel project

[![Latest Version on Packagist](https://img.shields.io/packagist/v/msonowal/laravel-auditor.svg?style=flat-square)](https://packagist.org/packages/msonowal/laravel-auditor)
[![Build Status](https://img.shields.io/travis/msonowal/laravel-auditor/master.svg?style=flat-square)](https://travis-ci.org/msonowal/laravel-auditor)
[![Quality Score](https://img.shields.io/scrutinizer/g/msonowal/laravel-auditor.svg?style=flat-square)](https://scrutinizer-ci.com/g/msonowal/laravel-auditor)
[![StyleCI](https://styleci.io/repos/61802818/shield)](https://styleci.io/repos/61802818)
[![Total Downloads](https://img.shields.io/packagist/dt/msonowal/laravel-auditor.svg?style=flat-square)](https://packagist.org/packages/msonowal/laravel-auditor)

A simple package to track, record and log changes of your laravel apps events and also
Eloquent Models by Polymorphic relations. By Default, the Package stores all audit activity 
in the `audit_logs` collection in the Mongo DB. However you can customize everything via config.
This package uses `jenssegers/mongodb` for interacting with Mongo DB.
By Default Users Ip address and User Agents are Captured for every request if it is performed by Users.

## Installation

```bash
$ composer require msonowal/laravel-auditor
```

The package will automatically register itself.

## Configuration
You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="Msonowal\Audit\AuditServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
[

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
     * By default all the activities will be processed via queue
     * If set to false, all the activities will be processed instantly.
     */
    'use_queue' => env('AUDIT_MODE', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified above and if its beyond the max entries limit
     * those records will be deleted and mostly on the specified log name
     */
    'max_entries' => 50000,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => env('AUDIT_DEFAULT_LOG_NAME', 'default'),

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * This is the name of the database connection that will be used by the migration and
     * used by the Services.
     */
    'connection_name' => env('AUDIT_CONNECTION', 'activity'),
    /*
     * This is the name of the collection that will be created by the migration and
     * used by the Activity model.
     */
    'collection_name' => env('AUDIT_COLLECTION_NAME', 'activity_logs'),
];
```

You can publish the migration with:
```bash
php artisan vendor:publish --provider="Msonowal\Audit\AuditServiceProvider" --tag="migrations"
```

*Note*: The default migration adds the indexes to the collection for the essentials fields however you can modify and tailor upto your needs.

After publishing the migration you can update the indexes on the `audit_logs` collection by running the migrations:


```bash
php artisan migrate
```

Here's a demo of how you can use it:

```php
audit()->log('Something, has been done');
```

You can retrieve all activity using the `AuditServiceRepository` class.

Inject in your methods
```php
function test(AuditServiceRepository $audit)
{
    $audit->all(); //this returns all the records in plain array directly from DB

    $audit->paginate(); //this returns all the records as a Model Instance with default 50 per page and all the fields
}
```

Here's a more advanced example:
```php
audit()
   ->performedOn($anEloquentModel)
   ->causedBy($user)
   ->withProperties(['customProperty' => 'customValue'])
   ->add('Something, has been done');
   
$lastLoggedActivity = AuditActivityMoloquent::all()->last();

$lastLoggedActivity->subject; //returns an instance of an eloquent model
$lastLoggedActivity->causer; //returns an instance of your user model
$lastLoggedActivity->getExtraProperty('customProperty'); //returns 'customValue'
$lastLoggedActivity->description; //returns 'Look, I logged something'
```


## Version
According to the composer docs the [version](https://getcomposer.org/doc/04-schema.md#version):

>We will follow the format of X.Y.Z or vX.Y.Z with an optional suffix of
>-dev, -patch (-p), -alpha (-a), -beta (-b) or -RC. The patch, alpha, beta and
>RC suffixes can also be followed by a number.
>Examples:
> * 1.0.0
> * 1.0.2
> * 0.1.0
> * 0.2.5
> * 1.0.0-dev
> * 1.0.0-alpha3
> * 1.0.0-beta2
> * 1.0.0-RC5
> * v2.0.4-p1


Testing
After install the dependencies you can run all the tests by excecuting the follow command:

```bash
$ vendor/bin/phpunit
```

The output should look similar to this:

```bash
.                                                                  1 / 1 (100%)

Time: 84 ms, Memory: 12.00MB

OK (1 test, 1 assertion)


```

All the test files should be inside the `tests/` directory. Here is an example:

```php

<?php

namespace Msonowal\Audit\Tests\Unit;

use Msonowal\Audit\Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function example_test_method()
    {
        $this->assertTrue(true);
    }
}

```

Have fun! 🎊

## Credits

## TODO:
Make the model event queable via config
Make It more configurable
Add Tests

This package was inspired by their work on [spatie/activitylog](https://github.com/spatie/activitylog) a package to use log activity in laravel in mysql or supported db by laravel.
