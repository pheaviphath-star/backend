<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservations extends Model
{
    use HasFactory;

    protected $table = 'reservations';

    protected $fillable = [
        'guest_id',
        'room_id',
        'check_in',
        'check_out',
        'status',
        'total',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guests::class, 'guest_id');
    }
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
    public function historys(): HasMany
    {
        return $this->hasMany(Historys::class);
    }
}