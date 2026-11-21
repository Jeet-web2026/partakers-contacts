<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mast_sett', function (Blueprint $table) {
            $table->bigInteger('Sett_Uin')->autoIncrement();
            $table->string('Ftr_Name', 255);
            $table->boolean('Status')->default(1);
            $table->integer('Orga_Uin');
            $table->timestamp('CrOn')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mast_sett');
    }
};
