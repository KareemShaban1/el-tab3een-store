<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_visit_logs', function (Blueprint $table) {
            $table->string('country', 120)->nullable()->after('ip_address');
            $table->string('country_code', 8)->nullable()->after('country')->index();
            $table->string('region', 120)->nullable()->after('country_code');
            $table->string('city', 120)->nullable()->after('region')->index();
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_label', 255)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('website_visit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'country_code',
                'region',
                'city',
                'latitude',
                'longitude',
                'location_label',
            ]);
        });
    }
};
