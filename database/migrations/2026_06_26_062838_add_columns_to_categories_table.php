<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            //
		// active_in_app
		// featured
		// order
		$table->boolean('active_in_app')->default(true)->after('description');
		$table->boolean('featured')->default(false)->after('active_in_app');
		$table->integer('order')->default(0)->after('featured');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
			$table->dropColumn(['active_in_app', 'featured', 'order']);
            //
        });
    }
};