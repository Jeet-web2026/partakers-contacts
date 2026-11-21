<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_note_mast', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Note_Mast_UIN');
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->bigInteger('Admn_Orga_Mast_UIN');
            $table->unsignedTinyInteger('Vertical_ID')->default(5);
            $table->text('Note_Detl');
            $table->timestamp('CrOn')->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('DelBy', 30)->nullable();
            $table->timestamp('DelOn')->nullable();
            $table->boolean('IsPinned')->default(0);
            $table->timestamp('PinnedOn')->nullable();
            $table->string('PinnedBy', 255)->nullable();

            $table->primary('Admn_Cnta_Note_Mast_UIN');

            $table->index(['Admn_User_Mast_UIN'], 'admn_cnta_note_mast_admn_user_mast_uin_index');
            $table->index(['DelOn'], 'admn_cnta_note_mast_deleted_at_index');
            $table->index(['Vertical_ID'], 'admn_cnta_note_mast_vertical_id_index');
            $table->index(['IsPinned'], 'admn_cnta_note_mast_ispinned_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_note_mast');
    }
};
