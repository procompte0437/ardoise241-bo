<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopMessage extends Model
{
    protected $fillable = ['uid', 'shop_id', 'kind', 'title', 'body'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'shop_id');
    }
}
