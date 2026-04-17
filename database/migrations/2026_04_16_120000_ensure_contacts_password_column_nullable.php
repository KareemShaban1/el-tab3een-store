<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure `contacts.password` exists and is optional (nullable).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('contacts', 'password')) {
            Schema::table('contacts', function (Blueprint $table) {
                if (Schema::hasColumn('contacts', 'email')) {
                    $table->string('password')->nullable()->after('email');
                } else {
                    $table->string('password')->nullable();
                }
            });

            return;
        }

        $this->makePasswordColumnNullable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally empty: do not drop password if it was added by an earlier migration.
    }

    private function makePasswordColumnNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE contacts MODIFY password VARCHAR(255) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE contacts ALTER COLUMN password DROP NOT NULL');

            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE contacts ALTER COLUMN password VARCHAR(255) NULL');

            return;
        }

        // SQLite: older versions cannot drop NOT NULL easily; Laravel typically uses nullable() on create.
    }
};
