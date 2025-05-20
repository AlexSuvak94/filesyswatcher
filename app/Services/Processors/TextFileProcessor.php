<?php

namespace App\Services\Processors;

use Illuminate\Support\Facades\Http;

class TextFileProcessor
{
    public function handle(string $file): string
    {
        $path = storage_path('app/' . $file);
        $response = Http::withOptions(['verify' => false])->get("https://baconipsum.com/api/?type=meat-and-filler");

        if (!$response->successful()) {
            return "ERROR: Failed to fetch bacon text.";
        }

        $baconText = "\n\n" . implode("\n\n", $response->json()) . "\n\nAppended by FileSystemWatcher";
        file_put_contents($path, $baconText, FILE_APPEND);

        return "Appended bacon text to $file.";
    }
}