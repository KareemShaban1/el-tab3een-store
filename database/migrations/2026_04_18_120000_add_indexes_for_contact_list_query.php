<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds up contact index DataTable filters: WHERE business_id AND type.
 * (transactions.contact_id is typically already indexed via FK to contacts.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['business_id', 'type'], 'contacts_business_id_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_business_id_type_index');
        });
    }
};
