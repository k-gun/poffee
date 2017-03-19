<?php
declare(strict_types=1); namespace Poffee;

class FileWriter
{
    private $file;

    function __construct(string $file)
    {
        $this->file = $file;
    }

    function write()
    {
        @ touch($this->file);
        if (!is_file($this->file)) {
            throw new \Exception("Could not create file: {$this->file}!");

        }

        // ...

        return !error_get_last();
    }

    function getFile(): string
    {
        return $this->file;
    }
}
