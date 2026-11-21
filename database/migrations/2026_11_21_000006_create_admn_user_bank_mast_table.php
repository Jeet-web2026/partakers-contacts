<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_user_bank_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_User_Bank_Mast_UIN');
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN');
            $table->unsignedBigInteger('Bank_Name_UIN');
            $table->string('Bank_Brnc_Name', 50)->nullable();
            $table->string('Acnt_Type', 50)->nullable();
            $table->string('Acnt_Numb', 50);
            $table->string('IFSC_Code', 20)->nullable();
            $table->string('Swift_Code', 20)->nullable();
            $table->tinyInteger('Prmy')->default(0);
            $table->integer('Stau')->default(100201);
            $table->string('CrBy', 100)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 100)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VrBy', 100)->nullable();
            $table->timestamp('VrOn')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary('Admn_User_Bank_Mast_UIN');

            $table->index(['Admn_Orga_Mast_UIN'], 'admn_user_bank_mast_admn_orga_mast_uin_foreign');
            $table->index(['Admn_User_Mast_UIN', 'Admn_Orga_Mast_UIN'], 'admn_user_bank_mast_admn_user_mast_uin_admn_orga_mast_uin_index');
            $table->index(['Bank_Name_UIN'], 'admn_user_bank_mast_bank_name_uin_index');
            $table->index(['Prmy'], 'admn_user_bank_mast_prmy_index');
            $table->index(['Stau'], 'admn_user_bank_mast_stau_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_bank_mast');
    }
};
