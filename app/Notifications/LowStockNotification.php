<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(private Product $product) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $unit  = $this->product->unit?->abbreviation ?? 'unités';
        $stock = (int) $this->product->stock_qty;
        $alert = (int) $this->product->stock_alert;

        $body = $stock <= 0
            ? 'Rupture de stock !'
            : "Stock: {$stock} {$unit} (seuil: {$alert})";

        return WebPushMessage::create()
            ->title('⚠️ Stock faible — ' . $this->product->name)
            ->body($body)
            ->icon('/images/icons/icon-192x192.png')
            ->badge('/images/icons/icon-72x72.png')
            ->data(['product_id' => $this->product->id]);
    }
}
