<?php

namespace App\Jobs;

use App\Mail\NewsletterWelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterWelcomeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new NewsletterWelcomeMail($this->email));
    }
}
