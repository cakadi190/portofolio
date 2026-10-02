<?php

use App\Enums\AwardType;
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
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('title');
            $table->string('type')->default(AwardType::Competition->value)->index();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('rank')->nullable();
            $table->date('awarded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
