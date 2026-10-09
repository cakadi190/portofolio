<?php

use App\Enums\SpeakingFormat;
use App\Enums\SpeakingRole;
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
        Schema::create('speaking_engagements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('organizer');
            $table->string('role')->default(SpeakingRole::Speaker->value)->index();
            $table->string('format')->default(SpeakingFormat::Offline->value);
            $table->string('location')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->string('registration_url')->nullable();
            $table->text('description')->nullable();
            $table->string('poster')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_engagements');
    }
};
