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
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('level');
            $table->string('grade')->nullable();
            $table->string('department')->nullable();
            $table->string('study_program')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('place');
            $table->string('academic_score_type')->nullable();
            $table->string('academic_score_label')->nullable();
            $table->decimal('academic_score_value', 6, 2)->nullable();
            $table->decimal('academic_score_scale', 6, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
