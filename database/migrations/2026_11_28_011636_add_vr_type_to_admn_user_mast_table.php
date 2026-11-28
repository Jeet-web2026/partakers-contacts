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
        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->string('Vr_Type')->nullable()->after('Hr_application_UIN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->string('Vr_Type')->nullable()->after('Hr_application_UIN');
        });
    }
};
