<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('admn_user_skil_mast');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('admn_user_skil_mast', function (Blueprint $table) {
            $table->string('Admn_User_Skil_Mast_UIN', 20)->primary();
            $table->string('Admn_User_Mast_UIN', 20);
            $table->string('Skil_Name', 100);
            $table->integer('Profc_Lvl')->nullable();
            $table->string('Skil_Type', 50)->nullable();
            $table->string('Skil_Type_1', 100)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('CrBy', 20)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('MoBy', 20)->nullable();
        });
    }
};
