<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_note_comnt', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Cnta_Note_Comnt_UIN');
            $table->string('Category', 100);
            $table->text('Comnt_Text');
            $table->unsignedBigInteger('Stau_UIN')->default(100201);
            $table->timestamp('CrOn')->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->unsignedBigInteger('CrBy')->nullable();
            $table->bigInteger('Orga_UIN')->nullable();

            $table->primary('Admn_Cnta_Note_Comnt_UIN');

            $table->index(['Category'], 'admn_cnta_note_comnt_category_index');
            $table->index(['Stau_UIN'], 'admn_cnta_note_comnt_stau_uin_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_note_comnt');
    }
};
