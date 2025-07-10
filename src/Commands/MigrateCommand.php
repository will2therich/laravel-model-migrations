<?php

namespace will2therich\LaravelModelMigrations\Commands;

use Doctrine\DBAL\DriverManager;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class MigrateCommand extends Command
{
    use ConfirmableTrait;
    
    protected $signature = 'lmm:migrate {--f|--fresh} {--s|--seed} {--force}';

    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', ['--force' => true]);

        $this->migrateModels();

        if ($this->option('seed')) {
            $this->call('db:seed', ['--force' => true]);
        }

        return 0;
    }

    protected function migrateModels()
    {
        $path = is_dir(app_path('Models')) ? app_path('Models') : app_path();
        $namespace = app()->getNamespace();
        $models = collect();

        foreach ((new Finder)->in($path)->files() as $model) {
            $model = $namespace . str_replace(
                ['/', '.php'], ['\\', ''], Str::after($model->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($model, Model::class) && method_exists($model, 'migration')) {
                $models->push([
                    'object' => $object = app($model),
                    'order' => $object->migrationOrder ?? 0,
                ]);
            }
        }

        foreach ($models->sortBy('order') as $model) {
            $this->migrateModel($model['object']);
        }
    }

    protected function migrateModel(Model $model)
    {
        try {
            $modelTable = $model->getTable();
            $tempTable = 'table_' . $modelTable;

            $schema = Schema::connection($model->getConnectionName());

            $schema->dropIfExists($tempTable);

            $schema->create($tempTable, function (Blueprint $table) use ($model) {
                $model->migration($table);
            });

            if ($schema->hasTable($modelTable)) {
                $doctrineConnection = $this->createDoctrineConnection();
                $schemaManager = $doctrineConnection->createSchemaManager();

                // Compare our temp and existing tbale
                $comparator = $schemaManager->createComparator();
                $diff = $comparator->compareTables(
                    $schemaManager->introspectTable($modelTable),
                    $schemaManager->introspectTable($tempTable)
                );

                // Generate our SQL statements to bring model table upto date
                $sqlStatements = $doctrineConnection->getDatabasePlatform()->getAlterTableSQL($diff);

                // If we have changes to make lets make them
                if (!empty($sqlStatements)) {
                    foreach ($sqlStatements as $statement) {
                        $doctrineConnection->executeStatement($statement);
                    }

                    $this->line('<info>Table updated:</info> ' . $modelTable);
                }

                $schema->drop($tempTable);
            } else {
                $schema->rename($tempTable, $modelTable);

                $this->line('<info>Table created:</info> ' . $modelTable);
            }
        } catch (\Exception $e) {
            $this->line('<error>Error migrating table:</error> ' . $model->getTable());
        }
    }

    private function createDoctrineConnection()
    {
        $connection = Schema::connection(null)->getConnection();
        $config = $connection->getConfig();

        return DriverManager::getConnection([
            'dbname' => $config['database'],
            'user' => $config['username'],
            'password' => $config['password'],
            'host' => $config['host'],
            'driver' => 'pdo_' . $config['driver'], // Adjust the driver as needed
        ]);


    }
}
