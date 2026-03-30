<?php

namespace Langsys\ApiKit\Contracts;

interface AuthorizableByKey
{
    public function keyHasPermission(string $permission): bool;
    public function keyBelongsToEntity(mixed $entity): bool;
}
