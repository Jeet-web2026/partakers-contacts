<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Livewire\Traits\GeneratesUINs;

use App\Livewire\Traits\HasMaxConstants;

trait ManagesSettings
{
    use GeneratesUINs, HasMaxConstants;

    public function getFeatureStatus($featureName)
    {
        $orgaUin = session('selected_Orga_UIN');
        $setting = DB::table('tbl_mast_sett')
            ->where('Orga_Uin', $orgaUin)
            ->where('Ftr_Name', $featureName)
            ->first();

        // Default to enabled (1) if no setting exists
        return $setting ? (bool)$setting->Status : true;
    }

    public function updateFeatureStatus($featureName, $status)
    {
        $orgaUin = session('selected_Orga_UIN');
        
        $exists = DB::table('tbl_mast_sett')
            ->where('Orga_Uin', $orgaUin)
            ->where('Ftr_Name', $featureName)
            ->exists();

        if ($exists) {
            DB::table('tbl_mast_sett')
                ->where('Orga_Uin', $orgaUin)
                ->where('Ftr_Name', $featureName)
                ->update([
                    'Status' => $status ? 1 : 0,
                    'CrOn' => now()
                ]);
        } else {
            DB::table('tbl_mast_sett')->insert([
                'Sett_Uin' => $this->generateUniqueUIN('tbl_mast_sett', 'Sett_Uin'),
                'Ftr_Name' => $featureName,
                'Status' => $status ? 1 : 0,
                'Orga_Uin' => $orgaUin,
                'CrOn' => now()
            ]);
        }
    }

    public static function isFeatureEnabled($featureName)
    {
        $orgaUin = session('selected_Orga_UIN');
        $setting = DB::table('tbl_mast_sett')
            ->where('Orga_Uin', $orgaUin)
            ->where('Ftr_Name', $featureName)
            ->first();

        return $setting ? (bool)$setting->Status : true;
    }

    public static function hasPermission($featureName)
    {
        $orgaUin = session('selected_Orga_UIN');
        $userUin = session('authenticated_user_uin');

        // First check organization feature status
        if (!self::isFeatureEnabled($featureName)) {
            return false;
        }

        // Map feature names to user association table columns
        $permissionMap = [
            'Add'        => 'can_add',
            'Edit'       => 'can_edit',
            'Delete'     => 'can_del',
            'Attachment' => 'can_atch',
            'Invite'     => 'can_link',
            'Reports'    => 'can_rept',
            'Import'      => 'can_impt',
            'Export'     => 'can_expt',
            'Groups'     => 'mast_grp',
            'Tag'        => 'mast_tag',
            'Notes'      => 'mast_notes',
        ];

        // If the feature doesn't require specific user permission (e.g. Invite), just rely on org setting 
        // Or if it's explicitly not in our map
        if (!array_key_exists($featureName, $permissionMap)) {
            return true;
        }

        $column = $permissionMap[$featureName];

        $assc = DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('User_UIN', $userUin)
            ->where('Stau_UIN', 100201)
            ->orderBy('User_Assc_UIN', 'desc')
            ->first();

        // If user is not in the association table, check if they are the organization owner
        if (!$assc) {
            $org = DB::table('admn_orga_mast')->where('Orga_UIN', $orgaUin)->first();
            return $org && $org->CrBy == $userUin;
        }

        // NOTE: Subscription expiry is handled centrally by ValidatesSubscriptionLimits.
        // We do not check valid_upto here to avoid blocking users if the field becomes stale.

        return (bool)$assc->{$column};
    }

    public static function isAdmin()
    {
        $orgaUin = session('selected_Orga_UIN');
        $userUin = session('authenticated_user_uin');

        $assc = DB::table('tbl_tran_user_assc')
            ->where('Orga_UIN', $orgaUin)
            ->where('User_UIN', $userUin)
            ->where('Stau_UIN', 100201)
            ->orderBy('User_Assc_UIN', 'desc')
            ->first();

        // If user has association record, check is_admin column
        if ($assc) {
            return (bool) $assc->is_admin;
        }

        // If no association or is_admin not set, check if they are the organization creator
        $org = DB::table('admn_orga_mast')->where('Orga_UIN', $orgaUin)->first();
        return $org && $org->CrBy == $userUin;
    }
}
