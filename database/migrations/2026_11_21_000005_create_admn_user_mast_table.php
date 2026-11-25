<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_user_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_User_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->index('hr_admin_uin');
            $table->unsignedBigInteger('Hr_application_UIN')->nullable();
            $table->unsignedBigInteger('Admn_Grup_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Prfx_UIN')->nullable();
            $table->string('FaNm', 255);
            $table->string('MiNm', 255)->nullable();
            $table->string('LaNm', 255)->nullable();
            $table->string('Gend', 20)->nullable();
            $table->string('Maritl_Stus', 30)->nullable();
            $table->string('Natly', 50)->nullable();
            $table->string('Relgn', 50)->nullable();
            $table->smallInteger('Heig_cm')->nullable();
            $table->smallInteger('Weig_kg')->nullable();
            $table->json('Lang_JSON')->nullable();
            $table->string('Blood_Grp', 10)->nullable();
            $table->string('Prfl_Pict', 255)->nullable();
            $table->date('Brth_Dt')->nullable();
            $table->date('Anvy_Dt')->nullable();
            $table->date('Deth_Dt')->nullable();
            $table->string('Web', 255)->nullable();
            $table->string('FcBk', 255)->nullable();
            $table->string('Twtr', 255)->nullable();
            $table->string('LnDn', 255)->nullable();
            $table->string('Intg', 255)->nullable();
            $table->string('Yaho', 255)->nullable();
            $table->string('Redt', 255)->nullable();
            $table->string('Ytb', 255)->nullable();
            $table->text('Note')->nullable();
            $table->unsignedBigInteger('Is_Actv')->default(100201);
            $table->unsignedBigInteger('Is_Vf')->default(100206);
            $table->string('Prty', 1)->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();
            $table->string('Comp_Name', 255)->nullable();
            $table->string('Comp_Dsig', 255)->nullable();
            $table->string('Comp_LdLi', 255)->nullable();
            $table->string('Comp_Desp', 1000)->nullable();
            $table->string('Comp_Emai', 255)->nullable();
            $table->string('Comp_Web', 255)->nullable();
            $table->text('Comp_Addr')->nullable();
            $table->string('Empl_Type', 50)->nullable()->default('job');

            $table->primary('Admn_User_Mast_UIN');

            $table->index(['Admn_Grup_Mast_UIN'], 'admn_user_mast_admn_grup_mast_uin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_mast');
    }
};
