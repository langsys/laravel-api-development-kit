<?php

namespace Langsys\ApiKit\Data;

use Spatie\LaravelData\Data;

class BaseData extends Data
{
    public function filled($additionalData = [], $appendNullsFromRequest = false)
    {
        $fields = array_filter(
            $this->all(),
            fn($value, $key) => $value !== null && property_exists($this, $key),
            ARRAY_FILTER_USE_BOTH
        );

        $originalRequestFields = request()?->json()?->all();

        if ($appendNullsFromRequest && is_array($originalRequestFields)) {
            foreach ($originalRequestFields as $key => $data) {
                if (!property_exists($this, $key)) {
                    continue;
                }
                if (isset($fields[$key])) {
                    continue;
                }
                $fields[$key] = null;
            }
        }

        return [...$fields, ...$additionalData];
    }
}
