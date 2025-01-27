<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

trait SchemaManagement
{
    /**
     * Create a new schema for the team if it doesn't exist.
     *
     * @param string $schemaName
     * @return void
     */
    public function createSchema(string $schemaName): void
    {
        // Check if schema already exists
        if ($this->schemaExists($schemaName)) {
            Log::info("Schema '$schemaName' already exists.");
            return;
        }

        // Create the schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"$schemaName\"");

        // Create the migration table for the schema
        DB::statement("CREATE TABLE IF NOT EXISTS \"$schemaName\".migrations (
            id serial PRIMARY KEY,
            migration varchar(255) NOT NULL,
            batch int NOT NULL
        )");

        Log::info("Schema '$schemaName' created successfully.");
    }

    /**
     * Check if the schema exists.
     *
     * @param string $schemaName
     * @return bool
     */
    public function schemaExists(string $schemaName): bool
    {
        $result = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$schemaName]);

        return !empty($result);
    }

    /**
     * Set the search path to the provided schema.
     *
     * @param string $schemaName
     * @return void
     */
    public function setSchema(string $schemaName): void
    {
        DB::statement("SET search_path TO \"$schemaName\", public");
    }

    /**
     * Run migrations for the given schema.
     *
     * @param string $schemaName
     * @return void
     */
    public function runMigrations(string $schemaName): void
    {
        $this->setSchema($schemaName);

        // Run migrations for the schema
        Artisan::call('migrate', [
            '--path' => 'database/migrations/schema',
            '--force' => true,
        ]);

        Log::info("Migrations ran successfully for schema '$schemaName'.");
    }

    /**
     * Delete the schema and its associated tables.
     *
     * @param string $schemaName
     * @return void
     */
    public function deleteSchema(string $schemaName): void
    {
        DB::statement("DROP SCHEMA IF EXISTS \"$schemaName\" CASCADE");

        Log::info("Schema '$schemaName' deleted successfully.");
    }

    /**
     * Check if the team already exists.
     *
     * @param string $teamName
     * @return bool
     */
    public function teamExists(string $teamName): bool
    {
        return Team::where('name', $teamName)->exists();
    }
}
