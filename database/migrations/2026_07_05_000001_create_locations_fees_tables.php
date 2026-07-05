<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lf_governorates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        Schema::create('lf_cities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('governorate_id');
            $table->string('name');
            $table->decimal('delivery_cost', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('governorate_id')->references('id')->on('lf_governorates')->onDelete('cascade');
            $table->index(['business_id', 'governorate_id', 'is_active']);
        });

        Schema::create('lf_areas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('city_id');
            $table->string('name');
            $table->decimal('delivery_cost', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('lf_cities')->onDelete('cascade');
            $table->index(['business_id', 'city_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lf_areas');
        Schema::dropIfExists('lf_cities');
        Schema::dropIfExists('lf_governorates');
    }
};
