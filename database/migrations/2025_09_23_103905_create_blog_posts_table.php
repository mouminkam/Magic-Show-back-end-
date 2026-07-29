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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Post title
            $table->string('slug')->unique(); // URL slug
            $table->text('content'); // Post content (HTML)
            $table->text('excerpt')->nullable(); // Post excerpt/summary
            $table->string('status')->default('draft'); // draft, published, archived
            $table->boolean('is_featured')->default(false); // Whether this post is featured
            $table->boolean('allow_comments')->default(true); // Allow comments on this post
            $table->integer('view_count')->default(0); // Number of views
            $table->integer('comment_count')->default(0); // Number of comments
            $table->string('featured_image')->nullable(); // Featured image URL
            $table->json('gallery_images')->nullable(); // Gallery images (JSON array)
            $table->string('meta_title')->nullable(); // SEO meta title
            $table->text('meta_description')->nullable(); // SEO meta description
            $table->json('meta_keywords')->nullable(); // SEO meta keywords
            $table->json('tags')->nullable(); // Post tags (JSON array)
            $table->json('custom_fields')->nullable(); // Custom fields (JSON)
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade'); // Post author
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null'); // Post category
            $table->timestamp('published_at')->nullable(); // When post was published
            $table->timestamp('featured_until')->nullable(); // Until when this post should be featured
            $table->timestamps();

            // Indexes for better performance
            $table->index(['slug']);
            $table->index(['status']);
            $table->index(['is_featured']);
            $table->index(['author_id']);
            $table->index(['category_id']);
            $table->index(['published_at']);
            $table->index(['view_count']);
            $table->index(['comment_count']);
            $table->index(['featured_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
