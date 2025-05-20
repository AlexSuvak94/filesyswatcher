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
    protected $signature = "fs:watch";
    protected $description = "Watch the storage/app/watched directory for file changes and handle them as needed";

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

            // Create stop_watching.txt to stop the watcher
            if (file_exists(storage_path("app/{$this->watchPath}/stop_watching.txt"))) {
                $this->info("Stopping file system watcher");
                return;
            }

            $this->info("Scanning storage/app/{$this->watchPath}");
            $changes = $detector->scanAndDetect();

            $result = "";
            foreach ($changes['created'] as $file) {
                $this->info("File created: $file");
                $result = $processor->processFile($file, $this->watchPath);
                $this->updateConsole($result);

            }

            foreach ($changes['modified'] as $file) {
                $this->info("File modified: $file");
                $result = $processor->processFile($file, $this->watchPath);
                $this->updateConsole($result);
            }

            foreach ($changes['deleted'] as $file) {
                $this->info("File deleted: $file");
                $result = $processor->handleDeletedFile($file, $this->watchPath);
                $this->updateConsole($result);
            }
            
            //if ($changes['created'][0]) {
                //$this->info(var_dump($changes['created'][0]['hash']));
            // }

            $this->info("Scan complete.\n");
            sleep(4);
        }
    }

    public function updateConsole($myString)
    {
        if (!empty($myString)) {
            if (str_contains($myString, "ERROR")) {
                $this->error($myString);
            } else {
                $this->info($myString);
            }
        }
    }
}