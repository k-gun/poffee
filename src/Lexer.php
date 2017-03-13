<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_ASSIGN = 'T_ASSIGN'; // -10;
const T_ASSIGN_NAME = 'T_ASSIGN_NAME'; // -11;
const T_ASSIGN_VALUE = 'T_ASSIGN_VALUE'; // -12;
const T_VAR = 'T_VAR'; // -13;
const T_CONST = 'T_CONST'; // -14;
const T_IDENTIFIER = 'T_IDENTIFIER'; // -15;

const T_STRING = 'T_STRING'; // -21;
const T_STRING_VAR = 'T_STRING_VAR'; // -22;
const T_NUMBER = 'T_NUMBER'; // -23;

const T_FUNCTION = 'T_FUNCTION'; // -30;
const T_FUNCTION_CALL = 'T_FUNCTION_CALL'; // -31;


const KEYWORDS = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default',
'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif',
'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach',
'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof',
'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];
const KEYWORDS_DECLARE = [''];
const KEYWORDS_FUNCTION = ['declare', 'die', 'echo', 'empty', 'eval', 'exit',
    'include', 'include_once', 'isset', 'list', 'print', 'require', 'require_once', 'unset',
    '__halt_compiler'];
const KEYWORDS_CONDITION = ['if', 'else', 'elseif', 'else if'];
const KEYWORDS_LOOP = ['for', 'foreach', 'while'];

final class Lexer
{
    private static $space = ' ';
    private static $indent = '    ';
    private static $eol = PHP_EOL;

    public function __construct(string $indent = null)
    {
        if ($indent) self::$indent = $indent;
    }

    public function doScan($line, $input)
    {
        $this->line = $line;
        $pattern = '~
             (?:(\s+)?//\s*([^\r\n]+))                  # comment
            #|(?:(\s+)?(use)\s*([^\r\n]+))               # use
            #|(?:(\s+)?(if|else|elseif)\s*(.+:))         # condition
            #|(?:(\s+)?(const)\s+([a-z_][a-z0-9_]*)\s*(=)\s*(.+))   # const
            |(?:(\s+)?([a-z_][a-z0-9_]*)\s*(=)\s*(.+))   # assign
            |(?:(\s+)?('. join('|', KEYWORDS_FUNCTION) .')\s*\((.+)\))
            #|(?:(\s+)?\s+|(.))                          # any
        ~ix';
        $matches = $this->getMatches($pattern, $input);
        // pre($matches);
        return $this->generateTokens($matches);
    }

    public function doSubscan($line, $input)
    {
        // if (strlen($input) < 3) return;

        // $this->line = $line;
        // $pattern = '~
        //     (?:(\s+)?([a-z_][a-z0-9_]*)\s*(?=\((.*)\)))                  # function
        // ~ix';
        // $matches = $this->getMatches($pattern, $input);
        // // preg_match_all($pattern, $input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        // // prd($matches);
        // return $this->generateTokens($matches);
    }

    public function generateTokens(array $matches)
    {
        $tokens = new Tokens();
        foreach ($matches as $match) {
            $value = $match[0];
            $indent = 0;
            if ($value[0] == ' ') {
                $indent = strlen($value);
            }
            $type = $this->getType($value);
            $length = strlen($value);
            $start = $match[1]; $end = $start + $length;

            $tokens->add(new Token([
                'value' => $value, 'type' => $type,
                'line' => $this->line, 'indent' => $indent,
                'start' => $start, 'end' => $end, 'length' => $length,
                'tokens' => null,
            ]));
        }

        if ($tokens->count()) {
            // burda next prev vs isleri iste...
            while ($token = $tokens->getNext()) {
                pre($token->type);
            }
            pre($tokens->pointer());
        }
        prd($tokens);
        die;
        return $tokens->toArray();
    }

    public function getType($value)
    {
        switch ($value) {
            case self::$space:  return T_SPACE;
            case self::$indent: return T_INDENT;
            case self::$eol:    return T_EOL;
            case '=':           return T_ASSIGN;
            case 'use':         return T_USE;
            case 'const':       return T_CONST;
            default:
                $fChar = substr($value, 0, 1);
                $lChar = substr($value, -1);
                if ($lChar == "'" || $lChar == "'") {
                    return T_STRING;
                } elseif ($lChar == '"' || $lChar == '"') {
                    return T_STRING_VAR;
                } elseif (is_numeric($value)) {
                    return T_NUMBER;
                // } elseif ($value) {
                //     return T_FUNCTION_CALL;
                }
        }
        return T_NONE;
    }

    public function getMatches($pattern, $input)
    {
        return preg_split($pattern, $input, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
    }
}

class Token
{
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}

class Tokens implements \Countable, \IteratorAggregate
{
    private $tokens = [];
    private $count = 0;
    private $pointer = 0;

    public function __construct()
    {}

    public function add(Token $token)
    {
        $this->tokens[$this->count++] = $token;
    }
    public function get(int $pointer = null)
    {
        return $this->tokens[$pointer ?? $this->pointer] ?? null;
    }

    public function getPrev()
    {
        return $this->get($this->pointer--);
    }
    public function hasPrev()
    {
        return isset($this->tokens[$this->pointer - 1]);
    }
    public function getNext()
    {
        return $this->get($this->pointer++);
    }
    public function hasNext()
    {
        return isset($this->tokens[$this->pointer]);
    }

    public function reset()
    {
        $this->pointer = 0;
    }
    public function count()
    {
        return $this->count;
    }
    public function pointer()
    {
        return $this->pointer;
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->tokens);
    }

    public function toArray()
    {
        return array_filter($this->tokens);
    }
}
