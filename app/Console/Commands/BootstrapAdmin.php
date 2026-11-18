<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProvisionTeam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BootstrapAdmin extends Command
{
    protected $signature = 'eventjunction:bootstrap-admin';

    protected $description = 'Ensure EventJunction has a platform administrator without resetting an existing account';

    public function handle(ProvisionTeam $provisioner): int
    {
        try {
            return DB::transaction(function () use ($provisioner) {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('eventjunction.bootstrap-admin'))");
                $users = User::query()->where('is_platform_admin', true)->where('account_status', User::ACTIVE);
                if ($users->exists()) {
                    $this->info('An active platform administrator already exists.');

                    return self::SUCCESS;
                }

                $email = strtolower(trim((string) config('eventjunction.bootstrap_admin_email')));
                $password = (string) config('eventjunction.bootstrap_admin_password');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || strlen($password) < 16 || strlen($password) > 72 || str_contains($password, "\0")) {
                    $this->error('Set EVENTJUNCTION_ADMIN_EMAIL and a 16–72 byte EVENTJUNCTION_ADMIN_PASSWORD before releasing a fresh database.');

                    return self::FAILURE;
                }
                if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
                    $this->error('The requested admin email already exists. Bootstrap will not promote or overwrite that account.');

                    return self::FAILURE;
                }

                $admin = new User(['name' => 'Administrator', 'email' => $email, 'password' => $password]);
                $admin->forceFill(['is_platform_admin' => true, 'account_status' => User::ACTIVE])->save();
                $provisioner->create($admin, 'Administration', true);
                $this->info('Platform administrator provisioned.');

                return self::SUCCESS;
            });
        } catch (Throwable) {
            $this->error('Administrator provisioning failed. Check the database and role permissions.');

            return self::FAILURE;
        }
    }
}
