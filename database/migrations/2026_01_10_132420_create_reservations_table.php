<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// guest_id(name,email),
// room_id(number,type),
// check_in,
// check_out,
// status(Pending, Confirmed, Checked In, Checked Out, Cancelled), 
// total
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->date('check_in');
            $table->date('check_out');
            $table->enum('status', ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'])->default('Pending');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};