<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingTelegramNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            return;
        }

        $bikeName = $this->booking->bike->name ?? 'Неизвестный байк';
        $days = \Carbon\Carbon::parse($this->booking->start_date)->diffInDays(\Carbon\Carbon::parse($this->booking->end_date)) + 1;
        $formattedPrice = number_format($this->booking->total_price, 0, ',', ' ');
        $formattedSnapshot = number_format($this->booking->price_per_day_snapshot, 0, ',', ' ');

        $message = "🏍 *Новая бронь*\n\n"
            . "Байк: {$bikeName}\n"
            . "Даты: {$this->booking->start_date->format('d.m.Y')} — {$this->booking->end_date->format('d.m.Y')}\n"
            . "Дней: {$days} (по {$formattedSnapshot} ₽/д)\n"
            . "Цена: {$formattedPrice} ₽\n\n"
            . "Клиент: {$this->booking->customer_name}\n"
            . "Телефон: {$this->booking->phone}\n";
            
        if ($this->booking->source) {
            $message .= "Источник: {$this->booking->source}\n";
        }
        if ($this->booking->customer_comment) {
            $message .= "Комментарий: {$this->booking->customer_comment}\n";
        }

        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }
}
