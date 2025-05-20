<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileChangeDetector
{
    protected $watchPath;
    protected $stateFile;

    public function __construct($watchPath, $stateFile)
    {
        $this->watchPath = $watchPath;
        $this->stateFile = $stateFile;
    }

    public function scanAndDetect()
    {
        $previousState = $this->loadPreviousState();
        $currentState = $this->getCurrentFileState();

        $changes = [
            'created' => [],
            'modified' => [],
            'deleted' => [],
        ];

        foreach ($currentState as $file => $modifiedTime) {
            if (!isset($previousState[$file])) {
                $changes['created'][] = $file;
            } elseif ($previousState[$file]['hash'] !== $modifiedTime['hash']) { // INSPECT the hash thing
                $path = storage_path('app/' . $file);
                $contents = file_get_contents($path);
                if (!str_contains($contents, "Appended by FileSystemWatcher")) {
                    $changes['modified'][] = $file;
                }
            }
        }

        foreach ($previousState as $file => $modifiedTime) {
            if (!isset($currentState[$file])) {
                $changes['deleted'][] = $file;
            }
        }

        $this->saveCurrentState($currentState);
        return $changes;
    }

    public function loadPreviousState()
    {
        if (!Storage::exists($this->stateFile)) {
            return [];
        }

        $json = Storage::get($this->stateFile);
        return json_decode($json, true) ?: [];
    }

    protected function getCurrentFileState()
    {
        $files = Storage::files($this->watchPath);
        $state = [];

        foreach ($files as $file) {
            // $state[$file] = Storage::lastModified($file); -- Maybe Keep Only This Line if Nothing Works

            $fullPath = storage_path('app/' . $file);
            $contents = file_get_contents($fullPath);
            $hash = hash('sha256', $contents);
            $modified = filemtime($fullPath);

            $state[$file] = [
                'hash' => $hash,
                'lastModified' => $modified
            ];
        }

        return $state;
    }

    public function saveCurrentState(array $state)
    {
        Storage::put($this->stateFile, json_encode($state));
    }
}