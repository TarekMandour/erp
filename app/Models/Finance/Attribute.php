<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'code'
    ];

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }
}
