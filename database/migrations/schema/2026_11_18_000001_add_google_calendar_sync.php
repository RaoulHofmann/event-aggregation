<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_connections', fn (Blueprint $table) => $table->jsonb('settings')->nullable());
    }

    public function down(): void
    {
        Schema::table('provider_connections', fn (Blueprint $table) => $table->dropColumn('settings'));
    }
};
