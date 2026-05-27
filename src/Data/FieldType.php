<?php

namespace Langsys\ApiKit\Data;

use Langsys\ApiKit\Data\BaseInternalData;

/**
 * Internal type descriptor for filterable fields.
 * Ported from langsys main (Data/Internal/FieldType) for use in FilterableCollection.
 * Supports date-aware comparisons and proper nullable/enum handling.
 */
class FieldType extends BaseInternalData
{
    public function __construct(
        public string $type,
        public bool $nullable = false,
        public bool $is_date = false,
    ) {
    }
}
