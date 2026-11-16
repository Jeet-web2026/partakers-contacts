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
        if (!Schema::hasColumn('tbl_tran_user_assc', 'user_type')) {
            Schema::table('tbl_tran_user_assc', function (Blueprint $table) {
                $table->string('user_type', 100)->nullable()->after('valid_upto');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_tran_user_assc', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
};
