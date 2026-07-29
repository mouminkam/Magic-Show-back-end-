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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Page title
            $table->string('slug')->unique(); // URL slug
            $table->text('content')->nullable(); // Page content (HTML)
            $table->text('excerpt')->nullable(); // Page excerpt/summary
            $table->string('template')->default('default'); // Page template
            $table->string('status')->default('draft'); // draft, published, archived
            $table->boolean('is_homepage')->default(false); // Whether this is the homepage
            $table->boolean('is_featured')->default(false); // Whether this page is featured
            $table->boolean('show_in_navigation')->default(true); // Show in main navigation
            $table->boolean('allow_comments')->default(false); // Allow comments on this page
            $table->integer('sort_order')->default(0); // Sort order for navigation
            $table->string('meta_title')->nullable(); // SEO meta title
            $table->text('meta_description')->nullable(); // SEO meta description
            $table->json('meta_keywords')->nullable(); // SEO meta keywords
            $table->string('featured_image')->nullable(); // Featured image URL
            $table->json('custom_fields')->nullable(); // Custom fields (JSON)
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade'); // Page author
            $table->timestamp('published_at')->nullable(); // When page was published
            $table->timestamps();

            // Indexes for better performance
            $table->index(['slug']);
            $table->index(['status']);
            $table->index(['is_homepage']);
            $table->index(['is_featured']);
            $table->index(['show_in_navigation']);
            $table->index(['author_id']);
            $table->index(['published_at']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
