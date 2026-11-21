<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All foreign keys live in this one migration so the table migrations can run in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admn_grup_mast', function (Blueprint $table) {
            $table->foreign('Admn_Orga_Mast_UIN', 'admn_grup_mast_admn_orga_mast_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast')->cascadeOnDelete();
            $table->foreign('Parent_Grup_UIN', 'admn_grup_mast_parent_grup_uin_foreign')->references('Admn_Grup_Mast_UIN')->on('admn_grup_mast')->cascadeOnDelete();
        });

        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->foreign('Admn_Grup_Mast_UIN', 'admn_user_mast_admn_grup_mast_uin_foreign')->references('Admn_Grup_Mast_UIN')->on('admn_grup_mast')->nullOnDelete();
        });

        Schema::table('admn_user_bank_mast', function (Blueprint $table) {
            $table->foreign('Admn_Orga_Mast_UIN', 'admn_user_bank_mast_admn_orga_mast_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast')->cascadeOnDelete();
            $table->foreign('Admn_User_Mast_UIN', 'admn_user_bank_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast')->cascadeOnDelete();
            $table->foreign('Bank_Name_UIN', 'admn_user_bank_mast_bank_name_uin_foreign')->references('Bank_UIN')->on('admn_bank_name')->noActionOnDelete();
        });

        Schema::table('admn_bank_attachments', function (Blueprint $table) {
            $table->foreign('Admn_User_Bank_Mast_UIN', 'admn_bank_attachments_admn_user_bank_mast_uin_foreign')->references('Admn_User_Bank_Mast_UIN')->on('admn_user_bank_mast')->cascadeOnDelete();
        });

        Schema::table('admn_stat_mast', function (Blueprint $table) {
            $table->foreign('Admn_Cutr_Mast_UIN', 'admn_stat_mast_admn_cutr_mast_uin_foreign')->references('Admn_Cutr_Mast_UIN')->on('admn_cutr_mast')->cascadeOnDelete();
        });

        Schema::table('admn_dist_mast', function (Blueprint $table) {
            $table->foreign('Admn_Stat_Mast_UIN', 'admn_dist_mast_admn_stat_mast_uin_foreign')->references('Admn_Stat_Mast_UIN')->on('admn_stat_mast')->cascadeOnDelete();
        });

        Schema::table('admn_pincode_mast', function (Blueprint $table) {
            $table->foreign('Admn_Dist_Mast_UIN', 'admn_pincode_mast_admn_dist_mast_uin_foreign')->references('Admn_Dist_Mast_UIN')->on('admn_dist_mast')->cascadeOnDelete();
        });

        Schema::table('admn_cnta_addr_mast', function (Blueprint $table) {
            $table->foreign('Admn_Addr_Type_Mast_UIN', 'admn_cnta_addr_mast_admn_addr_type_mast_uin_foreign')->references('Admn_Addr_Type_Mast_UIN')->on('admn_addr_type_mast')->cascadeOnDelete();
            $table->foreign('Admn_Cutr_Mast_UIN', 'admn_cnta_addr_mast_admn_cutr_mast_uin_foreign')->references('Admn_Cutr_Mast_UIN')->on('admn_cutr_mast')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('Admn_Dist_Mast_UIN', 'admn_cnta_addr_mast_admn_dist_mast_uin_foreign')->references('Admn_Dist_Mast_UIN')->on('admn_dist_mast')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('Admn_PinCode_Mast_UIN', 'admn_cnta_addr_mast_admn_pincode_mast_uin_foreign')->references('Admn_PinCode_Mast_UIN')->on('admn_pincode_mast')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('Admn_Stat_Mast_UIN', 'admn_cnta_addr_mast_admn_stat_mast_uin_foreign')->references('Admn_Stat_Mast_UIN')->on('admn_stat_mast')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('Admn_User_Mast_UIN', 'admn_cnta_addr_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast')->cascadeOnDelete();
        });

        Schema::table('admn_cnta_emai_mast', function (Blueprint $table) {
            $table->foreign('Admn_User_Mast_UIN', 'admn_cnta_emai_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast');
        });

        Schema::table('admn_cnta_link_mast', function (Blueprint $table) {
            $table->foreign('Admn_Orga_Mast_UIN', 'admn_cnta_link_mast_admn_orga_mast_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast')->cascadeOnDelete();
        });

        Schema::table('admn_cnta_phon_mast', function (Blueprint $table) {
            $table->foreign('Admn_User_Mast_UIN', 'admn_cnta_phon_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast');
        });

        Schema::table('admn_tag_mast', function (Blueprint $table) {
            $table->foreign('Admn_Orga_Mast_UIN', 'admn_tag_mast_admn_orga_mast_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast');
        });

        Schema::table('admn_cnta_tag_mast', function (Blueprint $table) {
            $table->foreign('Admn_Tag_Mast_UIN', 'admn_cnta_tag_mast_admn_tag_mast_uin_foreign')->references('Admn_Tag_Mast_UIN')->on('admn_tag_mast');
            $table->foreign('Admn_User_Mast_UIN', 'admn_cnta_tag_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast');
        });

        Schema::table('admn_docu_mast', function (Blueprint $table) {
            $table->foreign('Admn_Cutr_Mast_UIN', 'admn_docu_mast_admn_cutr_mast_uin_foreign')->references('Admn_Cutr_Mast_UIN')->on('admn_cutr_mast')->nullOnDelete();
            $table->foreign('Admn_Docu_Type_Mast_UIN', 'admn_docu_mast_admn_docu_type_mast_uin_foreign')->references('Admn_Docu_Type_Mast_UIN')->on('admn_docu_type_mast')->cascadeOnDelete();
            $table->foreign('Admn_Orga_Mast_UIN', 'admn_docu_mast_admn_orga_mast_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast')->cascadeOnDelete();
            $table->foreign('Admn_User_Mast_UIN', 'admn_docu_mast_admn_user_mast_uin_foreign')->references('Admn_User_Mast_UIN')->on('admn_user_mast')->cascadeOnDelete();
        });

        Schema::table('admn_user_orga_rela', function (Blueprint $table) {
            $table->foreign('Orga_UIN', 'admn_user_orga_rela_orga_uin_foreign')->references('Orga_UIN')->on('admn_orga_mast')->cascadeOnDelete();
            $table->foreign('Orga_User_Assc_UIN', 'admn_user_orga_rela_orga_user_assc_uin_foreign')->references('Orga_User_Assc_UIN')->on('admn_user_assc_lbel_mast')->cascadeOnDelete();
            $table->foreign('User_UIN', 'admn_user_orga_rela_user_uin_foreign')->references('User_UIN')->on('admn_user_logi_mast')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admn_user_orga_rela', function (Blueprint $table) {
            $table->dropForeign('admn_user_orga_rela_orga_uin_foreign');
            $table->dropForeign('admn_user_orga_rela_orga_user_assc_uin_foreign');
            $table->dropForeign('admn_user_orga_rela_user_uin_foreign');
        });

        Schema::table('admn_docu_mast', function (Blueprint $table) {
            $table->dropForeign('admn_docu_mast_admn_cutr_mast_uin_foreign');
            $table->dropForeign('admn_docu_mast_admn_docu_type_mast_uin_foreign');
            $table->dropForeign('admn_docu_mast_admn_orga_mast_uin_foreign');
            $table->dropForeign('admn_docu_mast_admn_user_mast_uin_foreign');
        });

        Schema::table('admn_cnta_tag_mast', function (Blueprint $table) {
            $table->dropForeign('admn_cnta_tag_mast_admn_tag_mast_uin_foreign');
            $table->dropForeign('admn_cnta_tag_mast_admn_user_mast_uin_foreign');
        });

        Schema::table('admn_tag_mast', function (Blueprint $table) {
            $table->dropForeign('admn_tag_mast_admn_orga_mast_uin_foreign');
        });

        Schema::table('admn_cnta_phon_mast', function (Blueprint $table) {
            $table->dropForeign('admn_cnta_phon_mast_admn_user_mast_uin_foreign');
        });

        Schema::table('admn_cnta_link_mast', function (Blueprint $table) {
            $table->dropForeign('admn_cnta_link_mast_admn_orga_mast_uin_foreign');
        });

        Schema::table('admn_cnta_emai_mast', function (Blueprint $table) {
            $table->dropForeign('admn_cnta_emai_mast_admn_user_mast_uin_foreign');
        });

        Schema::table('admn_cnta_addr_mast', function (Blueprint $table) {
            $table->dropForeign('admn_cnta_addr_mast_admn_addr_type_mast_uin_foreign');
            $table->dropForeign('admn_cnta_addr_mast_admn_cutr_mast_uin_foreign');
            $table->dropForeign('admn_cnta_addr_mast_admn_dist_mast_uin_foreign');
            $table->dropForeign('admn_cnta_addr_mast_admn_pincode_mast_uin_foreign');
            $table->dropForeign('admn_cnta_addr_mast_admn_stat_mast_uin_foreign');
            $table->dropForeign('admn_cnta_addr_mast_admn_user_mast_uin_foreign');
        });

        Schema::table('admn_pincode_mast', function (Blueprint $table) {
            $table->dropForeign('admn_pincode_mast_admn_dist_mast_uin_foreign');
        });

        Schema::table('admn_dist_mast', function (Blueprint $table) {
            $table->dropForeign('admn_dist_mast_admn_stat_mast_uin_foreign');
        });

        Schema::table('admn_stat_mast', function (Blueprint $table) {
            $table->dropForeign('admn_stat_mast_admn_cutr_mast_uin_foreign');
        });

        Schema::table('admn_bank_attachments', function (Blueprint $table) {
            $table->dropForeign('admn_bank_attachments_admn_user_bank_mast_uin_foreign');
        });

        Schema::table('admn_user_bank_mast', function (Blueprint $table) {
            $table->dropForeign('admn_user_bank_mast_admn_orga_mast_uin_foreign');
            $table->dropForeign('admn_user_bank_mast_admn_user_mast_uin_foreign');
            $table->dropForeign('admn_user_bank_mast_bank_name_uin_foreign');
        });

        Schema::table('admn_user_mast', function (Blueprint $table) {
            $table->dropForeign('admn_user_mast_admn_grup_mast_uin_foreign');
        });

        Schema::table('admn_grup_mast', function (Blueprint $table) {
            $table->dropForeign('admn_grup_mast_admn_orga_mast_uin_foreign');
            $table->dropForeign('admn_grup_mast_parent_grup_uin_foreign');
        });
    }
};
