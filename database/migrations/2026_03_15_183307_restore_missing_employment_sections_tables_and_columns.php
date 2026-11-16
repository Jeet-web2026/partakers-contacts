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
        // 1. Re-add removed tables
        if (!Schema::hasTable('admn_user_skil_mast')) {
            Schema::create('admn_user_skil_mast', function (Blueprint $table) {
                $table->unsignedBigInteger('Admn_User_Skil_Mast_UIN')->primary();
                $table->unsignedBigInteger('Admn_User_Mast_UIN');
                $table->string('Skil_Type', 255)->nullable();
                $table->string('Skil_Type_1', 255)->nullable();
                $table->string('Skil_Name', 255);
                $table->integer('Profc_Lvl')->nullable();
                $table->string('CrBy', 255)->nullable();
                $table->timestamp('CrOn')->nullable();
                $table->timestamp('MoOn')->nullable();
            });
        }

        if (!Schema::hasTable('admn_user_educ_mast')) {
            Schema::create('admn_user_educ_mast', function (Blueprint $table) {
                $table->unsignedBigInteger('Admn_User_Educ_Mast_UIN')->primary();
                $table->unsignedBigInteger('Admn_User_Mast_UIN');
                $table->string('Deg_Name', 255);
                $table->string('Inst_Name', 255);
                $table->integer('Cmpt_Year')->nullable();
                $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();
                $table->string('CrBy', 255)->nullable();
                $table->timestamp('CrOn')->nullable();
                $table->timestamp('MoOn')->nullable();
            });
        }

        // 2. Re-add columns to admn_user_mast
        Schema::table('admn_user_mast', function (Blueprint $table) {
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Name')) {
                $table->string('Comp_Name', 255)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Dsig')) {
                $table->string('Comp_Dsig', 255)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_LdLi')) {
                $table->string('Comp_LdLi', 255)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Desp')) {
                $table->string('Comp_Desp', 1000)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Emai')) {
                $table->string('Comp_Emai', 255)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Web')) {
                $table->string('Comp_Web', 255)->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Comp_Addr')) {
                $table->text('Comp_Addr')->nullable();
            }
            if (!Schema::hasColumn('admn_user_mast', 'Empl_Type')) {
                $table->string('Empl_Type', 50)->nullable()->default('job');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->dropColumn([
                'Comp_Name',
                'Comp_Dsig',
                'Comp_LdLi',
                'Comp_Desp',
                'Comp_Emai',
                'Comp_Web',
                'Comp_Addr',
                'Empl_Type',
            ]);
        });

        Schema::dropIfExists('admn_user_educ_mast');
        Schema::dropIfExists('admn_user_skil_mast');
    }
};
