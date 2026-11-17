<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->change();
            $table->boolean('all_day')->default(false);
            $table->jsonb('recurrence')->nullable();
            $table->index('starts_at');
            $table->index('ends_at');
        });
        Schema::create('event_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->boolean('cancelled')->default(false);
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_exceptions');
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['starts_at']);
            $table->dropIndex(['ends_at']);
            $table->dropColumn(['all_day', 'recurrence']);
        });
    }
};
