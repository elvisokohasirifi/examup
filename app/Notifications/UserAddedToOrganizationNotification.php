<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class UserAddedToOrganizationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $addedUser,
        private readonly User $addedBy,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->addedUser->organization?->name ?? config('app.name');
        $department = $this->addedUser->department?->name ?? 'Not assigned';
        $role = $this->addedUser->isDepartmentAdmin()
            ? 'Department administrator'
            : Str::headline($this->addedUser->role);

        return (new MailMessage)
            ->subject("You've been added to {$organization} on ExamUP")
            ->greeting("Hello {$this->addedUser->name},")
            ->line("{$this->addedBy->name} has added you to {$organization} on ExamUP.")
            ->line("Role: {$role}")
            ->line("Department: {$department}")
            ->action('Sign in to ExamUP', backpack_url('login'))
            ->line('Use the email address this message was sent to and the password provided by your administrator to sign in.');
    }
}
