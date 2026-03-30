<?php

namespace Langsys\ApiKit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BindTo
{
    public function __construct(
        public string $modelClass
    ) {}
}
