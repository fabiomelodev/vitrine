<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BannerLinkType: string implements HasLabel
{
    case None = 'none';
    case Category = 'category';
    case Product = 'product';
    case Url = 'url';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Sem link',
            self::Category => 'Categoria',
            self::Product => 'Produto',
            self::Url => 'Endereço (URL)',
        };
    }
}
