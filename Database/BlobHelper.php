<?php 
// File: Database/BlobHelper.php

namespace Database;

class BlobHelper {
    public static function readFile(string $path): ?string {
        if (is_readable($path)) {
            return file_get_contents($path);
        }
        return null;
    }

    public static function isBlobFile(string $filename, string $formatList): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = explode('|', $formatList);
        return in_array($ext, $allowed);
    }
}