<?php

namespace App\Services\Processors;

use ZipArchive;

class ZipExtractor
{
    public function handle(string $file): string
    {
        $path = storage_path("app/" . $file);
        $extractTo = storage_path("app/watched/unzipped/" . pathinfo($file, PATHINFO_FILENAME));

        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $zip->extractTo($extractTo);
            $zip->close();
            return "Extracted Zip file to: $extractTo";
        }

        return "ERROR: Failed to extract Zip file: $file";
    }
}