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
        Schema::create('daily_collections', function (Blueprint $table) {
        $table->id();
        $table->string('currency')->nullable();
        $table->decimal('insurance_transactions', 10, 2)->nullable();
        $table->decimal('zinara_transactions', 10, 2)->nullable();
        $table->decimal('third_party_premiums', 10, 2)->nullable(); // Adjust precision as needed
        $table->decimal('full_cover_premiums', 10, 2)->nullable(); // Adjust precision as needed
        $table->decimal('zinara_fees', 10, 2)->nullable(); // Adjust precision as needed
        $table->decimal('total_cash_collected', 10, 2)->nullable(); // Adjust precision as needed
        $table->decimal('zwg_cash', 10, 2)->nullable();
        $table->decimal('zwg_swipe', 10, 2)->nullable();
        $table->decimal('zwg_transfers', 10, 2)->nullable();
        $table->decimal('usd_cash', 10, 2)->nullable();
        $table->decimal('usd_swipe', 10, 2)->nullable();
        $table->decimal('usd_transfers', 10, 2)->nullable();
        $table->string('bank')->nullable();
        $table->string('code')->nullable();
        $table->string('site_name')->nullable();
        $table->decimal('cash_deposited', 10, 2)->nullable();
        $table->text('comments')->nullable();
        $table->string('usd_debit_sales')->nullable();
        $table->string('usd_credit_sales')->nullable();
        $table->decimal('zwg_credit_sales', 10, 2)->nullable();
        $table->string('zwg_debit_sales')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
