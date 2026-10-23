<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'contact_id']);
            $table->index(['business_id', 'product_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
