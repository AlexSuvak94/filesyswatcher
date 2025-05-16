<?php

namespace App\Console\Commands;

use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FileSystemWatcher extends Command
{
    // The command signature
    protected $signature = "fs:watch";

    protected $description = "Watch the storage/app/watched directory for file changes and handle them";

    // This is the path to watch, relative to storage/app
    // I had to edit the config/filesystems.php file, since the local storage path was by default set to "app/private" --- I changed it to "app"
    protected $watchPath = 'watched';

    // A path to save previous state file
    protected $stateFile = "fswatcher_state.json";

    public function handle()
    {
        $this->info("Starting file system watcher...");

        // This is an infinite loop to keep watching
        while (true) {
            $this->scanAndProcess();
            sleep(5);
        }
    }

    protected function scanAndProcess()
    {
        $this->info("Scanning files...");

        // Step 1: Load previous state from JSON file
        $previousState = $this->loadPreviousState();

        // Step 2: Scan current files and their modification times
        $currentState = $this->getCurrentFilesState();

        // Step 3: Compare previous and current to find changes
        $changes = $this->detectChanges($previousState, $currentState);

        // Step 4: Process detected changes
        $this->processChanges($changes);

        // Step 5: Save current state for next scan
        $this->saveCurrentState($currentState);

        $this->info("Scan complete.");
        $this->info(""); // Blank line
    }

    protected function loadPreviousState()
    {
        if (!Storage::exists($this->stateFile)) {
            return [];
        }

        $json = Storage::get($this->stateFile);
        return json_decode($json, true) ?: [];
    }

    protected function saveCurrentState(array $state)
    {
        Storage::put($this->stateFile, json_encode($state));
    }

    protected function getCurrentFilesState()
    {
        // List files in "watched" directory
        $files = Storage::files($this->watchPath);

        $state = [];

        foreach ($files as $file) {
            $state[$file] = Storage::lastModified($file);
        }

        return $state;
    }

    protected function detectChanges(array $previousState, array $currentState)
    {
        $changes = [
            'created' => [],
            'modified' => [],
            'deleted' => [],
        ];

        // Now detect created and modified files
        foreach ($currentState as $file => $modifiedTime) {
            if (!isset($previousState[$file])) {
                $changes['created'][] = $file;
            } elseif ($previousState[$file] !== $modifiedTime) {
                $path = storage_path('app/' . $file);
                $contents = file_get_contents($path);
                if (!str_contains($contents, "Appended by FileSystemWatcher")) {
                    $changes['modified'][] = $file;
                }
            }
        }

        // Detect deleted files
        foreach ($previousState as $file => $modifiedTime) {
            if (!isset($currentState[$file])) {
                $changes['deleted'][] = $file;
            }
        }

        return $changes;
    }

    protected function processChanges(array $changes)
    {
        foreach ($changes['created'] as $file) {
            $this->info("File created: $file");
            $this->processFile($file);
        }

        foreach ($changes['modified'] as $file) {
            $this->info("File modified: $file");
            $this->processFile($file);
        }

        foreach ($changes['deleted'] as $file) {
            $this->info("File deleted: $file");
            $this->handleDeletedFile($file);
        }
    }

    protected function handleDeletedFile(string $file)
    {
        $this->info("Deleted $file ... Replacing with a meme image.");

        $response = Http::withOptions(['verify' => false])->get('https://meme-api.com/gimme');
        if (!$response->successful()) {
            $this->error("Failed to fetch meme from the API");
            return;
        }

        $memeData = $response->json();
        if (!isset($memeData['url'])) {
            $this->error("Meme API response doesn't contain a valid URL.");
            return;
        }

        $memeUrl = $memeData['url'];

        // Determine the file extension from the meme URL
        $extension = pathinfo(parse_url($memeUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

        // Define the path to save the meme image
        $filenameWithoutExt = pathinfo($file)['filename'];
        $targetPath = storage_path('app/watched/' . $filenameWithoutExt . '.png');

        // Download the MEME image
        try {
            $imageResponse = Http::withOptions(['verify' => false])->get($memeUrl);
            if (!$imageResponse->successful()) {
                $this->error("Failed to download meme image.");
                return;
            }

            file_put_contents($targetPath, $imageResponse->body());
            $this->info("Replaced deleted file with meme from URL: $memeUrl");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function processFile(string $file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'txt':
                $this->appendText($file);
                break;

            case 'jpg':
            case 'jpeg':
                $this->optimizeJpg($file);
                break;

            case 'zip':
                $this->extractZip($file);
                break;

            case 'json':
                $this->postJSON($file);
                break;
        }
    }

    protected function postJSON(string $file)
    {
        $path = storage_path('app/' . $file);
        $this->info("Posting JSON content: $file");

        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return;
        }

        $content = file_get_contents($path);
        $jsonData = json_decode($content, true);

        if ($jsonData === null) {
            $this->error("Invalid JSON in file: $file");
            return;
        }

        $endpoint = 'https://fswatcher.requestcatcher.com/';

        $response = Http::withOptions(['verify' => false])->post($endpoint, $jsonData);
        if ($response->successful()) {
            $this->info("JSON posted successfully!");
        } else {
            $this->error("Failed to post JSON. Status: " . $response->status());
            $this->error("Response: " . $response->body());
        }
    }

    protected function extractZip(string $file)
    {
        $path = storage_path('app/' . $file);
        $extractTo = storage_path('app/watched');

        $this->info("Extracting Zip: $file");

        $zip = new \ZipArchive;
        if ($zip->open($path) === TRUE) {
            $zip->extractTo($extractTo);
            $zip->close();

            $this->info("Zip extracted successfully.");
        } else {
            $this->error("Failed to open zip file: $file");
        }
    }

    protected function optimizeJpg(string $file)
    {
        $path = storage_path('app/' . $file);

        if (!file_exists($path)) {
            $this->warn("File not found: $file");
            return;
        }

        // Load original file content
        $contents = file_get_contents($path);

        // Check for marker
        if (str_contains($contents, 'OptimizedByFileSystemWatcher')) {
            $this->info("Already optimized: $file");
            return;
        }

        // Load image using Intervention
        $image = Image::make($path);

        // Re-encode at lower quality (e.g., 75%)
        $image->encode('jpg', 75);

        // Append marker to content
        $optimizedData = $image->__toString() . "\n<!-- OptimizedByFileSystemWatcher -->";

        // Save over the original
        file_put_contents($path, $optimizedData);

        $this->info("Optimized: $file");
    }

    protected function appendText(string $file)
    {
        $path = storage_path('app/' . $file);

        $response = Http::withOptions(['verify' => false])->get('https://baconipsum.com/api/?type=meat-and-filler');
        if (!$response->successful()) {
            $this->error("Failed to fetch!");
            return;
        }

        $baconTextArray = $response->json();
        $baconText = "\n\n" . implode("\n\n", $baconTextArray) . "\n\n Appended by FileSystemWatcher";

        file_put_contents($path, $baconText, FILE_APPEND);
    }
}