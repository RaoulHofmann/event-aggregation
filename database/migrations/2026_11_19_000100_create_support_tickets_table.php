<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public.support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('public.users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('public.teams')->nullOnDelete();
            $table->string('subject', 160);
            $table->text('message');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE public.support_tickets ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        Schema::dropIfExists('public.support_tickets');
    }
};
