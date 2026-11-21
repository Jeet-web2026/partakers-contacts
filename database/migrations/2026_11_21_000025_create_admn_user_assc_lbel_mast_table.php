<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_user_assc_lbel_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Orga_User_Assc_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Orga_UIN');
            $table->string('User_Assc_Lbel_Name', 64)->nullable();
            $table->string('User_Assc_Lbel_Desp', 256)->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->string('CrOn', 255)->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->string('MoOn', 255)->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->string('VfOn', 255)->nullable();

            $table->primary('Orga_User_Assc_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_assc_lbel_mast');
    }
};
