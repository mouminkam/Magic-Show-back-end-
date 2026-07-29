<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Page extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'template',
        'status',
        'is_homepage',
        'is_featured',
        'show_in_navigation',
        'allow_comments',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'featured_image',
        'custom_fields',
        'author_id',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_homepage' => 'boolean',
        'is_featured' => 'boolean',
        'show_in_navigation' => 'boolean',
        'allow_comments' => 'boolean',
        'sort_order' => 'integer',
        'meta_keywords' => 'array',
        'custom_fields' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Page status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Available page statuses with display names.
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

        // Auto-generate slug from title if not provided
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            
            // Set published_at if status is published and not set
            if ($page->status === self::STATUS_PUBLISHED && empty($page->published_at)) {
                $page->published_at = now();
            }
        });

        // Update slug when title changes
        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            
            // Set published_at if status changed to published
            if ($page->isDirty('status') && $page->status === self::STATUS_PUBLISHED && empty($page->published_at)) {
                $page->published_at = now();
            }
        });
    }

    /**
     * Get the author that owns this page.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope a query to only include archived pages.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope a query to only include featured pages.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include pages that show in navigation.
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true);
    }

    /**
     * Scope a query to only include the homepage.
     */
    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Scope a query to filter by template.
     */
    public function scopeWithTemplate($query, $template)
    {
        return $query->where('template', $template);
    }

    /**
     * Get the page status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Unknown';
    }

    /**
     * Get the page URL.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_homepage) {
            return url('/');
        }
        
        return url('/pages/' . $this->slug);
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    /**
     * Get the meta title or fallback to page title.
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
     * Check if the page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if the page is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the page is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if the page is the homepage.
     */
    public function isHomepage(): bool
    {
        return $this->is_homepage;
    }

    /**
     * Check if the page is featured.
     */
    public function isFeatured(): bool
    {
        return $this->is_featured;
    }

    /**
     * Check if the page shows in navigation.
     */
    public function showsInNavigation(): bool
    {
        return $this->show_in_navigation;
    }

    /**
     * Check if the page allows comments.
     */
    public function allowsComments(): bool
    {
        return $this->allow_comments;
    }

    /**
     * Publish the page.
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the page (set to draft).
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => self::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Archive the page.
     */
    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Set as homepage (only one can be homepage).
     */
    public function setAsHomepage(): bool
    {
        // Remove homepage flag from all other pages
        static::where('id', '!=', $this->id)->update(['is_homepage' => false]);
        
        // Set this page as homepage
        $this->is_homepage = true;
        
        return $this->save();
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
}
