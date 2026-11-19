<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->text('recoverable_secret')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropColumn('recoverable_secret');
        });
    }
};
