<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckPostgres extends Command
{
    protected $signature = 'eventjunction:check-postgres';

    protected $description = 'Check PostgreSQL schema permissions and workspace search path with a rolled-back probe';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'pgsql') {
            $this->error('Select the PostgreSQL connection before running this check.');

            return self::FAILURE;
        }

        $probe = 'eventjunction_probe_'.bin2hex(random_bytes(8));
        $step = 'opening the PostgreSQL connection';

        try {
            $connection->beginTransaction();

            try {
                $step = 'creating an application table in public';
                $connection->statement("CREATE TABLE public.{$probe} (scope text)");
                $connection->insert("INSERT INTO public.{$probe} (scope) VALUES (?)", ['public']);

                $step = 'creating a workspace schema and table';
                $connection->statement("CREATE SCHEMA {$probe}");
                $connection->statement("CREATE TABLE {$probe}.{$probe} (scope text)");
                $connection->insert("INSERT INTO {$probe}.{$probe} (scope) VALUES (?)", ['workspace']);

                $step = 'resolving tables through the workspace search path';
                foreach (["{$probe}, public" => 'workspace', 'public' => 'public'] as $path => $expected) {
                    $connection->select("SELECT set_config('search_path', ?, true)", [$path]);
                    if ($connection->selectOne("SELECT scope FROM {$probe}")->scope !== $expected) {
                        throw new \RuntimeException('Unexpected search path resolution.');
                    }
                }

                $step = 'rolling back the PostgreSQL probe';
            } finally {
                $connection->rollBack();
            }
        } catch (Throwable) {
            $this->error("PostgreSQL check failed while {$step}. Check the connection settings and role permissions.");

            return self::FAILURE;
        }

        $this->info('PostgreSQL connection, schema permissions, and workspace table resolution passed. Probe changes rolled back.');

        return self::SUCCESS;
    }
}
