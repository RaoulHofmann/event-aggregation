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
        Schema::create('recurrence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, yearly
            $table->integer('interval')->default(1); // Every X units of frequency
            $table->string('days_of_week')->nullable(); // For weekly (e.g., "Mon,Wed,Fri")
            $table->integer('day_of_month')->nullable(); // For monthly
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable(); // Optional end date
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrence_rules');
    }
};
