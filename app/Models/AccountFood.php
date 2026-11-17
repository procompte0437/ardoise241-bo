<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountFood extends Model
{
    protected $table = 'account_foods';

    protected $fillable = ['account_id', 'uid', 'name', 'price', 'position', 'image_data', 'image_hash', 'deleted', 'client_updated_at', 'rev'];

    protected $hidden = ['image_data'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'position' => 'integer', 'deleted' => 'boolean', 'client_updated_at' => 'integer', 'rev' => 'integer'];
    }
}
