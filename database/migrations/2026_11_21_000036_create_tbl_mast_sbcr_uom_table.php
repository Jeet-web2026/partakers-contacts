<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mast_sbcr_uom', function (Blueprint $table) {
            $table->integer('Sbcr_Uom_Uin');
            $table->string('Uom_Name', 100);
            $table->string('Uom_Shrt_Name', 50);
            $table->integer('Stau_Uin');
            $table->integer('CrBy');
            $table->timestamp('CrOn')->useCurrent();

            $table->primary('Sbcr_Uom_Uin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mast_sbcr_uom');
    }
};
