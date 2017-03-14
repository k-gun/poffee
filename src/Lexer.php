<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_COLON = 'T_COLON';
const T_ASSIGN = 'T_ASSIGN';
const T_OPERATOR = 'T_OPERATOR';

const T_VAR = 'T_VAR';
const T_OBJECT = 'T_OBJECT';
const T_MODIFIER = 'T_MODIFIER';
const T_USE = 'T_USE';
const T_CONST = 'T_CONST';
const T_CLASS = 'T_CLASS';
const T_RETURN = 'T_RETURN';
const T_IF = 'T_IF';
const T_ELSE = 'T_ELSE';
const T_ELSE_IF = 'T_ELSE_IF';
const T_IDENTIFIER = 'T_IDENTIFIER';
const T_PARENTHESIS_BLOCK = 'T_PARENTHESIS_BLOCK';

const T_STRING = 'T_STRING';
const T_STRING_VAR = 'T_STRING_VAR';
const T_NUMBER = 'T_NUMBER';
const T_BOOLEAN = 'T_BOOLEAN';

const T_FUNCTION = 'T_FUNCTION';
const T_FUNCTION_CALL = 'T_FUNCTION_CALL';

// const KEYWORDS = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
// 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default',
// 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif',
// 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach',
// 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof',
// 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
// 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
// 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'];
const KEYWORDS_OBJECT = ['class', 'interface', 'trait', 'function'];
const KEYWORDS_MODIFIER = ['abstract', 'final', 'static', 'public', 'private', 'protected'];
const KEYWORDS_FUNCTION = ['declare', 'die', 'echo', 'empty', 'eval', 'exit',
    'include', 'include_once', 'isset', 'list', 'print', 'require', 'require_once', 'unset',
    '__halt_compiler'];
const KEYWORDS_CONDITION = ['if', 'else', 'elseif', 'else if'];
const KEYWORDS_LOOP = ['for', 'foreach', 'while'];

function is_identifier($s) { return preg_match('~^[a-z_][a-z0-9_]*$~i', $s); }

class Lexer
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
            |(?:(\s+)?(use)\s*([^\r\n]+))               # use
            |(?:(\s+)?(const)\s+([a-z_][a-z0-9_]*)\s*(=)\s*(.+))   # const
            |(?:(\s+)?(abstract|final|static)?\s*(class)\s+(\w+)(:)) # class
            |(?:(\s+)?(return)\s+(.+))                          # return
            |(?:(\s+)?(if|else|else\s*if)\s+(.+)(:))         # condition
            |(?:(\s+)?([a-z_][a-z0-9_]*)\s*(=)\s*(.+))   # assign
            #|(?:(\s+)?('. join('|', KEYWORDS_FUNCTION) .')\s*\((.+)\))
            #|(?:(\s+)?\s+|(.))                          # any
        ~ix';
        $matches = $this->getMatches($pattern, $input);
        // preg_match($pattern, $input, $matches, PREG_OFFSET_CAPTURE); $matches = array_filter($matches, function($match) { return $match[1] > -1; });
        // pre($matches);
        return $this->generateTokens($matches);
    }

    public function doSubscan($line, $input)
    {
        if (strlen($input) < 3) return;
        $this->line = $line;
        $pattern = '~
             (?:(\s+)?([a-z_][a-z0-9_]*)\s*(?=\((.*)\)))
            |(?:(\s+)?([a-z_][a-z0-9_]*)\s*(?=\((.+)\)))
            |(?:(\s+)?([^\s]+)\s*([\<\>\!\=\*/\+\-%\|\^\~]+)\s*(.+))
        ~ix';
        $matches = $this->getMatches($pattern, $input);
        pre($matches);
        return $this->generateTokens($matches);
    }

    public function generateTokens(array $matches)
    {
        $tokens = [];
        foreach ($matches as $match) {
            $value = $match[0];
            if ($value == self::$space) {
                continue;
            }
            $indent = 0;
            if ($value[0] == ' ') {
                $indent = strlen($value);
            }
            $type = $this->getType($value);
            $length = strlen($value);
            $start = $match[1]; $end = $start + $length;
            $tokens[] = [
                'value' => $value, 'type' => $type,
                'line' => $this->line, 'indent' => $indent,
                'start' => $start, 'end' => $end, 'length' => $length,
                'children' => null,
            ];
        }
        return $this->checkTypes($tokens);
    }

    public function getType($value)
    {
        $value = strval($value);
        switch ($value) {
            case self::$space:  return T_SPACE;
            case self::$indent: return T_INDENT;
            case self::$eol:    return T_EOL;

            case '=':           return T_ASSIGN;
            case ':':           return T_COLON;
            case '<': case '>': case '!':
            case '%': case '*': case '/':
            case '+': case '-': case '|':
            case '^': case '~': return T_OPERATOR;

            // case 'use':         return T_USE;
            // case 'const':       return T_CONST;
            // case 'class':       return T_CLASS;
            // case 'return':      return T_RETURN;
            // case 'true':
            // case 'false':      return T_BOOLEAN;
            // case 'if':         return T_IF;

            default:
                if (in_array($value, KEYWORDS_OBJECT)) {
                    return T_OBJECT;
                } elseif (in_array($value, KEYWORDS_MODIFIER)) {
                    return T_MODIFIER;
                }
                // $name = strtoupper("t_{$value}");
                // if (defined($name)) {
                //     return $name; // @tmp // constant($name);
                // }

                $fChar = substr($value, 0, 1); $lChar = substr($value, -1);
                if ($fChar == "'" && $lChar == "'") {
                    return T_STRING;
                } elseif ($fChar == '"' && $lChar == '"') {
                    return T_STRING_VAR;
                } elseif (is_numeric($value)) {
                    return T_NUMBER;
                } elseif ($fChar == '(' && $lChar == ')') {
                    return T_PARENTHESIS_BLOCK;
                }
        }
        return T_NONE;
    }
    public function checkTypes(array $tokens)
    {
        $tokens = new Tokens($tokens);
        if (!$tokens->isEmpty()) {
            while ($token = $tokens->getNext()) {
                if ($token->hasPrev()) {
                    $prev = $token->prev;
                    if ($token->type == T_ASSIGN) {
                        $prev->type = T_IDENTIFIER;
                    }
                    if ($token->type == T_NONE) {
                        $lexer = new Lexer();
                        $children = $lexer->doSubscan($token->line, $token->value);
                        if ($children) {
                            if ($children->getFirst()->value != $token->value) {
                                $token->children = new Tokens($children->toArray());
                                while ($child = $token->children->getNext()) {
                                    $next = $child->next;
                                    if ($next && $next->type == T_OPERATOR &&
                                        $child->type == T_NONE && is_identifier($child->value)) {
                                        $child->type = T_IDENTIFIER;
                                    }
                                }
                            }
                        }
                        if ($prev->type == T_OBJECT /* class, function etc */ ||
                            $prev->type == T_MODIFIER /* property */) {
                            $token->type = T_IDENTIFIER;
                        }
                    }
                }
            }
        }
        return $tokens;
    }
    public function getMatches($pattern, $input)
    {
        return preg_split($pattern, $input, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
    }
}

