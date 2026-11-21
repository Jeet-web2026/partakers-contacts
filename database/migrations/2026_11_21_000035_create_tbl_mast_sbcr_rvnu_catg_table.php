<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_mast_sbcr_rvnu_catg', function (Blueprint $table) {
            $table->integer('Sbcr_Uin');
            $table->string('Sbcr_Rvnu_Catg_Name', 255);
            $table->integer('Parn_Uin');
            $table->integer('Stau_Uin');
            $table->integer('CrBy');
            $table->timestamp('CrOn')->useCurrent();

            $table->primary('Sbcr_Uin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mast_sbcr_rvnu_catg');
    }
};
