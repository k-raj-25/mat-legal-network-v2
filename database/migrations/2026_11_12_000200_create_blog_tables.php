<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // Author. Kept nullable so a post survives if its author account is ever removed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 300)->nullable();
            $table->longText('body'); // sanitised HTML (see App\Support\HtmlSanitizer)
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_alt', 160)->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('status', 20)->default('draft'); // draft | published
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->timestamps();

            // Public index / category pages: newest published first.
            $table->index(['status', 'published_at']);
            $table->index(['blog_category_id', 'status', 'published_at']);
            // Panel lists: "my posts" for editors, all posts for the admin.
            $table->index(['user_id', 'updated_at']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('blog_categories');
    }
};
