<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_tag_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Tag_Mast_UIN')->default(DB::raw('(unix_timestamp() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Tag_Mast_UIN');
            $table->boolean('Is_Actv')->default(1)->comment('Tag active/inactive status');
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->primary('Admn_Cnta_Tag_Mast_UIN');

            $table->unique(['Admn_User_Mast_UIN', 'Admn_Tag_Mast_UIN'], 'admn_cnta_tag_mast_admn_user_mast_uin_admn_tag_mast_uin_unique');
            $table->index(['Admn_Tag_Mast_UIN'], 'admn_cnta_tag_mast_admn_tag_mast_uin_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_tag_mast');
    }
};
