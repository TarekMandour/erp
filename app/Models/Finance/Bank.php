<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function accounts()
    {
        return $this->hasMany(BankAccount::class);
    }
}
