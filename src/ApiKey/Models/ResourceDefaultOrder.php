<?php

namespace Langsys\ApiKit\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceDefaultOrder extends Model
{
    protected $fillable = ['api_resource_id', 'orderable_field_id', 'direction', 'sort_order'];

    public function apiResource()
    {
        return $this->belongsTo(ApiResource::class);
    }

    public function orderableField()
    {
        return $this->belongsTo(ResourceOrderableField::class, 'orderable_field_id');
    }
}
