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
        Schema::table('tbl_mast_sbcr_log_book', function (Blueprint $table) {
            $table->bigInteger('Log_book_uin')->unsigned()->change();
            $table->bigInteger('Sbcr_Rvnu_Catg_Name_UIN')->unsigned()->change();
            $table->bigInteger('Indv_Orga_Uin')->unsigned()->change();
            $table->bigInteger('Vert_Uin')->unsigned()->change();
            $table->bigInteger('User_UIN')->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_mast_sbcr_log_book', function (Blueprint $table) {
            $table->integer('Log_book_uin')->change();
            $table->integer('Sbcr_Rvnu_Catg_Name_UIN')->change();
            $table->integer('Indv_Orga_Uin')->change();
            $table->integer('Vert_Uin')->change();
            $table->integer('User_UIN')->change();
        });
    }
};
