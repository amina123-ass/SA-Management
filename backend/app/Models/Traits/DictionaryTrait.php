<?php

namespace App\Models\Traits;

trait DictionaryTrait
{
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function canBeDeleted(): bool
    {
        return true;
    }
}