<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $fillable = ['name', 'debt', 'position'];

    protected function casts(): array
    {
        return [
            'debt' => 'integer',
            'position' => 'integer',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)->orderBy('happened_at')->orderBy('id');
    }
}
