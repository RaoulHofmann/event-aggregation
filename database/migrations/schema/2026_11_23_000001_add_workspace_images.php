<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('mime', 32);
            $table->unsignedInteger('bytes');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignUuid('cover_image_id')->nullable()->constrained('workspace_images');
            $table->index('cover_image_id');
            $table->string('cover_public_id', 40)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['cover_image_id']);
            $table->dropIndex(['cover_image_id']);
            $table->dropUnique(['cover_public_id']);
            $table->dropColumn(['cover_image_id', 'cover_public_id']);
        });

        Schema::dropIfExists('workspace_images');
    }
};
