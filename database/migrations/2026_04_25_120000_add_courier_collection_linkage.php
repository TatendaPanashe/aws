<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('face_values') && !Schema::hasColumn('face_values', 'daily_collection_id')) {
            Schema::table('face_values', function (Blueprint $table) {
                $table->unsignedBigInteger('daily_collection_id')->nullable()->after('networkid');
            });
        }

        if (!Schema::hasTable('face_value_usages')) {
            Schema::create('face_value_usages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('daily_collection_id')->nullable();
                $table->unsignedBigInteger('batch_id');
                $table->unsignedBigInteger('clerk_id');
                $table->unsignedBigInteger('network_id')->nullable();
                $table->unsignedBigInteger('site_id')->nullable();
                $table->unsignedInteger('used')->default(0);
                $table->unsignedInteger('spoiled')->default(0);
                $table->unsignedInteger('remaining')->default(0);
                $table->date('usage_date');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('face_value_usages', function (Blueprint $table) {
                if (!Schema::hasColumn('face_value_usages', 'daily_collection_id')) {
                    $table->unsignedBigInteger('daily_collection_id')->nullable()->after('id');
                }

                if (!Schema::hasColumn('face_value_usages', 'spoiled')) {
                    $table->unsignedInteger('spoiled')->default(0)->after('used');
                }

                if (!Schema::hasColumn('face_value_usages', 'comments')) {
                    $table->text('comments')->nullable()->after('usage_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('face_value_usages')) {
            Schema::dropIfExists('face_value_usages');
        }

        if (Schema::hasTable('face_values') && Schema::hasColumn('face_values', 'daily_collection_id')) {
            Schema::table('face_values', function (Blueprint $table) {
                $table->dropColumn('daily_collection_id');
            });
        }
    }
};
