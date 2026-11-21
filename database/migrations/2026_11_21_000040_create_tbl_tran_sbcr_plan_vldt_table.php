<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_tran_sbcr_plan_vldt', function (Blueprint $table) {
            $table->integer('Sbcr_Plan_Vldt_UIN');
            $table->date('Bkng_Dt');
            $table->string('Indv_Orga_Type', 1);
            $table->bigInteger('Indv_Orga_UIN')->nullable();
            $table->integer('New_Plan_UIN');
            $table->integer('Old_Plan_UIN');
            $table->integer('Vtcl_UIN');
            $table->string('Plan_Drtn', 15);
            $table->date('Plan_Strt_Dt');
            $table->integer('Ulzn_Ntry');
            $table->integer('Ulzn_Spce');
            $table->integer('Ulzn_User');
            $table->integer('Ulzn_Cr_Pont');
            $table->integer('Sbcr_Ntry');
            $table->integer('Sbcr_Spce');
            $table->integer('Sbcr_User');
            $table->string('Sbcr_Cary_Ford', 3);
            $table->date('Sbcr_Last_Dt');
            $table->integer('Adnl_Ntry');
            $table->integer('Adnl_Spce');
            $table->integer('Adnl_User');
            $table->string('Adnl_Cary_Ford', 3);
            $table->date('Adnl_Last_Dt');
            $table->integer('Bnft_Ntry');
            $table->date('Bnft_Ntry_Last_Dt');
            $table->integer('Bnft_Spce');
            $table->date('Bnft_Spce_Last_Dt');
            $table->integer('Bnft_User');
            $table->date('Bnft_User_Last_Dt');
            $table->date('Bnft_Last_Dt');
            $table->integer('Cr_Pont_Ntry');
            $table->integer('Cr_Pont_Spce');
            $table->integer('Excs_Ntry');
            $table->integer('Excs_Spce');
            $table->integer('Stau_UIN');
            $table->bigInteger('CrBy')->nullable();
            $table->date('CrOn');
            $table->bigInteger('MoBy')->nullable();
            $table->date('MoOn');

            $table->primary('Sbcr_Plan_Vldt_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tran_sbcr_plan_vldt');
    }
};
