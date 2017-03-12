<?php
declare(strict_types=1); namespace Poffee;

final class Poffee
{
    private $parser;

    final public function __construct(string $dir)
    {
        $this->parser = new Parser($dir);
    }

    final public function parse(string $file)
    {
        return $this->parser->parse($file);
    }

    final public function write()
    {}
    final public function writeTo(string $file)
    {}
}
