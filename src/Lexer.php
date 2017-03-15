<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_OPERATOR = 'T_OPERATOR';
const T_OPERATOR_ASSIGN = 'T_OPERATOR_ASSIGN';
const T_OPERATOR_COLON = 'T_OPERATOR_COLON';
const T_OPERATOR_COMMA = 'T_OPERATOR_COMMA';
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
const T_PARENTHESES_BLOCK = 'T_PARENTHESES_BLOCK';
const T_PARENTHESES_OPEN = 'T_PARENTHESES_OPEN';
const T_PARENTHESES_CLOSE = 'T_PARENTHESES_CLOSE';

const T_IDENTIFIER = 'T_IDENTIFIER';
const T_IDENTIFIER_VAR = 'T_IDENTIFIER_VAR';
const T_IDENTIFIER_FUNCTION = 'T_IDENTIFIER_FUNCTION';
const T_IDENTIFIER_OBJECT = 'T_IDENTIFIER_OBJECT';
const T_IDENTIFIER_PROPERTY = 'T_IDENTIFIER_PROPERTY';
const T_IDENTIFIER_METHOD = 'T_IDENTIFIER_METHOD';

const T_EXPRESSION = 'T_EXPRESSION';

const T_NULL = 'T_NULL';
const T_STRING = 'T_STRING';
const T_STRING_VAR = 'T_STRING_VAR';
const T_NUMBER = 'T_NUMBER';
const T_BOOLEAN = 'T_BOOLEAN';

const T_FUNCTION = 'T_FUNCTION';
const T_FUNCTION_CALL = 'T_FUNCTION_CALL';

// cache these!
function isValidIdentifier($s) {
    return !!preg_match('~^(?:[a-z_]\w*)$~i', $s);
}
function isValidExpression($s) {
    return !!preg_match('~^(
         (?:(\()?(?:[a-z_]\w*)\s*(?=[\<\>\!\=\*/\+\-%\|\^\~]+)\s*(.+)(\))?) # eg: a < 1
        |(?:(\()?(?:[a-z_]\w*)\s*(?=\?)(.+)\s*(?=:)\s*(.+)(\))?)            # eg: a ? a : 1
        |(?:(\()?(?:[a-z_]\w*)\s*(?=\?\?)\s*(.+)(\))?)                      # eg: a ?? 1
        |(?:(\()?(?:[a-z_]\w*)\s*(?=\?:)\s*(.+)(\))?)                       # eg: a ?: 1
        |(?:(\()\s*(.+)\s*(\)))                                             # eg: (a), (a ...)
    )$~ix', trim($s));
}
// prd("\x7f-\xff");
// prd(isValidExpression('(a) '));

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
        $lexer = new self(self::$indent);
        $lexer->line = $line;
        $pattern = '~
             (?:(\s+)?//\s*([^\r\n]+))                    # comment
            |(?:(\s+)?(use)\s*([^\r\n]+))                     # use
            |(?:(\s+)?(const)\s+([a-z_]\w*)\s*(=)\s*(.+))   # const
            |(?:(\s+)?(abstract|final|static)?\s*(class)\s+(\w+)\s*
                (?:(extends)\s+(\w+)\s*)?(?:(implements)\s+(\w+)\s*)?(:)) # class
            #|(?:(\s+)?(var|public|private|protected)\s+(\w+)(?:\s*(=)\s*([^\s]+))?) # function, property
            |(?:(\s+)?(func(?:tion)?)\s+([a-z_]\w*)\s*\((.+)\)(:)) # function
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
            $start = $match[1]; $end = $start + $length;
            $token = ['value' => $value, 'type' => $type, 'line' => $this->line,
                // 'length' => [$length, $start, $end],
                // 'length' => $length, 'start' => $start, 'end' => $end, 'children' => null
            ];
            $tokens[] = $token;
        }

        $tokens = new Tokens($tokens);
        if (!$tokens->isEmpty()) {
            while ($token = $tokens->next()) {
                $prev = $token->prev(); $next = $token->next();
                // at first
                if ($token->type == T_NONE) {
                    if (isValidExpression($token->value)) {
                        $token->type = T_EXPRESSION;
                    }
                }

                if ($token->type == T_EXPRESSION) {
                    $children = null;
                    if ($prev->type == T_IDENTIFIER_FUNCTION) {
                        $children = $this->scanFunctionExpression($token->line, $token->value);
                    } else {
                        // $children = $this->scanExpression($token->line, $token->value);
                    }
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
                }

                if ($prev) {
                    if ($token->value == 'extends') {
                        $prev->type = T_IDENTIFIER_OBJECT;
                    }
                    if ($prev->type == T_NONE && ($token->type == T_OPERATOR || $token->type == T_OPERATOR_ASSIGN)) {
                        $prev->type = T_IDENTIFIER_VAR;
                    }
                }
                if ($next) {
                    if ($token->value == 'extends' || $token->value == 'implements') {
                        $next->type = T_IDENTIFIER_OBJECT;
                    }
                    if ($next->type == T_NONE) {
                        if ($token->type == T_FUNCTION) {
                            $next->type = T_IDENTIFIER_FUNCTION;
                        } else {
                            $next->type = T_EXPRESSION;
                        }
                    }
                }

                // at last
                if ($token->type == T_NONE) {
                    if ($prev && $prev->type == T_OBJECT) {
                        $token->type = T_IDENTIFIER_OBJECT;
                    } elseif (isValidIdentifier($token->value)) {
                        $token->type = T_IDENTIFIER_VAR;
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
            case ',':           return T_OPERATOR_COMMA;
            case '?':           return T_OPERATOR_QUESTION;
            case '(':           return T_PARENTHESES_OPEN;
            case ')':           return T_PARENTHESES_CLOSE;
            case 'null': return T_NULL;
            case 'true': case 'false': return T_BOOLEAN;
            case 'class': case 'interface': case 'trait': return T_OBJECT;
            case 'func': case 'function': return T_FUNCTION;
            case 'abstract': case 'final': case 'static': case 'public': case 'private': case 'protected': case 'extends': case 'implements': T_MODIFIER;
            default:
                if (ctype_punct($value)) {
                    return T_OPERATOR;
                }
                $fChar = substr($value, 0, 1); $lChar = substr($value, -1);
                if ($fChar == "'" && $lChar == "'") {
                    return T_STRING;
                } elseif ($fChar == '"' && $lChar == '"') {
                    return T_STRING_VAR;
                } elseif (is_numeric($value)) {
                    return T_NUMBER;
                } elseif ($fChar == '(' && $lChar == ')') {
                    return T_EXPRESSION;
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
