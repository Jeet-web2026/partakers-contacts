<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admn_bank_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('Admn_Bank_Attachment_UIN');
            $table->unsignedBigInteger('Admn_User_Bank_Mast_UIN');
            $table->string('Atch_Path', 255)->nullable();
            $table->string('Atch_Path_2', 255)->nullable();
            $table->string('Atch_Type_1', 255)->nullable();
            $table->string('Atch_Type_2', 255)->nullable();
            $table->string('Orgn_Name', 255)->nullable();
            $table->string('CrBy', 100)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 100)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('VfBy', 100)->nullable();

            $table->primary('Admn_Bank_Attachment_UIN');

            $table->index(['Admn_User_Bank_Mast_UIN'], 'admn_bank_attachments_admn_user_bank_mast_uin_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_bank_attachments');
    }
};
