<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'type',
        'floor',
        'capacity',
        'price',
        'status',
        'image',
    ];
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservations::class);
    }
    public function historys(): HasMany
    {
        return $this->hasMany(Historys::class);
    }
}