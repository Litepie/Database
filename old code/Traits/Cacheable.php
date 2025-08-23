<?php

namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Cache the result of a query.
     */
    public function scopeCacheFor($query, $seconds, $key = null)
    {
        $key = $key ?: md5($query->toSql() . serialize($query->getBindings()));
        return Cache::remember($key, $seconds, function () use ($query) {
            return $query->get();
        });
    }
}
