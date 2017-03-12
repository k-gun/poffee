<?php
declare(strict_types=1); namespace Poffee;

class ParserException extends \ParseError
{
    protected $char;

    final public function __construct(string $message, string $file, int $line, int $char = null,
        \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->file = $file;
        $this->line = $line;
        $this->char = $char;
    }

    final public function getChar()
    {
        return $this->char;
    }
}
