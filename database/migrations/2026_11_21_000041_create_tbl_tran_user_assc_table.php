<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_tran_user_assc', function (Blueprint $table) {
            $table->bigInteger('User_Assc_UIN')->autoIncrement();
            $table->bigInteger('Orga_User_Assc_UIN');
            $table->bigInteger('Orga_UIN');
            $table->bigInteger('User_UIN');
            $table->bigInteger('Expy');
            $table->integer('Stau_UIN');
            $table->integer('CrOn');
            $table->bigInteger('CrBy');
            $table->integer('MoOn');
            $table->bigInteger('MoBy');
            $table->boolean('can_add')->default(0);
            $table->boolean('can_edit')->default(0);
            $table->boolean('can_atch')->default(0);
            $table->boolean('can_del')->default(0);
            $table->boolean('can_link')->default(0);
            $table->boolean('can_rept')->default(0);
            $table->boolean('can_impt')->default(0);
            $table->boolean('can_expt')->default(0);
            $table->boolean('mast_grp')->default(0);
            $table->boolean('mast_tag')->default(0);
            $table->boolean('mast_notes')->default(0);
            $table->date('valid_upto')->nullable();
            $table->string('user_type', 100)->nullable();
            $table->string('vert_uin', 100)->nullable();
            $table->boolean('is_admin')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tran_user_assc');
    }
};
