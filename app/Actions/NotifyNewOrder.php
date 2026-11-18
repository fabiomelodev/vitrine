<?php

namespace App\Actions;

use App\Models\Order;
use App\Notifications\NewOrderReceived;
use App\Support\Format;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Avisa a loja de um pedido novo: sininho do painel + e-mail.
 * Falhas aqui nunca impedem o cliente de concluir o pedido.
 */
class NotifyNewOrder
{
    public function handle(Order $order): void
    {
        $store = $order->store;
        $url = url("painel/{$store->slug}/orders/{$order->getKey()}");

        try {
            $notification = FilamentNotification::make()
                ->title("Novo pedido #{$order->code}")
                ->body($order->customer_name.' · '.Format::money($order->total).' · '.$store->name)
                ->icon('heroicon-o-shopping-bag')
                ->success()
                ->actions([
                    Action::make('ver')->label('Ver pedido')->url($url),
                ])
                ->toDatabase();

            // notifyNow: grava na hora. O sendToDatabase() do Filament usa fila (ShouldQueue),
            // e na hospedagem compartilhada não há worker para processá-la.
            foreach ($store->users()->get() as $user) {
                $user->notifyNow($notification);
            }
        } catch (Throwable $e) {
            report($e);
        }

        if (! $store->notify_new_orders) {
            return;
        }

        try {
            $emails = $store->orderNotificationEmails();

            if ($emails !== []) {
                Notification::route('mail', $emails)->notify(new NewOrderReceived($order));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
