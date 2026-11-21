<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_refa_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Refa_Mast_UIN');
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Refa_Name', 255)->nullable();
            $table->string('Refa_Phon', 30)->nullable();
            $table->string('Refa_Emai', 155)->nullable();
            $table->string('Refa_Rsip', 155)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->timestamp('CrOn')->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('CrBy', 255)->nullable();

            $table->primary('Admn_Cnta_Refa_Mast_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_refa_mast');
    }
};
