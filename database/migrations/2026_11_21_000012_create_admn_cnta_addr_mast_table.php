<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_addr_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Addr_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Addr_Type_Mast_UIN')->nullable();
            $table->text('Addr')->nullable();
            $table->string('Loca', 255)->nullable();
            $table->string('Lndm', 255)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Admn_Stat_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Admn_Dist_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Admn_PinCode_Mast_UIN')->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Cnta_Addr_Mast_UIN');

            $table->index(['Admn_User_Mast_UIN', 'Is_Prmy'], 'admn_cnta_addr_mast_admn_user_mast_uin_is_prmy_index');
            $table->index(['Admn_Cutr_Mast_UIN'], 'admn_cnta_addr_mast_admn_cutr_mast_uin_index');
            $table->index(['Admn_Addr_Type_Mast_UIN'], 'admn_cnta_addr_mast_admn_addr_type_mast_uin_index');
            $table->index(['Admn_Stat_Mast_UIN'], 'admn_cnta_addr_mast_admn_stat_mast_uin_index');
            $table->index(['Admn_Dist_Mast_UIN'], 'admn_cnta_addr_mast_admn_dist_mast_uin_index');
            $table->index(['Admn_PinCode_Mast_UIN'], 'admn_cnta_addr_mast_admn_pincode_mast_uin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_addr_mast');
    }
};
