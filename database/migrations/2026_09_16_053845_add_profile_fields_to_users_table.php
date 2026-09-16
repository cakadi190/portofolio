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
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->default('user')->after('email');
            $table->string('phone')->nullable()->after('account_type');
            $table->string('gender')->nullable()->after('phone');
            $table->boolean('is_student')->default(false)->after('gender');
            $table->text('nik')->nullable()->after('is_student');
            $table->text('date_of_birth')->nullable()->after('nik');
            $table->text('address')->nullable()->after('date_of_birth');
            $table->string('avatar')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'phone', 'gender', 'is_student', 'nik', 'date_of_birth', 'address', 'avatar']);
        });
    }
};
