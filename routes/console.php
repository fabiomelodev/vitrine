<?php

use App\Models\Product;
use App\Models\StorefrontEvent;
use App\Support\ImageThumbs;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('provador:miniaturas {--todas : Recria também as que já existem}', function () {
    $criadas = 0;

    Product::query()->whereNotNull('images')->chunkById(100, function ($products) use (&$criadas) {
        foreach ($products as $product) {
            foreach ($product->images ?? [] as $path) {
                if ($this->option('todas') || ! ImageThumbs::exists($path)) {
                    $criadas += ImageThumbs::generate($path) ? 1 : 0;
                }
            }
        }
    });

    $this->info("Miniaturas criadas: {$criadas}");
})->purpose('Gera as miniaturas das fotos dos produtos');

// Apaga eventos da vitrine com mais de 13 meses (precisa do agendador: php artisan schedule:run no cron).
Schedule::command('model:prune', ['--model' => [StorefrontEvent::class]])->daily();
