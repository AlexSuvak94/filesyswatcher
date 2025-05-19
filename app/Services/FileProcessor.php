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

    public function handleDeletedFile(string $file)
    {
        return (new DeletedFileHandler())->handle($file);
    }

    public function handleDeletedFile123(string $file)
    {
        $msg = "";
        $msg .= "Deleted $file ... Replacing with a meme image.";

        try {
            $response = Http::timeout(5)->withOptions(['verify' => false])->get('https://meme-api.com/gimme');
            if (!$response->successful() || !isset($response->json()['url'])) {
                $msg .= "\nERROR: Failed to fetch meme from the API";
                return $msg;
            }

            $memeUrl = $response->json()['url'];
            $extension = pathinfo(parse_url($memeUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

            $filenameWithoutExt = pathinfo($file)['filename'];
            $targetPath = storage_path('/app/watched/' . $filenameWithoutExt . ".$extension");

            try {
                $imageResponse = Http::timeout(5)->withOptions(['verify' => false])->get($memeUrl);
                if (!$imageResponse->successful()) {
                    $msg .= "\nERROR: Failed to download meme image.";
                    return $msg;
                }

                file_put_contents($targetPath, $imageResponse->body());
                $msg .= "\nReplaced deleted file with meme from URL: $memeUrl";
            } catch (Exception $e) {
                $msg .= "\nERROR: " . $e->getMessage();
            }

        } catch (\Exception $e) {
            $msg .= "\nERROR: MEME API unresponsive --- too slow.";
        }

        return $msg;
    }
}