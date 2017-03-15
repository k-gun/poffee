<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_OPERATOR = 'T_OPERATOR';
const T_ASSIGN_OPERATOR = 'T_ASSIGN_OPERATOR';
const T_COLON_OPERATOR = 'T_COLON_OPERATOR';
const T_COMMA_OPERATOR = 'T_COMMA_OPERATOR';
const T_QUESTION_OPERATOR = 'T_QUESTION_OPERATOR';

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
const T_PARENTHESES_BLOCK = 'T_PARENTHESES_BLOCK';
const T_OPEN_PARENTHESES = 'T_OPEN_PARENTHESES';
const T_CLOSE_PARENTHESES = 'T_CLOSE_PARENTHESES';

const T_IDENTIFIER = 'T_IDENTIFIER';
const T_VAR_IDENTIFIER = 'T_VAR_IDENTIFIER';
const T_FUNCTION_IDENTIFIER = 'T_FUNCTION_IDENTIFIER';
const T_OBJECT_IDENTIFIER = 'T_OBJECT_IDENTIFIER';
const T_PROPERTY_IDENTIFIER = 'T_PROPERTY_IDENTIFIER';
const T_METHOD_IDENTIFIER = 'T_METHOD_IDENTIFIER';

const T_EXPRESSION = 'T_EXPRESSION';

const T_NULL = 'T_NULL';
const T_STRING = 'T_STRING';
const T_NUMBER = 'T_NUMBER';
const T_BOOLEAN = 'T_BOOLEAN';

const T_FUNCTION = 'T_FUNCTION';
const T_FUNCTION_CALL = 'T_FUNCTION_CALL';

// cache these?
function isValidIdentifier($input) {
    return !!preg_match('~^(?:[a-z_]\w*)$~i', $input);
}
function isValidExpression($input) {
    return !!preg_match('~^(
         (?:(\()?(?:\w+)\s*(?=[\<\>\!\=\*/\+\-%\|\^\~]+)\s*(.+)(\))?)   # eg: a < 1
        |(?:(\()?(?:\w+)\s*(?=\?)(.+)\s*(?=:)\s*(.+)(\))?)              # eg: a ? a : 1
        |(?:(\()?(?:\w+)\s*(?=\?\?)\s*(.+)(\))?)                        # eg: a ?? 1
        |(?:(\()?(?:\w+)\s*(?=\?:)\s*(.+)(\))?)                         # eg: a ?: 1
        |(?:(\()?(?:\w+)\s*(?=(or|and|ise?|not))\s*(.+)(\))?)           # eg: a or 1
        |(?:(\()?([a-z_]\w*)\s*(\()\s*(.+)\s*(\)))(\)?)                 # eg: foo(a), foo(a ...)
        |(?:(\()\s*(.+)\s*(\)))                                         # eg: (a), (a ...)
    )$~ix', trim($input));
}

class Lexer
{
    private static $eol = PHP_EOL;
    private static $space = ' ';
    private static $indent = '    ';
    private static $indentLength = 4;
    private static $cache = [];

    public function __construct(string $indent = null)
    {
        if ($indent) {
            self::$indent = $indent;
            self::$indentLength = strlen($indent);
        }
    }

