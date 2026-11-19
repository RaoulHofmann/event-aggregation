<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public.calendar_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained('public.teams')->cascadeOnDelete();
            $table->string('public_id', 40)->unique();
            $table->string('title', 100);
            $table->string('source', 32)->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE public.calendar_widgets ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        Schema::dropIfExists('public.calendar_widgets');
    }
};
