<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_OPERATOR = 'T_OPERATOR';
const T_OPERATOR_ASSIGN = 'T_OPERATOR_ASSIGN';
const T_OPERATOR_COLON = 'T_OPERATOR_COLON';
const T_OPERATOR_QUESTION = 'T_OPERATOR_QUESTION';

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
const T_PARENTHESIS_BLOCK = 'T_PARENTHESIS_BLOCK';

const T_IDENTIFIER = 'T_IDENTIFIER';
const T_IDENTIFIER_VAR = 'T_IDENTIFIER_VAR';

const T_NULL = 'T_NULL';
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
const KEYWORDS_MODIFIER = ['abstract', 'final', 'static', 'public', 'private', 'protected', 'extends', 'implements'];
const KEYWORDS_FUNCTION = ['declare', 'die', 'echo', 'empty', 'eval', 'exit',
    'include', 'include_once', 'isset', 'list', 'print', 'require', 'require_once', 'unset',
    '__halt_compiler'];
const KEYWORDS_CONDITION = ['if', 'else', 'elseif', 'else if'];
const KEYWORDS_LOOP = ['for', 'foreach', 'while'];
const KEYWORDS_BOOLEAN = ['true', 'false'];

function isValidIdentifier($s) { return preg_match('~^[a-z_][a-z0-9_]*$~i', $s); }

class Lexer
{
    private static $eol = PHP_EOL;
    private static $space = ' ';
    private static $indent = '    ';
    private static $indentLength = 4;

    public function __construct(string $indent = null)
    {
        if ($indent) {
            self::$indent = $indent;
            self::$indentLength = strlen($indent);
        }
    }

    public function doScan($line, $input)
    {
        $this->line = $line;
        $pattern = '~
             (?:(\s+)?//\s*([^\r\n]+))                    # comment
            |(?:(\s+)?(use)\s*([^\r\n]+))                     # use
            |(?:(\s+)?(const)\s+([a-z_][a-z0-9_]*)\s*(=)\s*(.+))   # const
            |(?:(\s+)?(abstract|final|static)?\s*(class)\s+(\w+)\s*
                (?:(extends)\s+(\w+)\s*)?(?:(implements)\s+(\w+)\s*)?(:)) # class
            |(?:(\s+)?(var|public|private|protected)\s+(\w+)(?:\s*(=)\s*([^\s]+))?) # function, property
            |(?:(\s+)?(return)\s+(.+))                          # return
            |(?:(\s+)?(if|else|else\s*if)\s+(.+)(:))         # condition
            |(?:(\s+)?([a-z_][a-z0-9_]*)\s*(=)\s*([^\s]+))   # assign
            |(?:(\s+)?([^\s]+)\s*([\<\>\!\=\*/\+\-%\|\^\~]+)\s*(.+)) # operators
            #|(?:(\s+)?('. join('|', KEYWORDS_FUNCTION) .')\s*\((.+)\))
            #|(?:(\s+)?\s+|(.))                          # any
        ~ix';
        $matches = $this->getMatches($pattern, $input);
        pre($matches);
        return $this->generateTokens($matches);
    }

    public function doSubscan($line, $input)
    {
        if (strlen($input) < 3) return;
        $this->line = $line;
        $pattern = '~
             (?:(\s+)?([a-z_][a-z0-9_]*)\s*(?=\((.*)\)))
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
                continue; // ?
            }
            $length = strlen($value);
            if (ctype_space($value)
                && $length >= self::$indentLength && $length % self::$indentLength == 0) {
                $type = T_INDENT;
            } else {
                $type = $this->getType($value);
            }
            $start = $match[1]; $end = $start + $length;
            $token = ['value' => $value, 'type' => $type, 'line' => $this->line, 'length' => $length,
                'start' => $start, 'end' => $end, 'children' => null];
            $tokens[] = $token;
        }