class Token
{
    public function __construct(Tokens $tokens, array $data)
    {
        $this->tokens = $tokens;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
    public function __get($name)
    {
        if ($name == 'prev') return $this->getPrev();
        if ($name == 'next') return $this->getNext();
    }
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
    public function hasPrev()
    {
        return $this->tokens->has($this->index - 1);
    }
    public function hasNext()
    {
        return $this->tokens->has($this->index + 1);
    }
    public function getPrev()
    {
        return $this->tokens->get($this->index - 1);
    }
    public function getNext()
    {
        return $this->tokens->get($this->index + 1);
    }
    public function toArray()
    {
        return get_object_vars($this);
    }
}

class Tokens
{
    private $tokens = [];
    private $tokensIndex = 0;
    private $tokensIndexPointer = 0;

    public function __construct(array $tokens = null)
    {
        if ($tokens) foreach ($tokens as $token) {
            $this->add($token);
        }
    }

    public function add(array $data)
    {
        $token = new Token($this, $data);
        $token->index = $this->tokensIndex;
        $this->tokens[$this->tokensIndex] = $token;
        $this->tokensIndex++;
    }

    public function has(int $i)
    {
        return isset($this->tokens[$i]);
    }
    public function hasNext()
    {
        return $this->has($this->tokensIndexPointer);
    }

    public function get(int $i)
    {
        return $this->tokens[$i] ?? null;
    }
    public function getNext()
    {
        return $this->get($this->tokensIndexPointer++);
    }
    public function getFirst()
    {
        return $this->get(0);
    }
    public function getLast()
    {
        return $this->get($this->tokensIndex - 1);
    }

    public function getTokensIndex()
    {
        return $this->tokensIndex;
    }
    public function getTokensIndexPointer()
    {
        return $this->tokensIndexPointer;
    }

    public function reset()
    {
        $this->tokensIndexPointer = 0;
    }
    public function count()
    {
        return count($this->tokens);
    }
    public function isEmpty()
    {
        return empty($this->tokens);
    }
    public function toArray()
    {
        return array_filter($this->tokens);
    }
}
