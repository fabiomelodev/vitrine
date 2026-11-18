<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StoreRole: string implements HasLabel
{
    case Owner = 'owner';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Dono(a)',
            self::Staff => 'Equipe',
        };
    }
}
