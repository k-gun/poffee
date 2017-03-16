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
        $lines = file($file);
        $tokens = [];
        foreach ($lines as $i => $line) {
            $tokens = array_merge($tokens, $this->lexer->scan($file, $i + 1, $line, $lines)->toArray());
        }
        $tokens = clear($tokens);
        prf($tokens);
    }
}

// @tmp?
function clear($tokens) { //return $tokens;
    foreach ($tokens as &$token) {
        // $token->tokens = 'Poffee\Tokens {...}';
        unset($token->tokens);
        if ($token->children) {
            $token->children = clear($token->children->toArray());
        }
    }
    return $tokens;
}
