<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_docu_type_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Docu_Type_Mast_UIN');
            $table->string('Docu_Name', 50);
            $table->integer('Stau')->default(100201);
            $table->string('CrBy', 100)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 100)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VrBy', 100)->nullable();
            $table->timestamp('VrOn')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->primary('Admn_Docu_Type_Mast_UIN');

            $table->index(['Docu_Name'], 'admn_docu_type_mast_docu_name_index');
            $table->index(['Stau'], 'admn_docu_type_mast_stau_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_docu_type_mast');
    }
};
