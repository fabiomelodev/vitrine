<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Models\Product;
use App\Models\Store;
use App\Notifications\NewOrderReceived;
use App\Support\Cart;
use App\Support\ImageThumbs;
use App\Support\VariantGrid;
use Filament\Notifications\DatabaseNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopkeeperToolsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->store = Store::where('slug', 'atelierlume')->firstOrFail();
    }

    public function test_variant_grid_round_trip_keeps_ids_and_marks_sold_out(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();
        $state = VariantGrid::fromProduct($product);

        $this->assertSame(['Off-white', 'Areia', 'Preto'], array_column($state['color_options'], 'name'));
        $this->assertSame(['PP', 'P', 'M', 'G', 'GG'], $state['size_options']);

        $offWhiteM = $product->variants()->where(['color' => 'Off-white', 'size' => 'M'])->value('id');

        // Remove a cor Preto, adiciona XG e marca Off-white M como esgotado.
        $state['color_options'] = array_values(array_filter($state['color_options'], fn ($c) => $c['name'] !== 'Preto'));
        $state['size_options'][] = 'XG';
        $state['soldout'][VariantGrid::colorKey('Off-white')] = ['M'];

        VariantGrid::sync($product, $state);

        $variants = $product->variants()->get();
        $this->assertCount(12, $variants); // 2 cores × 6 tamanhos
        $this->assertFalse($variants->contains('color', 'Preto'));
        $this->assertSame($offWhiteM, $variants->where('color', 'Off-white')->firstWhere('size', 'M')->id);
        $this->assertFalse($variants->where('color', 'Off-white')->firstWhere('size', 'M')->is_available);
        $this->assertTrue($variants->where('color', 'Areia')->firstWhere('size', 'XG')->is_available);
    }

    public function test_color_photos_with_fallback_to_general_photos(): void
    {
        Storage::fake('public');

        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();
        $product->update([
            'images' => ['stores/1/products/geral.jpg'],
            'color_images' => ['Preto' => ['stores/1/products/preto-1.jpg', 'stores/1/products/preto-2.jpg']],
        ]);

        $this->assertSame(['stores/1/products/preto-1.jpg', 'stores/1/products/preto-2.jpg'], $product->imagesForColor('Preto'));
        $this->assertSame(['stores/1/products/geral.jpg'], $product->imagesForColor('Areia'));
        $this->assertSame('stores/1/products/preto-1.jpg', $product->coverImage('Preto'));
        $this->assertSame('stores/1/products/geral.jpg', $product->coverImage());
        $this->assertCount(3, $product->allImagePaths());

        $state = VariantGrid::fromProduct($product);
        $preto = collect($state['color_options'])->firstWhere('name', 'Preto');
        $this->assertSame(['stores/1/products/preto-1.jpg', 'stores/1/products/preto-2.jpg'], $preto['images']);
    }

    public function test_removed_color_photos_are_deleted(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('Extensão GD não instalada.');
        }

        Storage::fake('public');

        $path = UploadedFile::fake()->image('preto.jpg', 800, 1000)->store('stores/1/products', 'public');
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();

        $product->update(['color_images' => ['Preto' => [$path]]]);
        Storage::disk('public')->assertExists(ImageThumbs::thumbPath($path));

        $product->update(['color_images' => null]);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing(ImageThumbs::thumbPath($path));
    }

    public function test_variant_grid_without_colors_or_sizes_removes_variants(): void
    {
        $product = Product::where('slug', 'bolsa-tote-couro-cru')->firstOrFail();

        VariantGrid::sync($product, ['color_options' => [], 'size_options' => [], 'soldout' => []]);

        $this->assertSame(0, $product->variants()->count());
    }

    public function test_new_order_notifies_store_by_email_and_panel(): void
    {
        Notification::fake();
        $this->store->update(['email' => 'pedidos@atelierlume.test']);

        $product = Product::where('slug', 'bolsa-tote-couro-cru')->with('variants')->firstOrFail();
        Cart::for($this->store)->add($product->id, $product->variants->first()->id, 1);

        $order = app(PlaceOrder::class)->handle($this->store, Cart::for($this->store)->lines(), 'Ana', '(11) 91234-5678');

        Notification::assertSentOnDemand(
            NewOrderReceived::class,
            fn ($notification, $channels, $notifiable) => $notification->order->is($order)
                && $notifiable->routes['mail'] === ['pedidos@atelierlume.test'],
        );

        $owner = $this->store->users()->firstOrFail();
        Notification::assertSentTo($owner, DatabaseNotification::class);
    }

    public function test_panel_notification_is_saved_immediately_even_with_database_queue(): void
    {
        // Igual ao .env local/produção: QUEUE_CONNECTION=database e nenhum worker rodando.
        config(['queue.default' => 'database']);

        $product = Product::where('slug', 'bolsa-tote-couro-cru')->with('variants')->firstOrFail();
        Cart::for($this->store)->add($product->id, $product->variants->first()->id, 1);

        $order = app(PlaceOrder::class)->handle($this->store, Cart::for($this->store)->lines(), 'Ana', '(11) 91234-5678');

        $owner = $this->store->users()->firstOrFail();

        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame('Novo pedido #'.$order->code, $owner->notifications()->first()->data['title']);
    }

    public function test_email_can_be_turned_off(): void
    {
        Notification::fake();
        $this->store->update(['notify_new_orders' => false]);

        $product = Product::where('slug', 'bolsa-tote-couro-cru')->with('variants')->firstOrFail();
        Cart::for($this->store)->add($product->id, $product->variants->first()->id, 1);

        app(PlaceOrder::class)->handle($this->store, Cart::for($this->store)->lines(), 'Ana', '(11) 91234-5678');

        Notification::assertSentOnDemandTimes(NewOrderReceived::class, 0);
    }

    public function test_product_photos_get_thumbnails_and_are_cleaned_up(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('Extensão GD não instalada.');
        }

        Storage::fake('public');

        $path = UploadedFile::fake()->image('foto.jpg', 1200, 1600)->store('stores/1/products', 'public');
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();

        $product->update(['images' => [$path]]);

        Storage::disk('public')->assertExists(ImageThumbs::thumbPath($path));
        [$width] = getimagesizefromstring(Storage::disk('public')->get(ImageThumbs::thumbPath($path)));
        $this->assertSame(ImageThumbs::WIDTH, $width);

        $product->update(['images' => []]);

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing(ImageThumbs::thumbPath($path));
    }
}
