<?php

namespace Langsys\ApiKit\Authorization;

use Langsys\ApiKit\Data\AuthData;
use Langsys\ApiKit\Enums\AuthorizableType;
use Langsys\ApiKit\Contracts\Authorizable;
use Langsys\ApiKit\Contracts\AuthorizableByKey;
use Langsys\ApiKit\Contracts\AuthorizableByUser;
use Langsys\ApiKit\Contracts\GuardableResource;
use Langsys\ApiKit\Traits\AuthDataResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class AccessGuardService
{
    use AuthDataResolver;

    private ?AuthData $authData = null;

    public function authorize(string $permission, ?GuardableResource $entity): void
    {
        $this->authData = authData();

        // Check super admin bypass
        $user = Auth::user();
        if ($user instanceof Authorizable && $user->isSuperAdmin()) {
            return;
        }

        if (!$this->authData || !$entity) {
            throw new AuthorizationException();
        }

        match ($this->authData->authType) {
            AuthorizableType::API_KEY => $this->authorizeApiKey($permission, $entity),
            AuthorizableType::USER => $this->authorizeUser($permission, $entity),
        };
    }

    public function filterByPermission(string $permission, Collection $collection): Collection
    {
        return $collection->filter(function ($item) use ($permission) {
            if (!$item instanceof GuardableResource) {
                return true;
            }

            try {
                $this->authorize($permission, $item);
                return true;
            } catch (AuthorizationException) {
                return false;
            }
        });
    }

    private function authorizeApiKey(string $permission, GuardableResource $entity): void
    {
        $apiKeyModelClass = config('api-kit.api_key.model');

        if (!$apiKeyModelClass) {
            throw new AuthorizationException();
        }

        $apiKey = $apiKeyModelClass::find($this->authData->authId);

        if (!$apiKey || !$apiKey instanceof AuthorizableByKey) {
            throw new AuthorizationException();
        }

        if (!$apiKey->keyHasPermission($permission) || !$apiKey->keyBelongsToEntity($entity)) {
            throw new AuthorizationException();
        }
    }

    private function authorizeUser(string $permission, GuardableResource $entity): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthorizationException();
        }

        if ($user instanceof Authorizable && $user->isSuperAdmin()) {
            return;
        }

        if (!$user instanceof AuthorizableByUser) {
            throw new AuthorizationException();
        }

        $role = $user->userRoleInEntity($entity);

        if (!$role || !$user->roleHasPermission($role, $permission) || $user->userHasDisabledEntity($entity)) {
            throw new AuthorizationException();
        }
    }
}
