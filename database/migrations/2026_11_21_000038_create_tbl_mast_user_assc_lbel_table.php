<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mast_user_assc_lbel', function (Blueprint $table) {
            $table->bigInteger('Orga_User_Assc_UIN');
            $table->bigInteger('Orga_UIN');
            $table->string('User_Assc_Lbel_Name', 64)->nullable();
            $table->string('User_Assc_Lbel_Desp', 256)->nullable();
            $table->bigInteger('CrBy');
            $table->bigInteger('CrOn');
            $table->bigInteger('MoBy');
            $table->bigInteger('MoOn');
            $table->bigInteger('VfBy');
            $table->bigInteger('VfOn');

            $table->primary('Orga_User_Assc_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mast_user_assc_lbel');
    }
};
