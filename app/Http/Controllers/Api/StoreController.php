<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Models\Branch;
use App\Models\StorePageSetting;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreController extends Controller
{
    /**
     * Get stores banner data (from dashboard settings).
     */
    public function banner()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "stores_banner_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                $setting = StorePageSetting::get();
                $imgUrl = $setting->hero_background_image_url ?? asset('images/stores-banner.jpg');
                $ts = $setting->updated_at?->timestamp ?? time();
                $imgUrl .= (str_contains($imgUrl, '?') ? '&' : '?') . 'v=' . $ts;
                return [
                    'title' => LocaleHelper::transAttr($setting, 'hero_title') ?? __('stores.banner.title'),
                    'subtitle' => LocaleHelper::transAttr($setting, 'hero_subtitle') ?? __('stores.banner.subtitle'),
                    'backgroundImage' => $imgUrl,
                    'leftBadge' => LocaleHelper::transAttr($setting, 'hero_left_badge'),
                    'rightBadge' => LocaleHelper::transAttr($setting, 'hero_right_badge'),
                ];
            });

            return ApiResponseHelper::success($data);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'STORES_BANNER_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get stores list.
     */
    public function index()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "stores_list_{$locale}";

            $stores = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                return Branch::where('is_active', true)
                    ->get()
                    ->map(function($branch) {
                        return [
                            'id' => $branch->id,
                            'name' => LocaleHelper::transAttr($branch, 'name') ?? $branch->name,
                            'manager' => $branch->manager_name ?? 'Store Manager',
                            'address' => LocaleHelper::transAttr($branch, 'address') ?? $branch->address,
                            'phone' => $branch->phone,
                            'email' => $branch->email,
                            'hours' => $branch->working_hours ?? '9:00 AM – 7:00 PM',
                            'lat' => $branch->latitude !== null ? (float) $branch->latitude : null,
                            'lng' => $branch->longitude !== null ? (float) $branch->longitude : null,
                            'mapUrl' => $branch->map_url
                        ];
                    })
                    ->values();
            });

            return ApiResponseHelper::success($stores);

        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'STORES_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
