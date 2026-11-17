<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_connections', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->constrained('event_templates')->restrictOnDelete();
            $table->jsonb('field_mapping')->nullable();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->jsonb('source_snapshot')->nullable();
            $table->jsonb('local_overrides')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn(['source_snapshot', 'local_overrides']));
        Schema::table('provider_connections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn('field_mapping');
        });
    }
};
