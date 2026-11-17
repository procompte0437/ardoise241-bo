<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountPerson extends Model
{
    protected $table = 'account_people';

    protected $fillable = ['account_id', 'uid', 'name', 'position', 'deleted', 'client_updated_at', 'rev'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'deleted' => 'boolean', 'client_updated_at' => 'integer', 'rev' => 'integer'];
    }
}
