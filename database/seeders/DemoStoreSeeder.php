<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\StoreRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Loja de exemplo "Atelier Lume", igual à dos protótipos.
 */
class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'lojista@provador.test'],
            ['name' => 'Lojista Demo', 'password' => 'password'],
        );

        $store = Store::query()->updateOrCreate(
            ['slug' => 'atelierlume'],
            [
                'name' => 'Atelier Lume',
                'whatsapp' => '11900000000',
                'instagram' => 'atelierlume',
                'description' => 'Moda feminina leve e atemporal.',
                'city' => 'Mogi das Cruzes',
                'state' => 'SP',
                'opening_hours' => 'Seg. a sex., 9h às 19h · Sáb., 9h às 14h',
                'order_prefix' => 'AL',
                'is_active' => true,
                // Loja de demonstração não expira.
                'subscription_status' => 'courtesy',
            ],
        );

        $store->users()->syncWithoutDetaching([$owner->id => ['role' => StoreRole::Owner->value]]);

        $categories = collect([
            'Vestidos', 'Calças e saias', 'Camisas', 'Alfaiataria', 'Calçados', 'Bolsas', 'Acessórios',
        ])->mapWithKeys(fn (string $name, int $i) => [
            $name => $store->categories()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'position' => $i],
            ),
        ]);

        $colors = [
            'Off-white' => '#EFEAE2', 'Preto' => '#1C1A17', 'Areia' => '#CDBBA0',
            'Caramelo' => '#9A6B43', 'Oliva' => '#6B6B3E', 'Vinho' => '#6E2A33', 'Jeans' => '#4A5D78',
        ];

        $catalog = [
            ['Vestido midi de linho', 'Vestidos', 289.90, null, ['Off-white', 'Areia', 'Preto'], ['PP', 'P', 'M', 'G', 'GG'], true],
            ['Calça pantalona alfaiataria', 'Calças e saias', 219.90, null, ['Preto', 'Areia'], ['36', '38', '40', '42', '44'], false],
            ['Camisa oversized algodão', 'Camisas', 159.90, 199.90, ['Off-white', 'Jeans'], ['P', 'M', 'G', 'GG'], false],
            ['Sandália tiras couro', 'Calçados', 249.90, null, ['Caramelo', 'Preto'], ['34', '35', '36', '37', '38', '39'], false],
            ['Bolsa tote couro cru', 'Bolsas', 329.90, null, ['Areia', 'Caramelo'], [], true],
            ['Blazer alongado', 'Alfaiataria', 389.90, null, ['Oliva', 'Preto'], ['P', 'M', 'G', 'GG'], true],
            ['Saia midi plissada', 'Calças e saias', 179.90, 229.90, ['Vinho', 'Preto'], ['P', 'M', 'G'], false],
            ['Chapéu de palha', 'Acessórios', 119.90, null, [], ['P', 'M', 'G'], false],
        ];

        $products = [];

        foreach ($catalog as $i => [$name, $category, $price, $compareAt, $productColors, $sizes, $featured]) {
            $product = $store->products()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category_id' => $categories[$category]->id,
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'description' => 'Peça de exemplo para demonstração do catálogo.',
                    'is_featured' => $featured,
                    'position' => $i,
                ],
            );

            $product->variants()->delete();
            $position = 0;

            foreach ($productColors ?: [null] as $color) {
                foreach ($sizes ?: [null] as $size) {
                    $product->variants()->create([
                        'color' => $color,
                        'color_hex' => $color ? $colors[$color] : null,
                        'size' => $size,
                        // Alguns tamanhos esgotados para testar a vitrine.
                        'is_available' => ! in_array($size, ['PP', 'GG', '34'], true),
                        'position' => $position++,
                    ]);
                }
            }

            $products[$name] = $product;
        }

        // "Complete o look"
        $products['Vestido midi de linho']->related()->sync([
            $products['Sandália tiras couro']->id,
            $products['Bolsa tote couro cru']->id,
            $products['Chapéu de palha']->id,
        ]);

        if ($store->orders()->doesntExist()) {
            $this->seedOrders($store, $products);
        }
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function seedOrders(Store $store, array $products): void
    {
        $orders = [
            ['Mariana', '11912345678', OrderStatus::New, [['Vestido midi de linho', 'Off-white', 'M', 1], ['Sandália tiras couro', 'Caramelo', '36', 1], ['Bolsa tote couro cru', 'Areia', null, 1]]],
            ['Juliana', '11987654321', OrderStatus::InProgress, [['Calça pantalona alfaiataria', 'Preto', '38', 1], ['Blazer alongado', 'Oliva', 'M', 1]]],
            ['Patrícia', '11955554444', OrderStatus::Confirmed, [['Vestido midi de linho', 'Areia', 'P', 1], ['Chapéu de palha', null, 'M', 1]]],
            ['Carla', '11933332222', OrderStatus::Canceled, [['Sandália tiras couro', 'Preto', '37', 1]]],
        ];

        foreach ($orders as [$customer, $phone, $status, $items]) {
            $subtotal = collect($items)->sum(fn ($item) => $products[$item[0]]->price * $item[3]);
            $pieces = collect($items)->sum(fn ($item) => $item[3]);
            $discount = $pieces >= 3 ? round($subtotal * 0.10, 2) : 0;

            /** @var Order $order */
            $order = $store->orders()->create([
                'customer_name' => $customer,
                'customer_phone' => $phone,
                'status' => $status,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $subtotal - $discount,
            ]);

            foreach ($items as [$name, $color, $size, $qty]) {
                $product = $products[$name];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'color' => $color,
                    'size' => $size,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'total' => $product->price * $qty,
                ]);
            }
        }
    }
}
