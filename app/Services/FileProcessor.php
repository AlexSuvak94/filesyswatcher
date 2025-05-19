<?php

namespace App\Services;

use App\Services\Processors\TextFileProcessor;
use App\Services\Processors\JpgOptimizer;
use App\Services\Processors\ZipExtractor;
use App\Services\Processors\JsonPoster;

class FileProcessor
{
    public function processFile(string $file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'txt':
                return (new TextFileProcessor())->handle($file);

            case 'jpg':
            case 'jpeg':
                return (new JpgOptimizer())->handle($file);

            case 'zip':
                return (new ZipExtractor())->handle($file);

            case 'json':
                return (new JsonPoster())->handle($file);
        }
    }

    public function handleDeletedFile(string $file, string $watchPath)
    {
        return (new DeletedFileHandler())->handle($file, $watchPath);
    }
}