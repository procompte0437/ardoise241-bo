<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountEntry extends Model
{
    protected $table = 'account_entries';

    protected $fillable = ['account_id', 'uid', 'person_uid', 'amount', 'pay', 'label', 'items', 'happened_at', 'deleted', 'client_updated_at', 'rev'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer', 'pay' => 'boolean', 'items' => 'array', 'happened_at' => 'integer',
            'deleted' => 'boolean', 'client_updated_at' => 'integer', 'rev' => 'integer',
        ];
    }
}
