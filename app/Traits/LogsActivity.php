<?php

namespace App\Traits;

use App\Models\Mast_Sbcr_Log_Book;
use Illuminate\Support\Facades\Session;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasMaxConstants;
use Illuminate\Support\Facades\DB;

trait LogsActivity
{
    use GeneratesUINs, HasMaxConstants;
    
    // Mapping action names to Sbcr_Rvnu_Catg_Name_UIN IDs
    protected array $mappings = [
        // Contact operations
        'New Entry'             => 154, // Add > Contact Add
        'Add with Attachment'   => 111, // Add > Contact Add with attachment
        'Edit Entry'            => 112, // Edit > Contact (no attachment)
        'Edit with Attachment'  => 113, // Edit > Contact with attachment
        'Delete Entry'          => 114, // Delete > Contact

        // Master Data
        'Master Data Entry'     => 115, // Add > Groups / Notes / Tag
        'Master Data Edit'      => 152, // Edit > Groups / Notes / Tag
        'Master Data Delete'    => 153, // Delete > Groups

        // User
        'User Onboard'          => 155, // Add > User Add
        'User Permission'       => 156, // Permission > User

        // Links / Import / Export / Report
        'Link Generate'         => 124, // Link > Link Generate
        'General Report'        => 118, // Report > Name (Hyperlink)
        'Import Data'           => 126, // Import > CSV File Upload
        'Export Data'           => 127, // Export > CSV File Download
        // Fallback
        'Entry'                 => 101,
    ];

    /**
     * Log a subscription-related activity.
     *
     * @param string $action The action name (e.g., 'Export Data', 'Import Data', 'Entry')
     * @param string|null $description Custom description for the log
     * @param int|string $cont Number of items/size (default 1)
     * @param string $uom Unit of measure (default 'Single')
     * @param float $size Size in MB or other units (default 0)
     */
    public function logActivity($action, $description = null, $cont = 1, $uom = 'Single', $size = 0)
    {
        $catgId = $this->mappings[$action] ?? 101; // Default to 'Entry' if action not in map

        $userUin = session('authenticated_user_uin') ?? session('user_uin') ?? auth()->id();
        $orgaUin = session('selected_Orga_UIN') ?? session('orga_uin') ?? session('organization_uin');
        
        if (!$orgaUin && $userUin) {
            $orgaUin = DB::table('admn_user_mast')->where('Admn_User_Mast_UIN', $userUin)->value('Admn_Orga_Mast_UIN')
                    ?? DB::table('tbl_tran_user_assc')->where('User_UIN', $userUin)->value('Orga_UIN');
        }

        // Default Vert_Uin to 1 (placeholder for 'Notice & Circular' if ID is 1)
        $vertUin = session('selected_Vert_UIN') ?? 1; 

        // Generate a unique UIN for the log book entry
        $logUin = $this->generateUniqueUIN('tbl_mast_sbcr_log_book', 'Log_book_uin');

        return Mast_Sbcr_Log_Book::create([
            'Log_book_uin' => $logUin,
            'Sbcr_Rvnu_Catg_Name_UIN' => $catgId,
            'Indv_Orga' => 'O', // 'O' for Organization as seen in screenshot
            'Indv_Orga_Uin' => $orgaUin,
            'Cont' => $cont,
            'UOM' => $uom,
            'Size' => $size,
            'Vert_Uin' => $vertUin,
            'Desc' => $description ?? "[$action] performed",
            'User_UIN' => $userUin,
            'CrOn' => now(),
        ]);
    }
}
