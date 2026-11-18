<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 16)->default('active')->index();
            $table->boolean('is_platform_admin')->default(false);
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable();
        });
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('target_type', 16);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('suspended_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['account_status', 'is_platform_admin']));
    }
};
