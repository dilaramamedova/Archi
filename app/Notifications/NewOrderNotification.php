<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Sent to a seller when one of their products is ordered.
 *
 * $sellerItems holds only the OrderItem rows that belong to the notified seller —
 * an order can span several sellers, so the totals here are per-seller, not per-order.
 * Dispatched by the order pipeline (OrderController), not from this class.
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Collection $sellerItems,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = $this->sellerItems->sum(fn ($item): float => (float) $item->total);

        $message = (new MailMessage)
            ->subject(t('notifications.new_order.subject', ['number' => $this->order->order_number]))
            ->greeting(t('notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name]))
            ->line(t('notifications.new_order.line', ['number' => $this->order->order_number]));

        foreach ($this->sellerItems as $item) {
            $name = $item->product_snapshot['name'] ?? $item->product?->name ?? '—';
            $message->line(sprintf('• %s × %d', $name, (int) $item->quantity));
        }

        return $message
            ->line(t('notifications.new_order.total', ['total' => number_format($total, 2)]))
            ->line(t('notifications.signature'));
    }
}
