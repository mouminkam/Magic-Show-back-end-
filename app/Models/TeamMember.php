<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyLocaleBaseFields;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use SyncsLegacyLocaleBaseFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'role',
        'role_ar',
        'role_en',
        'bio',
        'bio_ar',
        'bio_en',
        'image',
        'image_medium',
        'image_thumb',
        'email',
        'phone',
        'social_links',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'social_links' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active team members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Get the medium image URL.
     */
    public function getImageMediumUrlAttribute(): ?string
    {
        return $this->image_medium ? asset('storage/' . $this->image_medium) : $this->image_url;
    }

    /**
     * Get the thumbnail image URL.
     */
    public function getImageThumbUrlAttribute(): ?string
    {
        return $this->image_thumb ? asset('storage/' . $this->image_thumb) : $this->image_url;
    }

    protected function getLocaleBaseFieldMap(): array
    {
        return [
            'name' => ['name_en', 'name_ar'],
            'role' => ['role_en', 'role_ar'],
            'bio' => ['bio_en', 'bio_ar'],
        ];
    }
}
