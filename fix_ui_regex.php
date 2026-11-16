<?php

$files = [
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\create.blade.php",
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\edit.blade.php",
    "C:\\Users\\DIVYANSHU\\laravel\\contacts\\resources\\views\\livewire\\contacts\\create-by-link.blade.php"
];

$pattern = '/<span class="flex items-center gap-2 min-w-0">\s*<template x-if="selectedCountry">\s*<span class="flex items-center gap-2">\s*<span :class="`fi fi-\\\$\{\(selectedCountry\?\.Code \|\| \'\'\)\.trim\(\)\.toLowerCase\(\)\}`"><\/span>\s*<span class="truncate max-w-\[120px\]" x-text="selectedCountry\?\.Name"><\/span>\s*<\/span>\s*<\/template>\s*<template x-if="!selectedCountry">\s*<span class="text-gray-400">Select Country...<\/span>\s*<\/template>\s*<\/span>/';

$new_block = <<<EOT
<span class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden">
                                                <span x-show="selectedCountry" class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden" style="display: none;">
                                                    <span class="flex-shrink-0" :class="`fi fi-\${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
                                                    <span class="truncate" x-text="selectedCountry?.Name"></span>
                                                </span>
                                                <span x-show="!selectedCountry" class="text-gray-400 truncate">Select Country...</span>
                                            </span>
EOT;

foreach ($files as $filepath) {
    if (!file_exists($filepath)) continue;
    $content = file_get_contents($filepath);
    $new_content = preg_replace($pattern, $new_block, $content);
    if ($new_content !== $content) {
        file_put_contents($filepath, $new_content);
        echo "Updated " . basename($filepath) . "\n";
    }
}