        $tokens = new Tokens($tokens);
        if (!$tokens->isEmpty()) {
            while ($token = $tokens->next()) {
                if ($token->hasPrev()) {
                    $prev = $token->prev();
                    if ($prev->type == T_NONE
                            && ($token->type == T_OPERATOR || $token->type == T_OPERATOR_ASSIGN)) {
                        $prev->type = T_IDENTIFIER_VAR;
                    }
                    // still not set?
                    if ($token->type == T_NONE) {
                        $lexer = new Lexer(self::$indent);
                        $children = $lexer->doSubscan($token->line, $token->value);
                        if ($children) {
                            if ($children->first->value != $token->value) {
                                $token->children = new Tokens($children->toArray());
                                while ($child = $token->children->next()) {
                                    $next = $child->next();
                                    if ($next && $next->type == T_OPERATOR &&
                                        $child->type == T_NONE && isValidIdentifier($child->value)) {
                                        $child->type = T_IDENTIFIER;
                                    }
                                }
                            }
                        }
                        if ($prev->type == T_OBJECT /* class, function etc */
                                || $prev->type == T_MODIFIER /* property */) {
                            $token->type = T_IDENTIFIER;
                        }
                    }
                }
                if ($token->hasNext()) {
                    $next = $token->next();
                    if ($next->type == T_NONE) {
                        if (isValidIdentifier($next->value)) {
                            $next->type = T_IDENTIFIER_VAR;
                        } else {
                            // ??
                        }
                    }
                }
            }
        }
        return $tokens;
    }

    public function getType($value)
    {
        $value = strval($value);
        switch ($value) {
            case self::$eol:    return T_EOL;
            case self::$space:  return T_SPACE;
            case self::$indent: return T_INDENT;
            case '=':           return T_OPERATOR_ASSIGN;
            case ':':           return T_OPERATOR_COLON;
            case '?':           return T_OPERATOR_QUESTION;
            case 'null': return T_NULL;
            case 'true': case 'false': return T_BOOLEAN;
            default:
                if (ctype_punct($value)) {
                    return T_OPERATOR;
                }
                if (in_array($value, KEYWORDS_OBJECT)) {
                    return T_OBJECT;
                } elseif (in_array($value, KEYWORDS_MODIFIER)) {
                    return T_MODIFIER;
                }
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
                $name = strtoupper("t_{$value}"); // !!
                if (defined(__namespace__ .'\\'. $name)) {
                    return $name; // @tmp // constant($name);
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
    public function __construct(Tokens $tokens, array $data)
    {
        $this->tokens = $tokens;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
    public function __get($name)
    {
        switch ($name) {
            case 'prev': return $this->prev();
            case 'next': return $this->next();
        }
    }
    public function hasPrev()
    {
        return $this->tokens->has($this->index - 1);
    }
    public function hasNext()
    {
        return $this->tokens->has($this->index + 1);
    }
    public function prev()
    {
        return $this->tokens->get($this->index - 1);
    }
    public function next()
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
            if (is_object($token)) {
                $token = $token->toArray();
            }
            $this->add($token);
        }
    }
    public function __get($name)
    {
        switch ($name) {
            case 'prev': return $this->prev();
            case 'next': return $this->next();
            case 'first': return $this->first();
            case 'last': return $this->last();
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
    public function hasPrev()
    {
        return $this->has($this->tokensIndexPointer - 1);
    }
    public function hasNext()
    {
        return $this->has($this->tokensIndexPointer);
    }

    public function get(int $i)
    {
        return $this->tokens[$i] ?? null;
    }
    public function prev()
    {
        return $this->get($this->tokensIndexPointer--);
    }
    public function next()
    {
        return $this->get($this->tokensIndexPointer++);
    }

    public function first()
    {
        return $this->get(0);
    }
    public function last()
    {
        return $this->get($this->tokensIndex - 1);
    }

    public function tokensIndex()
    {
        return $this->tokensIndex;
    }
    public function tokensIndexPointer()
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
        return array_filter($this->tokens); // array_filter?
    }
}
