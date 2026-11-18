<div>
    <button type="button" class="cart-btn" x-data @click="$dispatch('open-cart')" aria-label="Abrir sacola, {{ $count }} {{ $count === 1 ? 'peça' : 'peças' }}">
        <x-storefront.icon name="bag" :size="18" />
        <span class="label">Sacola</span>
        <span class="count">{{ $count }}</span>
    </button>
</div>
