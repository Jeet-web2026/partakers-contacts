<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_bank_name', function (Blueprint $table) {
            $table->unsignedBigInteger('Bank_UIN');
            $table->string('Bank_Name', 256);
            $table->string('Bank_Type', 256);
            $table->integer('Stau_UIN');
            $table->bigInteger('CrOn');
            $table->bigInteger('CrBy');

            $table->primary('Bank_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_bank_name');
    }
};
