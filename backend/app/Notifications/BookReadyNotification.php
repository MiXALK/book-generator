<?php

namespace App\Notifications;

use App\Models\BookGeneration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BookGeneration $generation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'mail',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $readerUrl = rtrim((string) config('services.observability.book_reader_url'), '/')
            .'/'.$this->generation->id;

        return (new MailMessage)
            ->subject('Your personalized book is ready')
            ->greeting('Hello!')
            ->line('Your personalized children\'s book has finished generating and is ready to read.')
            ->action('Read your book', $readerUrl)
            ->line('Thank you for using '.config('app.name', 'Book Generator').'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'generation_id' => $this->generation->id,
            'correlation_id' => $this->generation->correlation_id,
        ];
    }
}
