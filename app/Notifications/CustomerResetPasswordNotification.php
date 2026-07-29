<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Must extend Illuminate\Notifications\Notification: the base class supplies the
 * $id / $locale properties that NotificationSender and the notification
 * channels read. Without it, Laravel created $notification->id as a dynamic
 * property (deprecated on PHP 8.2, a fatal error on PHP 9).
 */
class CustomerResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[\SensitiveParameter]
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expire = config('auth.passwords.customers.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }

    protected function resetUrl(object $notifiable): string
    {
        $frontend = config('app.frontend_url', 'http://localhost:3000');
        $email = $notifiable->getEmailForPasswordReset();

        return rtrim($frontend, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($email);
    }
}
