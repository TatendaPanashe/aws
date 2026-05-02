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
        Schema::create('csv_data', function (Blueprint $table) {
            $table->id();
            $table->string('id_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('agent')->nullable();
            $table->string('classification')->nullable();
            $table->string('main_agent')->nullable();
            $table->string('issue_date')->nullable();
            $table->string('status')->nullable();
            $table->string('vehicle_reg_no')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('policy_no')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('location')->nullable();
            $table->string('broker_name')->nullable();
            $table->string('approved')->nullable();
            $table->string('amount')->nullable();       
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_data');
    }
};
