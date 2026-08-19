<?php

namespace App\Notifications;

use App\Models\ExamAccessLink;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamAccessLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ExamAccessLink $accessLink,
        private readonly string $examUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your exam invitation')
            ->greeting('Hello,')
            ->line('You have been invited to take an online exam.')
            ->line('Exam: '.$this->accessLink->exam->title)
            ->action('Start exam', $this->examUrl)
            ->line('If the button does not work, use this link: '.$this->examUrl);
    }
}
