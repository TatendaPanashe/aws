<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('face_values')) {
            Schema::table('face_values', function (Blueprint $table) {
                if (!Schema::hasColumn('face_values', 'insurance_provider')) {
                    $table->string('insurance_provider')->nullable()->after('comments');
                }

                if (!Schema::hasColumn('face_values', 'document_channel')) {
                    $table->string('document_channel')->nullable()->after('insurance_provider');
                }
            });
        }

        if (!Schema::hasTable('courier_sales')) {
            Schema::create('courier_sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('face_value_id');
                $table->unsignedBigInteger('batch_id');
                $table->unsignedBigInteger('clerk_id');
                $table->unsignedBigInteger('supervisor_id')->nullable();
                $table->unsignedBigInteger('network_id')->nullable();
                $table->unsignedBigInteger('site_id')->nullable();
                $table->string('insurance_provider');
                $table->string('currency')->default('USD');
                $table->decimal('sales_amount', 12, 2);
                $table->date('sale_date');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courier_sales')) {
            Schema::dropIfExists('courier_sales');
        }

        if (Schema::hasTable('face_values')) {
            Schema::table('face_values', function (Blueprint $table) {
                $columns = [];

                if (Schema::hasColumn('face_values', 'insurance_provider')) {
                    $columns[] = 'insurance_provider';
                }

                if (Schema::hasColumn('face_values', 'document_channel')) {
                    $columns[] = 'document_channel';
                }

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
