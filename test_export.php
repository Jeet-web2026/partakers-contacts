<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Simulate user session
    session()->put('authenticated_user_uin', 1500000001);
    session()->put('selected_Orga_UIN', 1500000001);
    session()->put('selected_Vert_UIN', 1);

    // Run export
    $component = app(\App\Livewire\Contacts\Index::class);
    $response = $component->exportCsv();

    echo "Export function finished.\n";
    
    // Check logs
    $log = \Illuminate\Support\Facades\DB::table('tbl_mast_sbcr_log_book')->orderBy('CrOn', 'desc')->first();
    if ($log) {
        echo "Latest log found! Category UIN: {$log->Sbcr_Rvnu_Catg_Name_UIN}\n";
    } else {
        echo "No log found in db.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
