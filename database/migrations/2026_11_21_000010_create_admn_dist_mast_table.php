<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_dist_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Dist_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Admn_Stat_Mast_UIN');
            $table->string('Name', 255);
            $table->string('Code', 10)->nullable();
            $table->bigInteger('Stau_UIN')->default(100201);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Dist_Mast_UIN');

            $table->index(['Admn_Stat_Mast_UIN'], 'admn_dist_mast_admn_stat_mast_uin_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_dist_mast');
    }
};
