<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$table = 'tbl_mast_sett';
if (Schema::hasTable($table)) {
    echo "Columns of $table:\n";
    $columns = DB::select("SHOW COLUMNS FROM $table");
    foreach ($columns as $col) {
        echo "{$col->Field} ({$col->Type})\n";
    }

    echo "\nSample Data:\n";
    $data = DB::table($table)->take(5)->get();
    print_r($data->toArray());
} else {
    echo "Table $table does not exist.\n";
    
    // Search for similar table names
    $tables = DB::select('SHOW TABLES');
    echo "\nAvailable Tables:\n";
    foreach($tables as $t) {
        $name = array_values((array)$t)[0];
        if (str_contains($name, 'sett')) {
            echo "- $name\n";
        }
    }
}
