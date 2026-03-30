<?php

namespace Langsys\ApiKit\Contracts;

interface Authorizable
{
    public function hasPermission(string $permission): bool;
    public function isSuperAdmin(): bool;
}
