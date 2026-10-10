<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'tags')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('tags')->nullable()->after('product_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'tags')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('tags');
            });
        }
    }
};
