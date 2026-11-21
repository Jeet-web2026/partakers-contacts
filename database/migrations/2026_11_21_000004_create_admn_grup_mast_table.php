<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_grup_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Grup_Mast_UIN');
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->nullable();
            $table->unsignedBigInteger('Parent_Grup_UIN')->nullable();
            $table->string('Name', 255);
            $table->integer('Is_Actv')->default(100201);
            $table->bigInteger('CrBy')->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->bigInteger('MoBy')->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary('Admn_Grup_Mast_UIN');

            $table->index(['Admn_Orga_Mast_UIN', 'Is_Actv'], 'admn_grup_mast_admn_orga_mast_uin_is_actv_index');
            $table->index(['Parent_Grup_UIN'], 'admn_grup_mast_parent_grup_uin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_grup_mast');
    }
};
