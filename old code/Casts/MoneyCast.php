<?php

namespace Litepie\Database\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return number_format($value / 100, 2);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return (int) round($value * 100);
    }
}
