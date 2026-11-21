<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_addr_type_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Addr_Type_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->string('Name', 30);
            $table->char('Addr_Type', 2);
            $table->string('Cr_By', 30)->nullable();
            $table->timestamp('Cr_On')->nullable();
            $table->string('Mo_By', 30)->nullable();
            $table->timestamp('Mo_On')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();
            $table->string('Vf_By', 30)->nullable();
            $table->timestamp('Vf_On')->nullable();

            $table->primary('Admn_Addr_Type_Mast_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_addr_type_mast');
    }
};
