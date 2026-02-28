<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guests extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'phone',
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
