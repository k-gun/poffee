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
        $tokens = [];
        foreach ($file as $i => $value) {
            $tokens = array_merge($tokens, $this->lexer->doScan($i + 1, $value));
        }
        pre($tokens);
    }
}
