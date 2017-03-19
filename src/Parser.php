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
        $array = [];
        foreach ($lines as $i => $line) {
            $tokens = $this->lexer->scan($file, $i + 1, $line, $lines);
            $tokens = $this->lexer->toAst($tokens);
            // $array = array_merge($array, $tokens->toArray());
            $array = array_merge($array, $tokens);
        }
        // $array = clear($array);
        prf($array);
    }
}

// @tmp?
function clear($tokens) {
    foreach ($tokens as &$token) {
        unset($token->tokens);
        if ($token->children) {
            $token->children = clear($token->children->toArray());
        }
    }
    return $tokens;
}
