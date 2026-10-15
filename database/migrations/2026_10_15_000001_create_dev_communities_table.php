<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dev_communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform');
            $table->text('description')->nullable();
            $table->string('invite_url', 500);
            $table->unsignedInteger('member_count')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['platform', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dev_communities');
    }
};
