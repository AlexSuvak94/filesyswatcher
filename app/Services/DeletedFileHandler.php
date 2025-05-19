<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class DeletedFileHandler
{
    public function handle(string $file): string
    {
        $msg = "Deleted $file. Attempting to replace with a meme.";

        try {
            $response = Http::timeout(5)->withOptions(['verify' => false])->get('https://meme-api.com/gimme');
            if (!$response->successful() || !isset($response->json()['url'])) {
                return "$msg\nFailed to fetch meme.";
            }

            $memeUrl = $response->json()['url'];
            $ext = pathinfo(parse_url($memeUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $targetPath = storage_path("app/watched/{$filename}.{$ext}");

            $img = Http::timeout(5)->withOptions(['verify' => false])->get($memeUrl);
            if ($img->successful()) {
                file_put_contents($targetPath, $img->body());
                return "$msg\nReplaced with meme from: $memeUrl.";
            }

            return "$msg\nFailed to download meme image.";

        } catch (Exception $e) {
            return "$msg\nException: " . $e->getMessage();
        }
    }
}