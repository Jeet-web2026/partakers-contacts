<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_land_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Land_Mast_UIN');
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->nullable();
            $table->string('Land_Numb', 20)->nullable();
            $table->string('Land_Type', 20)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->string('Cutr_Code', 5)->nullable();
            $table->unsignedBigInteger('Stau')->default(100201);
            $table->unsignedBigInteger('CrBy')->nullable();
            $table->dateTime('CrOn')->nullable();
            $table->unsignedBigInteger('MoBy')->nullable();
            $table->dateTime('MoOn')->nullable();

            $table->primary('Admn_Cnta_Land_Mast_UIN');

            $table->index(['Admn_User_Mast_UIN'], 'admn_cnta_land_mast_admn_user_mast_uin_index');
            $table->index(['Admn_Orga_Mast_UIN'], 'admn_cnta_land_mast_admn_orga_mast_uin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_land_mast');
    }
};
