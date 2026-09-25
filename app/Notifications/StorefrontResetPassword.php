<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorefrontResetPassword extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $expire = (int) config('auth.passwords.contacts.expire');

        return (new MailMessage)
            ->subject(__('storefront.auth.reset_mail_subject'))
            ->line(__('storefront.auth.reset_mail_line'))
            ->action(__('storefront.auth.reset_mail_action'), $this->resetUrl($notifiable))
            ->line(__('storefront.auth.reset_mail_expire', ['count' => $expire]))
            ->line(__('storefront.auth.reset_mail_ignore'));
    }

    /**
     * @param  mixed  $notifiable
     */
    public function resetUrl($notifiable): string
    {
        return route('store.auth.password.reset.form', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
