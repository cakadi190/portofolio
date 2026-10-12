<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->unsignedInteger('visits_count')->default(0);
            $table->unsignedBigInteger('page_views_count')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->string('entry_path', 191);
            $table->string('referrer_domain', 191)->nullable();
            $table->unsignedInteger('page_views')->default(1);
            $table->timestamp('started_at')->index();
            $table->timestamp('last_activity_at');
            $table->timestamps();

            $table->index(['visitor_id', 'last_activity_at']);
        });

        Schema::create('visitor_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->date('day');

            $table->unique(['visitor_id', 'day']);
            $table->index('day');
        });

        Schema::create('visitor_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('path', 191);
            $table->boolean('is_bot')->default(false);
            $table->unsignedBigInteger('page_views')->default(0);

            $table->unique(['day', 'path', 'is_bot']);
        });

        Schema::create('ip_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('provider', 20)->default('ipinfo');
            $table->string('status', 20);
            $table->string('country', 8)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('asn', 32)->nullable();
            $table->string('organization', 255)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_lookups');
        Schema::dropIfExists('visitor_daily_stats');
        Schema::dropIfExists('visitor_days');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('visitors');
    }
};
