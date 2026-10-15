<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servo_order_logs', function (Blueprint $table) {
            $table->decimal('shipping_charges', 22, 4)->default(0)->after('client_name');
            $table->text('order_addresses')->nullable()->after('shipping_charges');
        });
    }

    public function down(): void
    {
        Schema::table('servo_order_logs', function (Blueprint $table) {
            $table->dropColumn(['shipping_charges', 'order_addresses']);
        });
    }
};
