<?php

namespace Langsys\ApiKit\Contracts;

interface AuthorizableByUser
{
    public function userRoleInEntity(mixed $entity): ?object;
    public function roleHasPermission(object $role, string $permission): bool;
    public function userHasDisabledEntity(mixed $entity): bool;
}
