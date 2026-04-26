<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'transaction_id',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'import_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
