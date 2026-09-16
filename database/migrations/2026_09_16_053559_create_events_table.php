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
            $table->string('tenant_id');
            $table->foreignId('event_category_id')->nullable()->constrained('event_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description')->nullable();
            $table->longText('rules')->nullable();
            $table->json('keywords')->nullable();
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->boolean('is_always_open')->default(false);
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('place')->nullable();
            $table->string('map_link')->nullable();
            $table->string('whatsapp_link')->nullable();
            $table->string('contact_person')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('status')->default('draft');
            $table->string('image_url')->nullable();
            $table->string('venue_layout_url')->nullable();
            $table->json('merchandise')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('username')->on('tenants')->cascadeOnDelete();
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
