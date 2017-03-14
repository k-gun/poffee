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
            $tokens = array_merge($tokens, $this->lexer->doScan($i + 1, $value)->toArray());
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
