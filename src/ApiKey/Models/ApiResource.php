<?php

namespace Langsys\ApiKit\ApiKey\Models;

use Illuminate\Database\Eloquent\Model;

class ApiResource extends Model
{
    protected $fillable = ['name', 'endpoint'];

    public function orderableFields()
    {
        return $this->hasMany(ResourceOrderableField::class);
    }

    public function filterableFields()
    {
        return $this->hasMany(ResourceFilterableField::class);
    }

    public function defaultOrderEntries()
    {
        return $this->hasMany(ResourceDefaultOrder::class)->orderBy('sort_order');
    }

    public function defaultFilters()
    {
        return $this->hasMany(ResourceDefaultFilter::class);
    }
}
