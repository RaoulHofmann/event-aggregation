<?php

namespace App\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait SchemaManagement
{
    private function safeSchema(string $name): void
    {
        if ($name === '' || in_array($name, ['public', 'information_schema']) || str_starts_with($name, 'pg_')) {
            throw new \InvalidArgumentException('Reserved schemas cannot be used as workspaces.');
        }
    }

    public function schemaExists(string $schemaName): bool
    {
        return (bool) DB::select('SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?', [$schemaName]);
    }

    public function isTenantSchema(string $name): bool
    {
        return $name !== '' && ! in_array($name, ['public', 'information_schema']) && ! str_starts_with($name, 'pg_') && $this->schemaExists($name);
    }

    public function setSchema(string $schemaName): void
    {
        $this->safeSchema($schemaName);
        DB::select("SELECT set_config('search_path', ?, false)", ['"'.str_replace('"', '""', $schemaName).'", public']);
    }

    public function createSchema(string $schemaName): void
    {
        $this->safeSchema($schemaName);
        $name = str_replace('"', '""', $schemaName);
        DB::statement('CREATE SCHEMA IF NOT EXISTS "'.$name.'"');
        DB::statement('CREATE TABLE IF NOT EXISTS "'.$name.'".migrations (id serial PRIMARY KEY, migration varchar(255) NOT NULL, batch int NOT NULL)');
    }

    public function runMigrations(string $schemaName): void
    {
        $previous = DB::selectOne("SELECT current_setting('search_path') AS path")->path;
        try {
            $this->setSchema($schemaName);
            if (Artisan::call('migrate', ['--path' => 'database/migrations/schema', '--force' => true]) !== 0) {
                throw new \RuntimeException('Workspace provisioning failed.');
            }
        } finally {
            DB::select("SELECT set_config('search_path', ?, false)", [$previous]);
        }
    }

    public function deleteSchema(string $schemaName): void
    {
        $this->safeSchema($schemaName);
        DB::statement('DROP SCHEMA IF EXISTS "'.str_replace('"', '""', $schemaName).'" CASCADE');
    }
}
