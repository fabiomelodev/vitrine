<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Courtesy = 'courtesy';

    public function getLabel(): string
    {
        return match ($this) {
            self::Trialing => 'Em teste',
            self::Active => 'Ativa',
            self::PastDue => 'Pagamento atrasado',
            self::Canceled => 'Cancelada',
            self::Courtesy => 'Cortesia',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Trialing => 'info',
            self::Active => 'success',
            self::PastDue => 'warning',
            self::Canceled => 'danger',
            self::Courtesy => 'gray',
        };
    }
}
