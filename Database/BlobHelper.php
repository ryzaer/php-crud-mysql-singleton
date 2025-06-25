<?php 
// File: Database/BlobHelper.php

namespace Database;

class BlobHelper
{
    public static function isBlobFile(string $filePath, string $format): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, explode('|', $format));
    }

    public static function readFile(string $filePath): string
    {
        return file_exists($filePath) ? file_get_contents($filePath) : '';
    }
}