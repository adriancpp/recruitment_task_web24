<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
        'created_at',
    ];

    protected $casts = [
        'import_id' => 'integer',
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
