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
        Schema::table('admn_user_bank_mast', function (Blueprint $table) {
            $table->string('Atch_Path')->nullable();
            $table->string('Atch_Path_2')->nullable();
            $table->string('Atch_Type_1')->nullable();
            $table->string('Atch_Type_2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admn_user_bank_mast', function (Blueprint $table) {
            //
        });
    }
};
