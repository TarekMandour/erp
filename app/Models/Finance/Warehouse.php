<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'is_active',
        'location',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'location'  => 'array',
    ];
}
