<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerItem extends Model
{
    protected $fillable = ['ledger_entry_id', 'name', 'qty', 'price'];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }
}
