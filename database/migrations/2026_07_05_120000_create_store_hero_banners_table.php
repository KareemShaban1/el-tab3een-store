<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_hero_banners', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('badge', 191)->nullable();
            $table->text('title');
            $table->text('content')->nullable();
            $table->string('link_title', 191)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('image', 191)->nullable();
            $table->string('image_alt', 191)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_hero_banners');
    }
};
