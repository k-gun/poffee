<?php
declare(strict_types=1); namespace Poffee;

class FileReader
{
    private $file;
    private $flags = 0;
    private $lines = [];

    function __construct(string $file, int $flags = 0)
    {
        $this->file = $file;
        $this->flags = $flags;
    }

    function read(): bool
    {
        if (!is_file($this->file)) {
            throw new \Exception("File not exists: {$this->file}!");

        }

        $this->lines = file($this->file, $this->flags);

        return !error_get_last();
    }

    function getFile(): string
    {
        return $this->file;
    }

    function getFlags(): int
    {
        return $this->flags;
    }

    function getLines(): array
    {
        return $this->lines;
    }
}
