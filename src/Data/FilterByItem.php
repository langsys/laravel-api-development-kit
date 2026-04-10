<?php

namespace Langsys\ApiKit\Data;

class FilterByItem extends BaseInternalData
{
    public function __construct(
        public string $field,
        public FilterByCondition $condition,
    ) {
    }
}
