<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_docu_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Docu_Mast_UIN');
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN');
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Admn_Docu_Type_Mast_UIN');
            $table->string('Auth_Issd', 100)->nullable();
            $table->string('Regn_Numb', 100);
            $table->string('Docu_Name', 100)->nullable();
            $table->date('Vald_From')->nullable();
            $table->date('Vald_Upto')->nullable();
            $table->string('Docu_Atch_Path', 500)->nullable();
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

            $table->primary('Admn_Docu_Mast_UIN');

            $table->index(['Admn_Orga_Mast_UIN'], 'admn_docu_mast_admn_orga_mast_uin_foreign');
            $table->index(['Admn_Cutr_Mast_UIN'], 'admn_docu_mast_admn_cutr_mast_uin_foreign');
            $table->index(['Admn_User_Mast_UIN', 'Admn_Orga_Mast_UIN'], 'admn_docu_mast_admn_user_mast_uin_admn_orga_mast_uin_index');
            $table->index(['Admn_Docu_Type_Mast_UIN'], 'admn_docu_mast_admn_docu_type_mast_uin_index');
            $table->index(['Regn_Numb'], 'admn_docu_mast_regn_numb_index');
            $table->index(['Stau'], 'admn_docu_mast_stau_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_docu_mast');
    }
};
