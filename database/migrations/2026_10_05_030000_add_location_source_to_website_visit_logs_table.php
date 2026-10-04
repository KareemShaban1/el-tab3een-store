<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_visit_logs', function (Blueprint $table) {
            $table->string('location_source', 20)->nullable()->after('location_label')->index();
        });
    }

    public function down(): void
    {
        Schema::table('website_visit_logs', function (Blueprint $table) {
            $table->dropColumn('location_source');
        });
    }
};