    public function doScan($line, $input)
    {
        $lexer = new self(self::$indent);
        $lexer->line = $line;
        $pattern = '~
             (?:(\s+)?//\s*([^\r\n]+))                    # comment
            |(?:(\s+)?(use)\s*([^\r\n]+))                     # use
            |(?:(\s+)?(const)\s+([a-z_]\w*)\s*(=)\s*(.+))   # const
            |(?:(\s+)?(abstract|final|static)?\s*(class)\s+(\w+)\s*
                (?:(extends)\s+(\w+)\s*)?(?:(implements)\s+(\w+)\s*)?(:)) # class
            |(?:(\s+)?(var|public|private|protected)\s+(\w+)(?:\s*(=)\s*([^\s]+))?) # function, property
            |(?:(\s+)?(func(?:tion)?)\s+([a-z_]\w*)\s*\((.*)\)(:)) # function
            |(?:(\s+)?(return)\s+(.+))                          # return
            |(?:(\s+)?(if|else|else\s*if)\s+(.+)(:))         # condition
            |(?:(\s+)?([a-z_]\w*)\s*(=)\s*([^\s]+))   # assign
            |(?:(\s+)?([^\s]+)\s*([\<\>\!\=\*/\+\-%\|\^\~]+)\s*(.+)) # operators
            #|(?:(.+))                          # any
        ~ix';
        $matches = $lexer->getMatches($pattern, $input);
        pre($matches);
        return $lexer->generateTokens($matches);
    }
    public function scanFunctionExpression($line, $input)
    {
        $lexer = new self(self::$indent);
        $lexer->line = $line;
        $pattern = '~(?:
            (\()? # open parentheses
                \s*((\?)?[a-z_]\w*)?             # typehint
                \s*(&)?                          # reference
                \s*([a-z_]\w*)                   # variable name
                \s*(?:(=)\s*(\w+|[\'"].*[\'"]))? # variable default value
            (\))? # close parentheses
        )~ix';
        $matches = $lexer->getMatches($pattern, $input);
        pre($matches);
        return $lexer->generateTokens($matches);
    }

    public function generateTokens(array $matches)
    {
        $tokens = [];
        foreach ($matches as $match) {
            $value = $match[0];
            if ($value == self::$space) continue; // ?
            $length = strlen($value);
            if (ctype_space($value) && $length >= self::$indentLength && $length % self::$indentLength == 0) {
                $type = T_INDENT;
            } else {
                $type = $this->getType($value);
            }
            $indent = -1;
            // if ($type != T_INDENT) {
            //     while ($indent < $length && $value[++$indent] == ' ') {}
            // }
            $start = $match[1]; $end = $start + $length;
            $token = ['value' => $value, 'type' => $type, 'line' => $this->line, //'indent' => $indent,
                // 'length' => $length, 'start' => $start, 'end' => $end, 'children' => null
            ];
            $tokens[] = $token;
        }

        $tokens = new Tokens($tokens);
        if (!$tokens->isEmpty()) {
            while ($token = $tokens->next()) {
                $prev = $token->prev(); $prevType = $prev ? $prev->type : null;
                $next = $token->next(); $nextType = $next ? $next->type : null;
                if ($token->type == T_NONE) {
                    if ($nextType == T_OPERATOR || $nextType == T_ASSIGN_OPERATOR) {
                        $token->type = T_VAR_IDENTIFIER;
                    } elseif (isValidExpression($token->value)) {
                        $token->type = T_EXPRESSION;
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
            case '=':           return T_ASSIGN_OPERATOR;
            case ':':           return T_COLON_OPERATOR;
            case ',':           return T_COMMA_OPERATOR;
            case '?':           return T_QUESTION_OPERATOR;
            case '(':           return T_OPEN_PARENTHESES;
            case ')':           return T_CLOSE_PARENTHESES;
            case 'null': return T_NULL;
            case 'true': case 'false': return T_BOOLEAN;
            case 'class': case 'interface': case 'trait': return T_OBJECT;
            case 'func': case 'function': return T_FUNCTION;
            case 'abstract': case 'final': case 'static': case 'public': case 'private': case 'protected': case 'extends': case 'implements': T_MODIFIER;
            default:
                if (ctype_punct($value)) {
                    return T_OPERATOR;
                }

                $fChar = $value[0]; $lChar = substr($value, -1);
                if ($fChar == '(' && $lChar == ')') {
                    return T_EXPRESSION;
                } elseif ($fChar == "'" && $lChar == "'") {
                    return T_STRING;
                } elseif ($fChar == '"' && $lChar == '"') {
                    return T_STRING;
                } elseif (is_numeric($value)) {
                    return T_NUMBER;
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
