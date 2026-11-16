<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = ['name', 'price', 'image', 'position'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'position' => 'integer',
        ];
    }

    public function imageUrl(): string
    {
        return $this->image ? '/storage/'.$this->image : '';
    }
}
