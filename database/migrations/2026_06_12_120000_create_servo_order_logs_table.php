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
        Schema::create('servo_order_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('transaction_id')->nullable()->index();
            $table->string('idempotency_key', 191)->nullable()->index();
            $table->string('client_name', 191);
            $table->json('items');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('servo_reference', 191)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'contact_id']);
            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servo_order_logs');
    }
};