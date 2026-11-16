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
        // 1. Remove columns from admn_user_mast
        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->dropColumn([
                'Comp_Name',
                'Comp_Dsig',
                'Comp_LdLi',
                'Comp_Desp',
                'Comp_Emai',
                'Comp_Web',
                'Comp_Addr',
                'Prfl_Name',
                'Prfl_Addr',
            ]);
        });

        // 2. Drop unused tables (if they exist)
        Schema::dropIfExists('admn_user_educ_mast');
        Schema::dropIfExists('admn_user_skil_mast');
        Schema::dropIfExists('admn_user_work_mast');
        // Schema::dropIfExists('admn_cnta_refa_mast');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add removed tables (basic schema matching original creation)
        Schema::create('admn_cnta_refa_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Refa_Mast_UIN')->primary();
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Refa_Name', 255)->nullable();
            $table->string('Refa_Phon', 30)->nullable();
            $table->string('Refa_Emai', 155)->nullable();
            $table->string('Refa_Rsip', 155)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->timestamp('CrOn')->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('CrBy', 255)->nullable();
        });

        Schema::create('admn_user_work_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_User_Work_Mast_UIN')->primary();
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Orga_Name', 255);
            $table->string('Dsgn', 255)->nullable();
            $table->date('Prd_From')->nullable();
            $table->date('Prd_To')->nullable();
            $table->string('Orga_Type', 255)->nullable();
            $table->text('Job_Desp')->nullable();
            $table->string('Work_Type', 100)->default('Full');
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->timestamp('MoOn')->nullable();
        });

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

        Schema::create('admn_user_educ_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_User_Educ_Mast_UIN')->primary();
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Deg_Name', 255);
            $table->string('Inst_Name', 255);
            $table->integer('Cmpt_Year');
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->timestamp('MoOn')->nullable();
        });

        // Re-add columns to admn_user_mast
        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->string('Comp_Name', 255)->nullable();
            $table->string('Comp_Dsig', 255)->nullable();
            $table->string('Comp_LdLi', 255)->nullable();
            $table->string('Comp_Desp', 1000)->nullable();
            $table->string('Comp_Emai', 255)->nullable();
            $table->string('Comp_Web', 255)->nullable();
            $table->text('Comp_Addr')->nullable();

            $table->string('Prfl_Name', 255)->nullable();
            $table->text('Prfl_Addr')->nullable();
        });
    }
};
