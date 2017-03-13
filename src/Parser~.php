<?php
declare(strict_types=1); namespace Poffee;

final class Parser
{
    use Util\GetterTrait;

    const REGEXP_CONTROL = '~^(use|class|interface|trait|function|do|while|for|foreach|if|else|elseif)\s+(.+)~i';
    const REGEXP_ASSIGNMENT = '~^(\$?[\w]+)\s*=\s*(.+)~i';

    const KEYWORDS = ['abstract', 'and', 'array', 'as', 'break', 'callable', 'case',
        'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo',
        'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch',
        'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function',
        'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof',
        'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected',
        'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try',
        'unset', 'use', 'var', 'while', 'null', 'true', 'false', 'is', 'ise', 'not'];

    private $dir;
    private $file;
    private $nodes = [];

    private $indent = '    ';

    final public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    final public function parse(string $file)
    {
        $this->file = basename($file);
        if (!is_file($this->file)) {
            throw new ParserException("No such file {$this->file} found!");
        }

        $contents = file(sprintf('%s/%s', $this->dir, $this->file));
        // pre($contents);

        foreach ($contents as $i => $content) {
            $line = $i + 1;
            // $content = trim($content);
            if (preg_match(self::REGEXP_CONTROL, $content, $match)) {
                $this->renderControl($line, $match[1], $match[2]);
            } elseif (preg_match(self::REGEXP_ASSIGNMENT, $content, $match)) {
                $this->renderAssignment($line, $match[1], $match[2]);
            }
        }

        return $this;
    }

    final private function renderControl(int $line, string $name, string $value)
    {
        switch ($name) {
            case Node::NAME_USE:
                $node = new Node\UseNode($this, $name, $value, $this->file, $line);
                break;
            case Node::NAME_IF:
                $node = new Node\IfNode($this, $name, $value, $this->file, $line);
                break;
        }

        if (isset($node)) {
            $this->nodes[] = $node->render();
        }
    }

    final private function renderAssignment(int $line, string $name, string $value)
    {
        $node = new Node\VarNode($this, $name, $value, $this->file, $line);
        $this->nodes[] = $node->render();
    }

    final public function toString(): string
    {
        $contents = '';

        return "..";
    }

    final public static function updateVariables(string $input): string
    {
        // pre($input);
        $input = preg_replace('~\.+~', '->', $input);
        $input = preg_replace_callback('~\b([a-z][a-z0-9_]+)\b~i', function($match) {
            // pre(self::KEYWORDS);
            // pre($match);

            $value = $match[1];
            if (in_array($value, self::KEYWORDS)) {
                // pre(":::::::::::::::::$value");
                return $value;
            }
            return sprintf('$%s', $value);
        }, $input);
        return $input;
    }

    // [\'"]
    final public static function updateConditions(string $input): string
    {
        // $input = preg_replace_callback('~(?![\'"])(is(e)?)?\s+(not)?\s+([^\s]+)~', function($match) {
        // $input = preg_replace_callback('~([^\s]+)\s*(?![\'"])(is(e)?(?:\s+))?(not(?:\s+))?([^\s]+)~', function($match) {
        //     pre($match);
        //     $var = $match[1];
        //     $equ = $match[3];
        //     $not = $match[4];
        //     if ($match[5]) {
        //         // $foo is empty
        //         if ($match[5] == 'empty') {
        //             return sprintf('%sempty(%s)', ($not ? '!' : ''), $var);
        //         }
        //         // not $foo
        //         if ($match[5] && $var && $var[0] != '$') {
        //             return sprintf('empty(%s)', $match[5]);
        //         }
        //     }
        //     return sprintf('%s %s %s', $var,
        //         ($not ? ($equ ? '!==' : '!=') : ($equ ? '===' : '==')), $not);
        // }, $input);

        $input = preg_replace_callback('~([^\s]+)\s*(is(e)?)?\s*(not)?\s*([^\s]+)~', function($match) {
            pre($match);
            // not, equ?
            $not = (bool) $match[4];
            $equ = (bool) $match[3];

            if ($match[5] == 'empty') {
                return sprintf('%sempty(%s)', ($not ? '!' : ''), $match[1]);
            }

            return sprintf('%s %s %s', $match[1],
                ($not ? ($equ ? '!==' : '!=') : ($equ ? '===' : '==')), $match[5]);
        }, $input);

        return $input;
    }
}
