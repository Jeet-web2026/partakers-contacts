<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_phon_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Phon_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Phon_Numb', 20);
            $table->string('Phon_Type', 50);
            $table->string('Cutr_Code', 5)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->boolean('Has_WtAp')->default(0);
            $table->boolean('Has_Telg')->default(0);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Cnta_Phon_Mast_UIN');

            $table->unique(['Admn_User_Mast_UIN', 'Phon_Numb'], 'admn_cnta_phon_mast_admn_user_mast_uin_phon_numb_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_phon_mast');
    }
};
