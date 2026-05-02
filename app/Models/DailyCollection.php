<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCollection extends Model
{
    /** @use HasFactory<\Database\Factories\DailyCollectionFactory> */
    use HasFactory;
    protected $table ='daily_collections';
    protected $fillable = [
        'currency',
        'third_party_premiums',
        'full_cover_premiums',
        'zinara_fees',
        'usd_mpos',
        'zwg_mpos',
        'zwg_third_party_premiums',
        'zwg_full_cover_premiums',
        'zwg_zinara_fees',
        'usd_total_deposited',
        'zwg_total_deposited',
        'usd_cash',
        'usd_swipe',
        'usd_transfers',
        'zwg_cash',
        'zwg_swipe',
        'zwg_transfers',
        'bank',
        'cash_deposited',
        'zwg_cash_deposited',
        'comments',
        'user_id',
        'insurance_transactions',
        'zwg_insurance_transactions',
        'zinara_transactions',
        'username',
        'networkid',
        'siteid',
        'balance',
        'POS',
        'code',
        'site_name',
        'zwg_debit_sales',
        'usd_debit_sales',
        'zwg_credit_sales',
        'usd_credit_sales',
        'usd_cash_in_hand',
        'usd_cash_in_hand_balance',
          'zwg_cash_in_hand' ,
        'zwg_cash_in_hand_balance',
        'other_insurances_zwg',
        'other_insurances_usd',
    ];



    public function up()
{
    Schema::table('daily_collections', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id');
        $table->string('username')->nullable(); 
        $table->string('siteid')->nullable(); 
        $table->string('networkid')->nullable(); 
        $table->foreign('user_id')->references('id')->on('users'); 
    });
}

public function site()
{
    return $this->belongsTo(site::class, 'siteid');
}

public function down()
{
    Schema::table('daily_collections', function (Blueprint $table) {
        $table->dropForeign(['user_id']); // Drop the foreign key constraint
        $table->dropColumn(['user_id', 'username']);
        $table->string('siteid')->nullable(); 
        $table->string('networkid')->nullable(); 
    });
}


public function user()
    {
        return $this->belongsTo(User::class);
    }



    public function network()
    {
        return $this->belongsTo(Network::class); // Add this
    }

    public function faceValueUsages()
    {
        return $this->hasMany(FaceValueUsage::class);
    }

}
