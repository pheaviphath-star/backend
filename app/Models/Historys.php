<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historys extends Model
{
    use HasFactory;

    protected $table = 'historys';

    protected $fillable = [
        'guest_id',
        'room_id',
        'reservation_id',
        'total_stays',
        'status',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guests::class, 'guest_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservations::class, 'reservation_id');
    }
}
