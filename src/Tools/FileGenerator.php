<?php

namespace quintenmbusiness\LaravelProjectGeneration\Tools;

use Illuminate\Filesystem\Filesystem;

class FileGenerator
{
    protected Filesystem $files;

    public function __construct()
    {
        $this->files = new Filesystem();
    }

    public function createFile(string $laravelPath, string $content): void
    {
        // Normalize directory separators
        $laravelPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $laravelPath);

        // Prepend base_path if relative
        if (!preg_match('/^([a-zA-Z]:)?[\/\\\\]/', $laravelPath)) {
            $laravelPath = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($laravelPath, DIRECTORY_SEPARATOR);
        }

        $directory = dirname($laravelPath);

        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($laravelPath, $content);
    }
}
