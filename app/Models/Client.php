<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'phone',
        'name',
        'c_number',
        'address',
        'provider',
    ];

    protected function casts()
    {
        return [];
    }
}
