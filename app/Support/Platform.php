<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Dados da empresa dona do Provador (opcionais), usados no rodapé e nos termos.
 */
class Platform
{
    public static function companyName(): ?string
    {
        return Setting::get('company_name');
    }

    public static function companyDocument(): ?string
    {
        $digits = Document::digits(Setting::get('company_document'));

        return strlen($digits) === 14
            ? sprintf('%s.%s.%s/%s-%s', substr($digits, 0, 2), substr($digits, 2, 3), substr($digits, 5, 3), substr($digits, 8, 4), substr($digits, 12))
            : null;
    }

    /**
     * "Razão Social · CNPJ 00.000.000/0000-00", só com o que estiver preenchido.
     */
    public static function legalLine(): ?string
    {
        $parts = array_filter([
            static::companyName(),
            static::companyDocument() ? 'CNPJ '.static::companyDocument() : null,
        ]);

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * "Razão Social, CNPJ 00.000.000/0000-00", só com o que estiver preenchido.
     */
    public static function identification(): ?string
    {
        $parts = array_filter([
            static::companyName(),
            static::companyDocument() ? 'CNPJ '.static::companyDocument() : null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Trecho para os termos: " operada por Razão Social, CNPJ ..." (ou vazio).
     */
    public static function operatedBy(): string
    {
        $id = static::identification();

        return $id ? ' operada por '.$id : '';
    }
}
