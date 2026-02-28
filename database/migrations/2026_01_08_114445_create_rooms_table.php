<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type');              // Standard, Deluxe, Suite...
            $table->string('floor');             // 1st Floor, 2nd Floor
            $table->unsignedInteger('capacity'); // Max guests
            $table->decimal('price', 8, 2);      // Room price
            $table->enum('status', [
                'available',
                'occupied',
                'cleaning',
                'maintenance'
            ])->default('available');
            $table->string('image')->nullable(); // Image URL
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
        Schema::dropIfExists('rooms');
    }
};
