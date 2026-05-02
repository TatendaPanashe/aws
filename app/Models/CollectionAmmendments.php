<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionAmmendments extends Model
{
    /** @use HasFactory<\Database\Factories\DailyCollectionFactory> */
    use HasFactory;
    protected $table ='collection_ammendments';
    protected $fillable = [
        'transaction_id',
        'transaction_date',
        'currencyold',
        'third_party_premiumsold',
        'full_cover_premiumsold',
        'zinara_feesold',
        'usd_mposold',
        'zwg_mposold',
        'zwg_third_party_premiumsold',
        'zwg_full_cover_premiumsold',
        'zwg_zinara_feesold',
        'usd_total_depositedold',
        'zwg_total_depositedold',
        'usd_cashold',
        'usd_swipeold',
        'usd_transfersold',
        'zwg_cashold',
        'zwg_swipeold',
        'zwg_transfersold',
        'bankold',
        'cash_depositedold',
        'zwg_cash_depositedold',
        'commentsold',
        'user_idold',
        'insurance_transactionsold',
        'zwg_insurance_transactionsold',
        'zinara_transactionsold',
        'usernameold',
        'networkidold',
        'siteidold',
        'balanceold',
        'codeold',
        'site_nameold',
        'zwg_debit_salesold',
        'usd_debit_salesold',
        'zwg_credit_salesold',
        'usd_credit_salesold',

        
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
        'code',
        'site_name',
        'zwg_debit_sales',
        'usd_debit_sales',
        'zwg_credit_sales',
        'usd_credit_sales',
        'status',
        'userid',
        'siteid',
        'networkid',
        'ammendmentdate',
            'ammendmentapprovaldate',
            'approverid',

    ];



    public function up()
{
    Schema::table('daily_collections', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id');
        $table->string('username')->nullable(); 
        $table->string('siteid')->nullable(); 
        $table->string('networkid')->nullable(); 
        $table->foreign('userid')->references('id')->on('users'); 
    });
}

public function site()
{
    return $this->belongsTo(site::class, 'siteid');
}

public function network()
{
    return $this->belongsTo(site::class, 'networkid');
}

public function user()
{
    return $this->belongsTo(user::class, 'userid');
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





   

}
