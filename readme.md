![LaravelModelMigrations](https://banners.beyondco.de/Laravel%20Model%20Migrations.png?theme=light&packageManager=composer+require&packageName=+will2therich%2Flaravel-model-migrations&pattern=architect&style=style_1&description=Declare+database+migrations+and+factory+definitions+inside+Laravel+models.&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)


# Laravel Automatic Model Migrations

This package is a continuation of `legodion/lucid` which is no longer available

This package allows you to declare database migrations and factory definitions inside of your Laravel models.

Running the `lmm:migrate` command will automatically apply any changes you've made inside your `migration` methods to the database via Doctrine DBAL. If using the `HasNewFactory` trait and `definition` method, it will use the returned array inside the `definition` method to seed with when using the `-s` option.

The `lmm:migrate` command will also run your file-based (traditional) Laravel migrations first, and then your model method migrations after. If you need your model-based migrations to run in a specific order, you may add a `$migrationOrder` property to your models with an integer value (default is `0`).

## Installation

Require this package via Composer:

```console
composer require will2therich/laravel-model-migrations
```

## Usage

Use the `HasNewFactory` trait, and declare `migration` and `definition` methods in your models:

```php
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use will2therich\LaravelModelMigrations\Traits\HasNewFactory;

class MyModel extends Model
{
    use HasNewFactory;

    protected $guarded = [];
    protected $migrationOrder = 1; // optional

    public function migration(Blueprint $table)
    {
        $table->id();
        $table->string('name');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    }

    public function definition(Generator $faker)
    {
        return [
            'name' => $faker->name(),
            'created_at' => $faker->dateTimeThisMonth(),
        ];
    }
}
```

## Commands

### Migrating

Apply the changes inside your `migration` methods to your database:

```console
php artisan lmm:migrate {--f|--fresh} {--s|--seed}
```

Use the `-f` option for fresh migrations, and/or the `-s` option to run seeders afterwards.

### Making Models

Create a model containing the `migration` and `definition` methods:

```console
php artisan lmm:model {name} {--r|--resource}
```

Use the `-r` option to create a Laravel Nova resource for the model at the same time.

### Making Nova Resources

Create a Laravel Nova resource without all the comments:

```console
php artisan lmm:resource {name} {--m|--model}
```

Use the `-m` option to create a model for the Nova resource at the same time.
