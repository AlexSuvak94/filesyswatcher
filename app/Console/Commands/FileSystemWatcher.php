<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Services\FileChangeDetector;
use App\Services\FileProcessor;

class FileSystemWatcher extends Command
{
    // The command signature
    protected $signature = "fs";
    protected $description = "Watch the storage/app/watched directory for file changes and handle them";

    // This is the path to watch, relative to storage/app
    protected $watchPath = 'watched';

    // A path to save previous state file
    protected $stateFile = "fswatcher_state.json";

    public function handle()
    {
        $this->info("\nStarting file system watcher...\n");

        $detector = new FileChangeDetector($this->watchPath, $this->stateFile);
        $processor = new FileProcessor();

        while (true) {

            if (file_exists(storage_path('app/watched/stop_watching.txt'))) {
                $this->info("Stopping file system watcher");
                return;
            }

            $this->info("Scanning storage/app/{$this->watchPath}");
            $changes = $detector->scanAndDetect();

            foreach ($changes['created'] as $file) {
                $this->info("File created: $file");
                $this->info($processor->processFile($file));
            }

            foreach ($changes['modified'] as $file) {
                $this->info("File modified: $file");
                $processor->processFile($file);
            }

            foreach ($changes['deleted'] as $file) {
                $this->info("File deleted: $file");
                $this->info($processor->handleDeletedFile($file));
            }

            $this->info("Scan complete.\n");
            sleep(3);
        }
    }
}