<?php

$files = [
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\create.blade.php",
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\edit.blade.php",
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\create-by-link.blade.php"
];

$old_block = <<<EOT
<span class="flex items-center gap-2 min-w-0">
                                                <template x-if="selectedCountry">
                                                    <span class="flex items-center gap-2">
                                                        <span :class="`fi fi-\${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
                                                        <span class="truncate max-w-[120px]" x-text="selectedCountry?.Name"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!selectedCountry">
                                                    <span class="text-gray-400">Select Country...</span>
                                                </template>
                                            </span>
EOT;

$new_block = <<<EOT
<span class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden">
                                                <span x-show="selectedCountry" class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden" style="display: none;">
                                                    <span class="flex-shrink-0" :class="`fi fi-\${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
                                                    <span class="truncate" x-text="selectedCountry?.Name"></span>
                                                </span>
                                                <span x-show="!selectedCountry" class="text-gray-400 truncate">Select Country...</span>
                                            </span>
EOT;

// Normalize line endings for replacement
$old_block_normalized = str_replace("\r\n", "\n", $old_block);
$new_block_normalized = str_replace("\r\n", "\n", $new_block);

foreach ($files as $filepath) {
    if (!file_exists($filepath)) continue;
    $content = file_get_contents($filepath);
    $content_normalized = str_replace("\r\n", "\n", $content);
    
    $new_content_normalized = str_replace($old_block_normalized, $new_block_normalized, $content_normalized);
    
    // Restore CRLF for Windows
    $new_content = str_replace("\n", "\r\n", $new_content_normalized);
    
    if ($new_content !== $content) {
        file_put_contents($filepath, $new_content);
        echo "Updated " . basename($filepath) . "\n";
    }
}
