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
        Schema::create('website_visit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->uuid('visit_token')->unique();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->boolean('is_bot')->default(false)->index();
            $table->string('bot_name', 120)->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->string('page_path', 1024)->nullable()->index();
            $table->string('page_title', 255)->nullable();
            $table->string('page_type', 60)->nullable()->index();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->string('product_name', 255)->nullable();
            $table->string('product_source', 40)->nullable();
            $table->string('referer', 2048)->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->json('events')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['business_id', 'is_bot', 'created_at']);
            $table->index(['business_id', 'page_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_visit_logs');
    }
};
