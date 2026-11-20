<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un client qui suit une boutique. */
class ShopLink extends Model
{
    protected $fillable = ['customer_id', 'shop_id', 'person_uid', 'first_purchase_at'];

    protected function casts(): array
    {
        return ['first_purchase_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shop_id');
    }
}
