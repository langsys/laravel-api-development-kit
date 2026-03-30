<?php

namespace Langsys\ApiKit\ApiKey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Langsys\ApiKit\Traits\Uuid;
use Illuminate\Support\Str;

abstract class ApiKey extends Model
{
    use Uuid, SoftDeletes;

    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key',
        'is_active',
    ];

    protected $hidden = [
        'key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function generate(): string
    {
        return Str::random(64);
    }

    public static function getByKey(?string $key): ?static
    {
        if (!$key) {
            return null;
        }

        return static::where('key', $key)->first();
    }

    public static function isValidKey(?string $key): bool
    {
        if (!$key) {
            return false;
        }

        $apiKey = static::getByKey($key);

        return $apiKey && $apiKey->is_active;
    }

    public static function isValidName(?string $name): bool
    {
        return $name && preg_match('/^[a-z0-9-]{1,255}$/', $name);
    }

    public static function keyExists(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    public static function nameExists(string $name): bool
    {
        return static::where('name', $name)->exists();
    }

    public function permissions()
    {
        return $this->belongsToMany(
            config('api-kit.api_key.permission_model', ApiKeyPermission::class),
            'api_key_permissions',
            'api_key_id',
            'permission_id'
        );
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('value', $permission)->exists();
    }
}
