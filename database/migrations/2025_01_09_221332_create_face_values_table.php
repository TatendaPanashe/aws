<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('face_values', function (Blueprint $table) {
            $table->id();
            $table->string('starting')->nullable(); // changed 'starting' to 'starting',
            $table->string('ending')->nullable() ;
            $table->decimal('received', 10, 2)->default(0);;
            $table->decimal('used', 10, 2)->default(0);;
            $table->decimal('closing_balance', 10, 2)->default(0);
             $table->decimal('opening_balance', 10, 2)->default(0);

            $table->integer('clerk_id');
            $table->integer('batch_id');
            $table->integer('supervisor_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_values');
    }
};
