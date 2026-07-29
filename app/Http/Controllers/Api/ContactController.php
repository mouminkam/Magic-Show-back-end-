<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Contact\SendMessageRequest;
use App\Models\ContactMessage;
use App\Models\ContactSetting;

class ContactController extends Controller
{
    /**
     * Get contact hero data.
     */
    public function hero()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "contact_hero_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                $setting = ContactSetting::get();
                $imgUrl = $setting->hero_background_image_url ?? asset('images/contact-banner.jpg');
                $ts = $setting->updated_at?->timestamp ?? time();
                $imgUrl .= (str_contains($imgUrl, '?') ? '&' : '?') . 'v=' . $ts;
                return [
                    'title' => LocaleHelper::transAttr($setting, 'hero_title') ?? 'Contact Us',
                    'subtitle' => LocaleHelper::transAttr($setting, 'hero_subtitle') ?? "We're Here to Help",
                    'backgroundImage' => $imgUrl,
                    'leftBadge' => LocaleHelper::transAttr($setting, 'hero_left_badge'),
                    'rightBadge' => LocaleHelper::transAttr($setting, 'hero_right_badge'),
                ];
            });

            return ApiResponseHelper::success($data);
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'CONTACT_HERO_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get contact map data.
     */
    public function map()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "contact_map_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                $setting = ContactSetting::get();
                return [
                    'mapUrl' => $setting->map_url ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3312.0!2d35.5!3d33.9!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzPCsDU0JzAwLjAiTiAzNcKwMzAnMDAuMCJF!5e0!3m2!1sen!2slb!4v1234567890',
                ];
            });

            return ApiResponseHelper::success($data);
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'CONTACT_MAP_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Get contact details.
     */
    public function details()
    {
        try {
            $locale = app()->getLocale();
            $cacheKey = "contact_details_{$locale}";

            $data = Cache::remember($cacheKey, CacheService::TTL_STATIC, function () {
                $setting = ContactSetting::get();
                return [
                    'title' => LocaleHelper::transAttr($setting, 'details_title') ?? 'Contact Detail',
                    'address' => LocaleHelper::transAttr($setting, 'address') ?? '',
                    'email' => $setting->email ?? '',
                    'phone' => $setting->phone ?? '',
                    'fax' => $setting->fax ?? '',
                    'aboutTitle' => LocaleHelper::transAttr($setting, 'about_title') ?? 'About Us',
                    'aboutText' => LocaleHelper::transAttr($setting, 'about_text') ?? '',
                ];
            });

            return ApiResponseHelper::success($data);
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'CONTACT_DETAILS_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }

    /**
     * Send contact message.
     */
    public function sendMessage(SendMessageRequest $request)
    {
        try {
            $validated = $request->validated();

            ContactMessage::create($validated);

            return ApiResponseHelper::success(
                null,
                __('messages.contact_message_sent')
            );
        } catch (\Exception $e) {
            return ApiResponseHelper::error(
                'SEND_MESSAGE_ERROR',
                __('errors.server_error'),
                500
            );
        }
    }
}
