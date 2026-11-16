<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearContactsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all contacts and related data from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to clear contacts and related data...');

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToTruncate = [
            'admn_cnta_addr_mast',
            'admn_cnta_emai_mast',
            'admn_cnta_land_mast',
            'admn_cnta_phon_mast',
            'admn_cnta_refa_mast',
            'admn_cnta_tag_mast',
            'admn_user_bank_mast',
            'admn_bank_attachments',
            'admn_docu_mast',
            'admn_cnta_note_mast',
            'admn_user_mast', // Main contact table
        ];

        try {
            foreach ($tablesToTruncate as $table) {
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    \Illuminate\Support\Facades\DB::table($table)->truncate();
                    $this->info("Truncated {$table}");
                }
            }
            $this->info('All contact data cleared successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred while clearing data: ' . $e->getMessage());
        } finally {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
