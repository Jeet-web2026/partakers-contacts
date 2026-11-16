<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admn_bank_attachments', function (Blueprint $table) {
            // Check if column already exists before adding
            if (!Schema::hasColumn('admn_bank_attachments', 'Atch_Path_2')) {
                $table->string('Atch_Path_2')->nullable()->after('Atch_Path');
            }
            if (!Schema::hasColumn('admn_bank_attachments', 'Atch_Type_1')) {
                $table->string('Atch_Type_1')->nullable()->after('Atch_Path_2');
            }
            if (!Schema::hasColumn('admn_bank_attachments', 'Atch_Type_2')) {
                $table->string('Atch_Type_2')->nullable()->after('Atch_Type_1');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admn_bank_attachments', function (Blueprint $table) {
            $table->dropColumn(['Atch_Path_2', 'Atch_Type_1', 'Atch_Type_2']);
        });
    }
};
