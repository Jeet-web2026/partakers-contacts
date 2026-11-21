<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cutr_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->string('Name', 255);
            $table->string('Code', 10)->nullable();
            $table->string('Phon_Code', 10)->nullable();
            $table->string('Flag_Emoj', 10)->nullable();
            $table->smallInteger('MoNo_Digt');
            $table->bigInteger('Stau_UIN')->default(100201);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Cutr_Mast_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cutr_mast');
    }
};
