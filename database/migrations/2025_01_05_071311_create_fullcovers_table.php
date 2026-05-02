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
        Schema::create('fullcover', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('date')->nullable();
            $table->string('currency')->nullable();
            $table->string('number_of_policies')->nullable();
            $table->string('deposits')->nullable();
            $table->string('transaction_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fullcovers');
    }
};
