<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogPost extends Model
{
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'title_ar',
        'title_en',
        'slug',
        'content',
        'content_ar',
        'content_en',
        'excerpt',
        'excerpt_ar',
        'excerpt_en',
        'status',
        'is_featured',
        'allow_comments',
        'view_count',
        'comment_count',
        'featured_image',
        'gallery_images',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'tags',
        'custom_fields',
        'author_id',
        'category_id',
        'published_at',
        'featured_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'view_count' => 'integer',
        'comment_count' => 'integer',
        'gallery_images' => 'array',
        'meta_keywords' => 'array',
        'tags' => 'array',
        'custom_fields' => 'array',
        'published_at' => 'datetime',
        'featured_until' => 'datetime',
    ];

    /**
     * Blog post status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Available blog post statuses with display names.
     */
    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title (prefer title_en, then title_ar, then title) if not provided
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $titleForSlug = $post->title_en ?? $post->title_ar ?? $post->title ?? '';
                $post->slug = $titleForSlug !== '' ? Str::slug($titleForSlug) : Str::slug($post->title ?? 'post');
            }
            // Set published_at if status is published and not set
            if ($post->status === self::STATUS_PUBLISHED && empty($post->published_at)) {
                $post->published_at = now();
            }
        });

        // Update slug when title changes
        static::updating(function ($post) {
            if (($post->isDirty('title') || $post->isDirty('title_en') || $post->isDirty('title_ar')) && empty($post->slug)) {
                $titleForSlug = $post->title_en ?? $post->title_ar ?? $post->title ?? '';
                $post->slug = $titleForSlug !== '' ? Str::slug($titleForSlug) : Str::slug($post->title ?? 'post');
            }
            // Set published_at if status changed to published
            if ($post->isDirty('status') && $post->status === self::STATUS_PUBLISHED && empty($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    /**
     * Get the author that owns this blog post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the category that this blog post belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope a query to only include draft posts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope a query to only include archived posts.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope a query to only include featured posts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include posts that allow comments.
     */
    public function scopeWithComments($query)
    {
        return $query->where('allow_comments', true);
    }

    /**
     * Scope a query to order by published date (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope a query to order by view count (most viewed first).
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by author.
     */
    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Scope a query to filter by tags.
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopePublishedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('published_at', [$startDate, $endDate]);
    }

    /**
     * Get the post status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }

    /**
     * Get the post URL.
     */
    public function getUrlAttribute(): string
    {
        return url('/blog/' . $this->slug);
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    /**
     * Get the gallery images URLs.
     */
    public function getGalleryImageUrlsAttribute(): array
    {
        if (!$this->gallery_images) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->gallery_images);
    }

    /**
     * Get the meta title or fallback to post title.
     */
    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->title;
    }

    /**
     * Get the meta description or fallback to excerpt.
     */
    public function getMetaDescriptionAttribute($value): ?string
    {
        return $value ?: $this->excerpt;
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if the post is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the post is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if the post is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Check if the post allows comments.
     */
    public function allowsComments(): bool
    {
        return $this->allow_comments;
    }

    /**
     * Check if the post is currently featured (not expired).
     */
    public function isCurrentlyFeatured(): bool
    {
        if (!$this->is_featured) {
            return false;
        }

        if (!$this->featured_until) {
            return true;
        }

        return $this->featured_until->isFuture();
    }

    /**
     * Publish the post.
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the post (set to draft).
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => self::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Archive the post.
     */
    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Increment the view count.
     */
    public function incrementViewCount(): bool
    {
        return $this->increment('view_count');
    }

    /**
     * Increment the comment count.
     */
    public function incrementCommentCount(): bool
    {
        return $this->increment('comment_count');
    }

    /**
     * Decrement the comment count.
     */
    public function decrementCommentCount(): bool
    {
        return $this->decrement('comment_count');
    }

    /**
     * Add a tag to the post.
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
        }
    }

    /**
     * Remove a tag from the post.
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_filter($tags, function ($t) use ($tag) {
            return $t !== $tag;
        }));
    }

    /**
     * Check if the post has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Get a custom field value.
     */
    public function getCustomField(string $key, $default = null)
    {
        return $this->custom_fields[$key] ?? $default;
    }

    /**
     * Set a custom field value.
     */
    public function setCustomField(string $key, $value): void
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        $this->custom_fields = $fields;
    }

    /**
     * Get the reading time in minutes.
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, round($wordCount / 200)); // Average reading speed: 200 words per minute
    }

    /**
     * Get the word count.
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->content));
    }

    /**
     * Get the character count.
     */
    public function getCharacterCountAttribute(): int
    {
        return strlen(strip_tags($this->content));
    }

    /**
     * Get the time since published.
     */
    public function getTimeSincePublishedAttribute(): string
    {
        if (!$this->published_at) {
            return 'Not published';
        }

        return $this->published_at->diffForHumans();
    }

    /**
     * Get the excerpt or auto-generated excerpt from content.
     */
    public function getExcerptAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Auto-generate excerpt from content
        $content = strip_tags($this->content);
        return Str::limit($content, 150);
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'title' => ['title_en', 'title_ar'],
            'excerpt' => ['excerpt_en', 'excerpt_ar'],
            'content' => ['content_en', 'content_ar'],
        ];
    }
}
