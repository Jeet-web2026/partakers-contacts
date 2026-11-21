<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_user_orga_rela', function (Blueprint $table) {
            $table->unsignedBigInteger('User_Assc_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Orga_User_Assc_UIN');
            $table->unsignedBigInteger('Orga_UIN');
            $table->unsignedBigInteger('User_UIN');
            $table->string('Expy', 255)->nullable();
            $table->unsignedBigInteger('Stau_UIN')->nullable();
            $table->string('CrOn', 255)->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->string('MoOn', 255)->nullable();
            $table->string('MoBy', 255)->nullable();

            $table->primary('User_Assc_UIN');

            $table->index(['Orga_User_Assc_UIN'], 'admn_user_orga_rela_orga_user_assc_uin_foreign');
            $table->index(['Orga_UIN'], 'admn_user_orga_rela_orga_uin_foreign');
            $table->index(['User_UIN'], 'admn_user_orga_rela_user_uin_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_orga_rela');
    }
};
