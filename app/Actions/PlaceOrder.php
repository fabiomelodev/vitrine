<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Store;
use App\Support\CartLine;
use App\Support\Format;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria o pedido a partir das linhas da sacola e monta a mensagem do WhatsApp.
 * Preços sempre vêm do banco, nunca do navegador.
 */
class PlaceOrder
{
    public function __construct(protected NotifyNewOrder $notifier) {}

    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function handle(Store $store, Collection $lines, string $customerName, string $customerPhone): Order
    {
        $lines = $lines->filter(fn (CartLine $line) => $line->isAvailable())->values();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('A sacola não tem peças disponíveis.');
        }

        $order = DB::transaction(function () use ($store, $lines, $customerName, $customerPhone): Order {
            $subtotal = round($lines->sum(fn (CartLine $line) => $line->total()), 2);

            /** @var Order $order */
            $order = $store->orders()->create([
                'customer_name' => trim($customerName),
                'customer_phone' => $customerPhone,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->getKey(),
                    'product_variant_id' => $line->variant?->getKey(),
                    'product_name' => $line->product->name,
                    'color' => $line->variant?->color,
                    'size' => $line->variant?->size,
                    'quantity' => $line->qty,
                    'unit_price' => $line->unitPrice(),
                    'total' => $line->total(),
                ]);
            }

            $order->update(['whatsapp_message' => $this->message($store, $order, $lines)]);

            return $order;
        });

        $this->notifier->handle($order);

        return $order;
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function message(Store $store, Order $order, Collection $lines): string
    {
        $firstName = Str::of($order->customer_name)->trim()->explode(' ')->first();

        $template = filled($store->order_message_template)
            ? $store->order_message_template
            : 'Olá, {loja}! Sou {cliente} e quero fazer o pedido {codigo}:';

        $opening = strtr($template, [
            '{loja}' => $store->name,
            '{cliente}' => $firstName,
            '{codigo}' => '#'.$order->code,
        ]);

        $items = $lines->map(function (CartLine $line): string {
            $variant = $line->variantLabel();

            return $line->qty.'x '.$line->product->name
                .($variant !== '' ? ' — '.$variant : '')
                .' — '.Format::money($line->total());
        })->implode("\n");

        return $opening."\n".$items."\nTotal estimado: ".Format::money($order->total);
    }
}
