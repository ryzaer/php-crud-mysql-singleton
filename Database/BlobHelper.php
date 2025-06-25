<?php 
// File: Database/BlobHelper.php

namespace Database;

class BlobHelper
{
    public static function isBlobFile(string $filePath, string $format): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $allowed = explode('|', $format);
        return in_array($extension, $allowed) && is_file($filePath);
    }

    public static function readFile(string $filePath): ?string
    {
        return is_file($filePath) ? file_get_contents($filePath) : null;
    }
}