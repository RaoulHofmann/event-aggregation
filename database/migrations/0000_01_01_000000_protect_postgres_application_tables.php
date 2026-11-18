<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Supabase projects can grant PostgREST roles access to new public objects by default.
        $roles = DB::table('pg_roles')->whereIn('rolname', ['anon', 'authenticated', 'service_role'])->pluck('rolname');
        if ($roles->isEmpty()) {
            return;
        }
        $grantees = $roles->map(fn (string $role) => '"'.$role.'"')->implode(', ');
        foreach (['', ' IN SCHEMA public'] as $scope) {
            DB::statement("ALTER DEFAULT PRIVILEGES{$scope} REVOKE ALL ON TABLES FROM {$grantees}");
            DB::statement("ALTER DEFAULT PRIVILEGES{$scope} REVOKE ALL ON SEQUENCES FROM {$grantees}");
        }

        // Also cover an existing installation and the migrations table created before this migration.
        foreach (['migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'teams', 'team_user', 'team_invitations', 'personal_access_tokens', 'admin_actions'] as $table) {
            if (DB::selectOne('SELECT to_regclass(?) AS name', ['public.'.$table])->name !== null) {
                DB::statement("REVOKE ALL ON TABLE public.\"{$table}\" FROM {$grantees}");
            }
        }
    }

    public function down(): void
    {
        // Rolling back application code should not re-expose database tables to browser roles.
    }
};
