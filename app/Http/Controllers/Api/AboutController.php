<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Models\TeamMember;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use App\Models\Testimonial;
use App\Models\AboutSection;
use App\Models\AboutStat;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Get team members.
     */
    public function teamMembers()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "about_team_members_{$locale}";

            $members = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                return TeamMember::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function($member) {
                        return [
                            'name' => LocaleHelper::transAttr($member, 'name'),
                            'role' => LocaleHelper::transAttr($member, 'role'),
                            'image' => $member->image_url ?: asset('images/default-user.png'),
                            'image_medium' => $member->image_medium_url,
                            'image_thumb' => $member->image_thumb_url,
                            'bio' => LocaleHelper::transAttr($member, 'bio'),
                        ];
                    })
                    ->values();
            });

            return ApiResponseHelper::success($members);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'TEAM_MEMBERS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get testimonials.
     */
    public function testimonials()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "about_testimonials_{$locale}";

            $testimonials = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                // About Us: show all testimonials. Featured flag is only for the Home page section.
                return Testimonial::query()
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($testimonial) {
                        return [
                            'id' => $testimonial->id,
                            'customer_name' => LocaleHelper::transAttr($testimonial, 'customer_name'),
                            'customer_image' => $testimonial->customer_image_url,
                            'customer_image_thumb' => $testimonial->customer_image_thumb_url,
                            'text' => LocaleHelper::transAttr($testimonial, 'text'),
                            'rating' => $testimonial->rating,
                            'date' => $testimonial->created_at?->diffForHumans(),
                        ];
                    })
                    ->values();
            });

            return ApiResponseHelper::success($testimonials);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'TESTIMONIALS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get hero banner.
     */
    public function hero()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "about_hero_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                $section = AboutSection::where('section_key', 'hero')->first();
                if (!$section) {
                    return null;
                }
                $imgUrl = $section->background_image_url ?? '';
                $ts = $section->updated_at?->timestamp ?? time();
                if ($imgUrl) {
                    $imgUrl .= (str_contains($imgUrl, '?') ? '&' : '?') . 'v=' . $ts;
                }
                return [
                    'title' => LocaleHelper::transAttr($section, 'title'),
                    'backgroundImage' => $imgUrl,
                    'leftBadge' => LocaleHelper::transAttr($section, 'left_badge'),
                    'rightBadge' => LocaleHelper::transAttr($section, 'right_badge'),
                ];
            });

            if (!$data) {
                return ApiResponseHelper::error('HERO_NOT_FOUND', __('errors.not_found'), 404);
            }

            return ApiResponseHelper::success($data);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'HERO_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get about description.
     */
    public function description()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "about_description_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () use ($locale) {
                $section = AboutSection::where('section_key', 'description')->first();
                if (!$section) {
                    return null;
                }
                $features = $locale === 'ar' ? ($section->features_ar ?? $section->features_en ?? $section->features)
                    : ($section->features_en ?? $section->features_ar ?? $section->features);
                $features = is_array($features) ? $features : [];
                return [
                    'title' => LocaleHelper::transAttr($section, 'title'),
                    'subtitle' => LocaleHelper::transAttr($section, 'subtitle'),
                    'description' => LocaleHelper::transAttr($section, 'description'),
                    'image' => $section->image_url,
                    'features' => $features,
                    'buttonText' => LocaleHelper::transAttr($section, 'button_text'),
                ];
            });

            if (!$data) {
                return ApiResponseHelper::error('DESCRIPTION_NOT_FOUND', __('errors.not_found'), 404);
            }

            return ApiResponseHelper::success($data);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'ABOUT_DESCRIPTION_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get about stats.
     */
    public function stats()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "about_stats_{$locale}";

            $stats = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                return AboutStat::active()->ordered()->get()->map(function($stat) {
                    return [
                        'icon' => $stat->icon,
                        'title' => LocaleHelper::transAttr($stat, 'title'),
                        'value' => $stat->value,
                        'suffix' => LocaleHelper::transAttr($stat, 'suffix'),
                    ];
                })->values();
            });

            return ApiResponseHelper::success($stats);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'ABOUT_STATS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
