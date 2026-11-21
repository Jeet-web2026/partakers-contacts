<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mast_vert', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('vertical', 256);
            $table->string('redirect_url', 256);
            $table->integer('Stau_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mast_vert');
    }
};
