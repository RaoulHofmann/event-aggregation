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
        Schema::create('field_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('fieldId');
            $table->string('type'); // text, number, date, select, etc.
            $table->boolean('required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_templates');
    }
};
