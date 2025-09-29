<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'state',
        'amount',
        'currency',
        'provider',
        'phone',
        'c_number',
        'buyer',
        'key_code',
        'response',
        'request',
        'expire_at',
        'transaction_ref',
    ];

    protected function casts()
    {
        return [
            'response'  => 'array',
            'request'   => 'array',
            'expire_at' => 'datetime'
        ];
    }
}
