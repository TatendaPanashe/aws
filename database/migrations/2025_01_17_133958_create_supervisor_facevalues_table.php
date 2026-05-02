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
        
    Schema::create('supervisor_facevalue', function (Blueprint $table) {
        $table->id();
        
        $table->string('starting');
    $table->string('ending');
        $table->decimal('received', 10, 2)->default(0);;
        $table->decimal('allocated', 10, 2)->default(0);
        $table->decimal('balance', 10, 2)->default(0);;
        $table->unsignedBigInteger('batch_id')->nullable();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('assigned_to')->nullable();
        $table->string('new_starting')->nullable();
        $table->timestamps();
    });
}
        
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_facevalues');
    }
};
