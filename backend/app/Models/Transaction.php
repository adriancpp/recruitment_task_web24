<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
        'created_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
