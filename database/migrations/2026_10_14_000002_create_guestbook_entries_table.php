<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guestbook_entries', function (Blueprint $table) {
            $table->id();
            // Keep the entry when the author's account is removed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Deleting an entry re-parents its replies (see GuestbookEntry::booted).
            $table->foreignId('parent_id')->nullable()->constrained('guestbook_entries')->nullOnDelete();
            $table->text('body');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['status', 'parent_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guestbook_entries');
    }
};
