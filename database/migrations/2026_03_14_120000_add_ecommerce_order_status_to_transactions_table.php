<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'ecommerce_order_status')) {
                $table->string('ecommerce_order_status', 60)->nullable()->after('sub_status');
                $table->index('ecommerce_order_status', 'transactions_ecommerce_order_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'ecommerce_order_status')) {
                $table->dropIndex('transactions_ecommerce_order_status_idx');
                $table->dropColumn('ecommerce_order_status');
            }
        });
    }
};

