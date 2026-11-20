<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrder extends Model
{
    protected $fillable = ['uid', 'customer_id', 'shop_id', 'items', 'total', 'note', 'status', 'mode', 'ordered_at', 'status_at'];

    protected function casts(): array
    {
        return ['items' => 'array', 'total' => 'integer', 'ordered_at' => 'integer', 'status_at' => 'datetime'];
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
