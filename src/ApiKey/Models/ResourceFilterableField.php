<?php

namespace Langsys\ApiKit\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceFilterableField extends Model
{
    protected $fillable = ['api_resource_id', 'field_name', 'field_type', 'enum_class'];

    public function apiResource()
    {
        return $this->belongsTo(ApiResource::class);
    }

    public function defaultFilters()
    {
        return $this->hasMany(ResourceDefaultFilter::class, 'filterable_field_id');
    }
}
