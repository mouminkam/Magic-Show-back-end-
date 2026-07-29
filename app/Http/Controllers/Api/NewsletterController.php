<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Newsletter\SubscribeRequest;
use App\Jobs\SendNewsletterWelcomeJob;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter.
     */
    public function subscribe(SubscribeRequest $request)
    {
        $email = strtolower(trim($request->validated()['email']));
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            if ($subscriber->isSubscribed()) {
                return ApiResponseHelper::success(
                    ['subscribed' => true],
                    __('messages.newsletter_already_subscribed'),
                    null,
                    200
                );
            }
            $subscriber->update([
                'unsubscribed_at' => null,
                'subscribed_at' => now(),
            ]);
        } else {
            NewsletterSubscriber::create([
                'email' => $email,
                'subscribed_at' => now(),
            ]);
        }

        SendNewsletterWelcomeJob::dispatch($email);

        return ApiResponseHelper::success(
            ['subscribed' => true],
            __('messages.newsletter_subscribed'),
            null,
            201
        );
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->email));
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if (!$subscriber) {
            return ApiResponseHelper::error(
                'NOT_FOUND',
                __('messages.newsletter_not_found'),
                404
            );
        }

        if (!$subscriber->isSubscribed()) {
            return ApiResponseHelper::success(
                ['unsubscribed' => true],
                __('messages.newsletter_already_unsubscribed'),
                null,
                200
            );
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return ApiResponseHelper::success(
            ['unsubscribed' => true],
            __('messages.newsletter_unsubscribed'),
            null,
            200
        );
    }
}
