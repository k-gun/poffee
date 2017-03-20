<?php
declare(strict_types=1); namespace Poffee;

class Parser
{
    private $lexer;

    public function __construct(string $indent = null)
    {
        $this->lexer = new Lexer($indent);
    }

    public function parse(FileReader $reader)
    {
        $file = $reader->getFile();
        if (!$reader->read()) {
            throw new \Exception("Could not read file: {$file}!");
        }

        $tokens = [];
        foreach (($lines = $reader->getLines()) as $i => $line) {
            $scan = $this->lexer->scan($file, $i + 1, $line, $lines);
            $scan = $this->lexer->toAst($scan);
            $tokens = array_merge($tokens, $scan);
        }

        return $tokens;
    }
}

// @tmp?
// function clear($tokens) {
//     foreach ($tokens as &$token) {
//         unset($token->tokens);
//         if ($token->children) {
//             $token->children = clear($token->children->toArray());
//         }
//     }
//     return $tokens;
// }
