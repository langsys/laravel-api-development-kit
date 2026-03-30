<?php

namespace Langsys\ApiKit\Traits;

use Illuminate\Support\Str;

trait Uuid
{
    protected static function bootUuid(): void
    {
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
