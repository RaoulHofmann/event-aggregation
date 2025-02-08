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
            $table->string('field_id');
            $table->string('type'); // text, number, date, select, etc.
            $table->boolean('required')->default(false);
            $table->boolean('multiple')->default(false);
            $table->jsonb('validation_rules')->nullable();
            $table->jsonb('options')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
