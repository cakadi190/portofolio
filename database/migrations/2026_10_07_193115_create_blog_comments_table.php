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
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // Keep the discussion when the author's account is removed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Deleting a comment re-parents its replies (see BlogComment::booted);
            // the null fallback only applies to raw/cascaded deletes.
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->nullOnDelete();
            $table->text('body');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['post_id', 'status', 'parent_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
