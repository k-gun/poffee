<?php
declare(strict_types=1); namespace Poffee;

class Parser
{
    private $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    public function parse($file)
    {
        $file = file($file);
        foreach ($file as $i => $value) {
            $this->lexer->setInput($i + 1, $value);
        }
    }
}
