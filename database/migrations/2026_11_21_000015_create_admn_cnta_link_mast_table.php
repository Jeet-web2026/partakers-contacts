<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_link_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Link_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->string('Tokn', 255);
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->nullable();
            $table->string('Cnta_Tag', 255)->default('ByLink');
            $table->tinyInteger('Is_Used')->default(0);
            $table->timestamp('Expy_Dt')->nullable();
            $table->integer('Is_Actv')->default(100201);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Cnta_Link_Mast_UIN');

            $table->unique(['Tokn'], 'admn_cnta_link_mast_tokn_unique');
            $table->index(['Admn_Orga_Mast_UIN'], 'admn_cnta_link_mast_admn_orga_mast_uin_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_link_mast');
    }
};
