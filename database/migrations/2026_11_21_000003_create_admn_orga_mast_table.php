<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_orga_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Orga_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Orga_Type_UIN')->default(1500000001);
            $table->string('Orga_Name', 30);
            $table->string('orga_Logo', 100)->default('');
            $table->unsignedBigInteger('Dt_of_Incp')->default(0);
            $table->unsignedBigInteger('Fnal_Year_Strt_Dt')->default(0);
            $table->unsignedBigInteger('Actn_Strt_Dt')->default(0);
            $table->string('Web_Addr', 20)->default('');
            $table->unsignedBigInteger('Cnta_Pers')->default(0);
            $table->unsignedBigInteger('Admt_Pers_1st')->default(0);
            $table->unsignedBigInteger('Admt_Pers_2nd')->default(0);
            $table->unsignedBigInteger('Admt_Pers_3rd')->default(0);
            $table->unsignedBigInteger('Admt_Pers_4th')->default(0);
            $table->unsignedBigInteger('Assc_Part_Code')->default(0);
            $table->unsignedBigInteger('Sytm_Admt_Pers')->default(0);
            $table->unsignedBigInteger('Eft_Dt')->default(0);
            $table->unsignedBigInteger('Renw_Dt')->default(0);
            $table->unsignedBigInteger('Expy_Dt')->default(0);
            $table->unsignedBigInteger('Orga_Del_Dt')->default(0);
            $table->unsignedBigInteger('Stau_UIN');
            $table->string('CrBy', 255)->nullable();
            $table->string('CrOn', 255)->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->string('MoOn', 255)->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->string('VfOn', 255)->nullable();
            $table->unsignedBigInteger('Stat_UIN')->default(0);
            $table->string('pin_UIN', 10)->default('');
            $table->string('Prmy_Emai', 64)->default('');
            $table->unsignedBigInteger('Prmy_Emai_Vefi')->default(0);
            $table->integer('mpos_key')->default(0);

            $table->primary('Orga_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_orga_mast');
    }
};
