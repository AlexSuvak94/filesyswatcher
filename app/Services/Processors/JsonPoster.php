<?php

namespace App\Services\Processors;

use Illuminate\Support\Facades\Http;

class JsonPoster
{
    public function handle(string $file): string
    {
        $path = storage_path('app/' . $file);
        $jsonData = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Invalid JSON file.";
        }

        $response = Http::withOptions(['verify' => false])
            ->post("https://fswatcher.requestcatcher.com/", $jsonData);

        if ($response->successful()) {
            return "JSON posted successfully!";
        }

        return "Failed to post JSON file: $file";
    }
}