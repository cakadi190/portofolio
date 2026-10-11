<?php

use App\Enums\ClarificationVerdict;
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
        Schema::create('clarifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('claim');
            $table->string('claim_origin')->nullable();
            $table->string('verdict')->default(ClarificationVerdict::Unverified->value)->index();
            $table->string('summary')->nullable();
            $table->longText('content');
            $table->text('apology')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('sources')->nullable();
            $table->json('updates')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->dateTime('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clarifications');
    }
};
