<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerEntry extends Model
{
    protected $fillable = ['person_id', 'amount', 'pay', 'label', 'happened_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'pay' => 'boolean',
            'happened_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LedgerItem::class)->orderBy('id');
    }
}
