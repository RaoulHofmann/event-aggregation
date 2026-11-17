<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->text('credentials');
            $table->string('status')->default('pending');
            $table->timestampTz('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
        Schema::table('event_templates', fn (Blueprint $table) => $table->string('source')->default('eventnest'));
        Schema::table('events', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->string('timezone')->nullable();
            $table->text('location')->nullable();
            $table->string('status')->default('draft');
            $table->string('source')->default('eventnest');
            $table->foreignId('provider_connection_id')->nullable()->constrained('provider_connections')->restrictOnDelete();
            $table->string('external_id')->nullable();
            $table->text('source_url')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->unique(['provider_connection_id', 'external_id']);
        });
        DB::statement("UPDATE events SET title = LEFT(COALESCE(event_data->>'title', event_data->>'name'), 255)");
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['provider_connection_id', 'external_id']);
            $table->dropConstrainedForeignId('provider_connection_id');
            $table->dropColumn(['title', 'description', 'starts_at', 'ends_at', 'timezone', 'location', 'status', 'source', 'external_id', 'source_url', 'synced_at']);
        });
        Schema::table('event_templates', fn (Blueprint $table) => $table->dropColumn('source'));
        Schema::dropIfExists('provider_connections');
    }
};
