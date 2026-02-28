<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// id,
// guest_id(email,phone),
// room_id(number,type),
// reservation_id(check_in,check_out),
// Total_Stays,
// Status(Current Stay, Upcoming Stay, Past Stay)
// relationship: 
// guest_id references guests(id), room_id references rooms(id), reservation_id references reservations(id)
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained('guests');
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('reservation_id')->constrained('reservations');
            $table->unsignedInteger('total_stays')->default(0);
            $table->enum('status', ['current', 'upcoming', 'past']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historys');
    }
};
