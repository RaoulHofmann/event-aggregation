<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // conference, workshop, concert
            $table->string('status')->default('draft'); // draft, published, cancelled

            // Timing
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('timezone')->default('UTC');

            // Location
            $table->string('venue_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->string('virtual_url')->nullable();

            // Capacity & Registration
            $table->integer('capacity')->nullable();
            $table->boolean('registration_required')->default(false);
            $table->dateTime('registration_deadline')->nullable();
            $table->decimal('price', 10, 2)->nullable();

            // Organization
            $table->foreignId('organizer_id')->nullable()->constrained('users');
            $table->string('organizer_name')->nullable();
            $table->string('organizer_email')->nullable();

            // Additional
            $table->json('custom_fields')->nullable();
            $table->string('featured_image')->nullable();
            $table->boolean('is_recurring')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
