# JohannesSchhobel/Laravel-Revisionable

Nice and easy way to handle revisions of your db.

* Handles the revisions in **bulk** - one entry covers all the created/updated fields, what makes it really **easy to eg. compare 2 given versions** or get all the data changed during single action.


## Requirements

* This package requires PHP 5.4+
* Currently it works out of the box with Laravel5.4 + generic Illuminate Guard, tymon/jwt-auth OR cartalyst/sentry 2/sentinel 2


## Usage (Laravel5 basic example - see Customization below as well)

### 1. Download the package or require in your `composer.json`:

```
composer require sofa/revisionable
```

### 2. Add the service provider to your `app/config/app.php`:

```php
    'providers' => array(

        ...

        'Sofa\Revisionable\Laravel\ServiceProvider',
    ),
```

### 3. Publish the package config file:

```
~$ php artisan vendor:publish [--provider="Sofa\Revisionable\Laravel\ServiceProvider"]
```

this will create `config/sofa_revisionable.php` file, where you can adjust a few settings:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User model (for executor relation on Revision model).
    |--------------------------------------------------------------------------
    |
    | By default App\User.
    */
    'usermodel' => 'App\User',

    /*
    |--------------------------------------------------------------------------
    | User provider (auth) implementation.
    |--------------------------------------------------------------------------
    |
    | By default Laravel generic Illuminate\Auth\Guard.
    |
    | Supported options:
    |  - illuminate
    |  - sentry
    |  - sentinel
    |  - jwt-auth
    |  - session 
    */
    'userprovider' => 'illuminate',


    /*
    |--------------------------------------------------------------------------
    | User field to be saved as the author of tracked action.
    |--------------------------------------------------------------------------
    |
    | By default:
    |
    |  - id for illuminate
    |  - login field (email) for sentry/sentinel
    */
    'userfield' => 'id',


    /*
    |--------------------------------------------------------------------------
    | Table used for the revisions.
    |--------------------------------------------------------------------------
    */
    'table' => 'revisions',


    /*
    |--------------------------------------------------------------------------
    | Database connection used for the revisions.
    |--------------------------------------------------------------------------
    */
    'connection' => null,

];
```


### 4. Run the migration in order to create the revisions table:

```
~$ php artisan migrate
```

You can provide additional `--database` param if you want the migration to be run using non-default db connection.


### 5. Add revisionable trait to the models you wish to keep track of:

```php
<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Sofa\Revisionable\Laravel\Revisionable; // trait

class User extends Model
{
    use Revisionable;

    /*
     * Set revisionable whitelist - only changes to any
     * of these fields will be tracked during updates.
     */
    protected $revisionable = [
        'email',
        'name',
    ];
```


And that's all to get your started!


## Customization in L5

**Default behaviour**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Revisionable\Laravel\Revisionable;

class Ticket extends Model
{
    use Revisionable;
}
```

If you want to disable revisions for a specific model just add the following variables to your model
```php
protected $revisionEnabled = false;
```

You can further specify, how many revisions of a given model shall be kept (default value is set to 20). Furthermore,
you may customize, if "older" revisions shall be deleted if the limit is reached. You may customize this behaviour for
each model by changing the variables
```php
protected $revisionLimit = 50;  // keep 50 instead of 20 revisions of this model
protected $revisionLimitCleanup = false; // do not cleanup older models - this will result in not storing additional revisions!
```

```php
$ php artisan tinker

>>> $ticket = App\Models\Ticket::first();
=> <App\Models\Ticket>

>>> $revision->getDiff();
=> [
       "customer_id"    => [
           "old" => "1",
           "new" => "101"
       ],
       "item_id"        => [
           "old" => "2",
           "new" => "1"
       ],
       "responsible_id" => [
           "old" => "8",
           "new" => "2"
       ]
   ]

>>> $revision->old('item_id');
=> "2"

>>> $revision->new('item_id');
=> "1"

>>> $revision->isUpdated('item_id');
=> true

>>> $revision->isUpdated('note');
=> false

>>> $revision->label('item_id');
=> "item_id"

>>> $revision->old;
=> [
       "defect"         => "nie dziala",
       "note"           => "wrocilo na gwarancji",
       "customer_id"    => "1",
       "item_id"        => "2",
       "responsible_id" => "8",
       "status_id"      => "6"
   ]

>>> $revision->action;
=> "updated"
```

But here's where you can leverage bundled `Presenter` in order to make useful adjustments:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Revisionable\Laravel\Revisionable;

class Ticket extends Model
{
    use Revisionable;

    protected $revisionPresenter = 'App\Presenters\Revisions\Ticket';
}
```

```php
namespace App\Presenters\Revisions;

use Sofa\Revisionable\Laravel\Presenter;

class Ticket extends Presenter
{
    protected $labels = [
        'item_id'        => 'Przedmiot',
        'customer_id'    => 'Klient',
        'status_id'      => 'Status',
        'responsible_id' => 'Serwisant',
        'defect'         => 'Usterka',
        'note'           => 'Uwagi',
    ];

    protected $passThrough = [
        'item_id'        => 'item.name',
        'customer_id'    => 'customer.name',
        'responsible_id' => 'serviceman.name',
        'status_id'      => 'status.name',
    ];

    protected $actions = [
        'created'  => 'utworzony',
        'updated'  => 'edytowany',
        'deleted'  => 'usunięty',
        'restored' => 'przywrócony',
    ];

}
```

then
```
$ php artisan tinker

>>> $ticket = App\Models\Ticket::first();
=> <App\Models\Ticket>

>>> $revision->old('item_id'); // value fetched from the relationship
=> "komputer pc"

>>> $revision->new('item_id'); // value fetched from the relationship
=> "laptop acer"

>>> $revision->label('item_id'); // custom label defined in the presenter
=> "Przedmiot"

>>> $revision->action; // custom action name defined in the presenter
=> "edytowany"
```
