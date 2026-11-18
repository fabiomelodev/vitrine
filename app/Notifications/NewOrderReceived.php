<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Format;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail para a loja quando chega um pedido pela vitrine.
 * Enviado na hora (sem fila), porque a hospedagem compartilhada não roda worker.
 */
class NewOrderReceived extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'store']);
        $store = $order->store;

        $mail = (new MailMessage)
            ->subject("Novo pedido #{$order->code} — {$store->name}")
            ->greeting('Chegou um pedido novo!')
            ->line("**{$order->customer_name}** enviou o pedido **#{$order->code}** pela vitrine.")
            ->line('WhatsApp do cliente: '.Format::phone($order->customer_phone));

        foreach ($order->items as $item) {
            $variant = $item->variantLabel();
            $mail->line("• {$item->quantity}x {$item->product_name}".($variant ? " ({$variant})" : '').' — '.Format::money($item->total));
        }

        return $mail
            ->line('**Total estimado: '.Format::money($order->total).'**')
            ->action('Ver pedido no painel', url("painel/{$store->slug}/orders/{$order->getKey()}"))
            ->line('O cliente deve chamar você no WhatsApp com o código do pedido. Se não chamar, você pode chamá-lo pelo botão no painel.');
    }
}
