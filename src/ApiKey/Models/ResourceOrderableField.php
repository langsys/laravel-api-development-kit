<?php

namespace Langsys\ApiKit\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceOrderableField extends Model
{
    protected $fillable = ['api_resource_id', 'field_name'];

    public function apiResource()
    {
        return $this->belongsTo(ApiResource::class);
    }

    public function defaultOrderEntries()
    {
        return $this->hasMany(ResourceDefaultOrder::class, 'orderable_field_id');
    }
}
