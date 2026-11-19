<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\ProductOptions;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Support\Cart;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->store = Store::where('slug', 'atelierlume')->firstOrFail();
    }

    public function test_store_logo_is_favicon_and_share_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->store->update(['logo_path' => 'stores/logo-teste.png']);
        $logo = url(\Illuminate\Support\Facades\Storage::disk('public')->url('stores/logo-teste.png'));

        $this->get('/atelierlume')
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.e(\Illuminate\Support\Facades\Storage::disk('public')->url('stores/logo-teste.png')).'">', false)
            ->assertSee('<meta property="og:image" content="'.e($logo).'">', false);

        $product = $this->store->products()->firstOrFail();
        $product->update(['images' => ['products/foto-teste.jpg'], 'color_images' => []]);

        $this->get('/atelierlume/p/'.$product->slug)
            ->assertOk()
            ->assertSee('products/foto-teste.jpg', false)
            ->assertSee('summary_large_image', false);
    }

    public function test_store_without_logo_has_no_custom_favicon(): void
    {
        $this->store->update(['logo_path' => null]);

        $this->get('/atelierlume')->assertOk()->assertDontSee('rel="icon"', false);
    }

    public function test_public_pages_load(): void
    {
        $category = $this->store->categories()->firstOrFail();
        $product = $this->store->products()->firstOrFail();

        $this->get('/atelierlume')->assertOk()->assertSee('Atelier Lume');
        $this->get('/atelierlume/produtos')->assertOk();
        $this->get('/atelierlume/c/'.$category->slug)->assertOk()->assertSee($category->name);
        $this->get('/atelierlume/p/'.$product->slug)->assertOk()->assertSee($product->name);
        $this->get('/atelierlume/busca?q=linho')->assertOk();
        $this->get('/atelierlume/sacola')->assertOk();
        $this->get('/atelierlume/favoritos')->assertOk();
        $this->get('/atelierlume/sobre')->assertOk();
    }

    public function test_home_shows_active_banners_with_links(): void
    {
        $category = $this->store->categories()->firstOrFail();

        $this->store->banners()->create([
            'title' => 'Coleção Verão',
            'image_path' => 'stores/1/banners/verao.jpg',
            'link_type' => 'category',
            'link_category_id' => $category->id,
        ]);
        $this->store->banners()->create([
            'title' => 'Banner escondido',
            'image_path' => 'stores/1/banners/old.jpg',
            'is_active' => false,
        ]);

        $this->get('/atelierlume')
            ->assertOk()
            ->assertSee('alt="Coleção Verão"', false)
            ->assertSee(route('storefront.category', ['atelierlume', $category->slug]), false)
            ->assertDontSee('Banner escondido');
    }

    public function test_missing_product_shows_store_404(): void
    {
        $this->get('/atelierlume/p/nao-existe')->assertNotFound()->assertSee('não está mais aqui');
        $this->get('/loja-que-nao-existe')->assertNotFound();
    }

    public function test_suspended_store_is_unavailable(): void
    {
        $this->store->update(['is_active' => false]);

        $this->get('/atelierlume')->assertStatus(503)->assertSee('fora do ar');
    }

    public function test_reserved_paths_are_not_stores(): void
    {
        $this->get('/painel/login')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_product_requires_size_before_adding_to_cart(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();

        Livewire::test(ProductOptions::class, ['storeId' => $this->store->id, 'productId' => $product->id])
            ->call('addToCart')
            ->assertSet('error', 'Escolha um tamanho para continuar.')
            ->call('selectSize', 'M')
            ->call('addToCart')
            ->assertSet('error', null)
            ->assertDispatched('cart-updated');

        $this->assertSame(1, Cart::for($this->store)->count());
    }

    public function test_choosing_a_color_tells_the_gallery_to_switch_photos(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();

        Livewire::test(ProductOptions::class, ['storeId' => $this->store->id, 'productId' => $product->id])
            ->call('selectColor', 'Preto')
            ->assertSet('color', 'Preto')
            ->assertDispatched('color-selected', color: 'Preto');
    }

    public function test_product_page_and_listing_load_with_color_photos(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();
        $product->update(['color_images' => ['Preto' => ['stores/1/products/preto-1.jpg']]]);

        $this->get('/atelierlume/p/'.$product->slug)->assertOk();
        $this->get('/atelierlume/produtos?cor[0]=Preto')->assertOk();
    }

    public function test_checkout_creates_order_and_redirects_to_confirmation(): void
    {
        $product = Product::where('slug', 'bolsa-tote-couro-cru')->with('variants')->firstOrFail();
        $variant = $product->variants->firstWhere('is_available', true);

        Cart::for($this->store)->add($product->id, $variant->id, 2);

        $component = Livewire::test(CartDrawer::class, ['storeId' => $this->store->id])
            ->call('startCheckout')
            ->set('name', 'Ana Paula')
            ->set('phone', '11912345678')
            ->call('submit')
            ->assertHasNoErrors();

        $order = Order::latest('id')->firstOrFail();

        $component->assertRedirect(route('storefront.order', ['storeSlug' => 'atelierlume', 'token' => $order->public_token]));

        $this->assertSame('Ana Paula', $order->customer_name);
        $this->assertSame('11912345678', $order->customer_phone);
        $this->assertSame(2, $order->items()->sum('quantity'));
        $this->assertEquals(round((float) $product->price * 2, 2), (float) $order->total);
        $this->assertStringContainsString('Sou Ana e quero fazer o pedido #'.$order->code, $order->whatsapp_message);
        $this->assertSame(0, Cart::for($this->store)->count());

        $this->get('/atelierlume/pedido/'.$order->public_token)
            ->assertOk()
            ->assertSee('#'.$order->code)
            ->assertSee('wa.me/5511900000000', false);
    }

    public function test_checkout_validates_phone(): void
    {
        $product = Product::where('slug', 'bolsa-tote-couro-cru')->with('variants')->firstOrFail();
        Cart::for($this->store)->add($product->id, $product->variants->first()->id, 1);

        Livewire::test(CartDrawer::class, ['storeId' => $this->store->id])
            ->call('startCheckout')
            ->set('name', 'Ana')
            ->set('phone', '1234')
            ->call('submit')
            ->assertHasErrors(['phone']);

        $this->assertSame(0, Order::where('customer_name', 'Ana')->count());
    }
}
