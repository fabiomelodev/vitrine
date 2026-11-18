<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Confirmed = 'confirmed';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::InProgress => 'Em atendimento',
            self::Confirmed => 'Confirmado',
            self::Canceled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'success',
            self::InProgress => 'warning',
            self::Confirmed => 'primary',
            self::Canceled => 'gray',
        };
    }
}
