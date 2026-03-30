<?php

namespace Langsys\ApiKit\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceDefaultFilter extends Model
{
    protected $fillable = ['api_resource_id', 'filterable_field_id', 'filter_value'];

    public function apiResource()
    {
        return $this->belongsTo(ApiResource::class);
    }

    public function filterableField()
    {
        return $this->belongsTo(ResourceFilterableField::class, 'filterable_field_id');
    }
}
