<?php
// Creates storage symlink for Filament

$storageAppPublic = realpath(__DIR__ . '/../storage/app/public');
$publicStorage = __DIR__ . '/storage';

if (!file_exists($publicStorage)) {
    if (symlink($storageAppPublic, $publicStorage)) {
        echo "Storage link created successfully!";
    } else {
        echo "Failed to create storage link.";
    }
} else {
    echo "Storage link already exists.";
}

// Delete this file after running
unlink(__FILE__);
?>