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
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->string('gateway_channel_code');
            $table->string('name');
            $table->string('logo')->nullable();
            $table->decimal('fee_flat', 12, 2)->nullable();
            $table->decimal('fee_percentage', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['payment_method_id', 'gateway_channel_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_channels');
    }
};
