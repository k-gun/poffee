<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;
const T_COMMENT = 'T_COMMENT';

const T_OPERATOR = 'T_OPERATOR';
const T_ASSIGN_OPERATOR = 'T_ASSIGN_OPERATOR';
const T_DOT_OPERATOR = 'T_DOT_OPERATOR';
const T_COMMA_OPERATOR = 'T_COMMA_OPERATOR';
const T_COLON_OPERATOR = 'T_COLON_OPERATOR';
const T_QUESTION_OPERATOR = 'T_QUESTION_OPERATOR';
const T_COMMENT_OPERATOR = 'T_COMMENT_OPERATOR';

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
const T_LOOP = 'T_LOOP';
const T_PARENTHESES_BLOCK = 'T_PARENTHESES_BLOCK';
const T_OPEN_PARENTHESES = 'T_OPEN_PARENTHESES';
const T_CLOSE_PARENTHESES = 'T_CLOSE_PARENTHESES';
const T_BRACKET_BLOCK = 'T_BRACKET_BLOCK';
const T_OPEN_BRACKET = 'T_OPEN_BRACKET';
const T_CLOSE_BRACKET = 'T_CLOSE_BRACKET';

const T_IDENTIFIER = 'T_IDENTIFIER';
const T_VAR_IDENTIFIER = 'T_VAR_IDENTIFIER';
const T_FUNCTION_IDENTIFIER = 'T_FUNCTION_IDENTIFIER';
const T_OBJECT_IDENTIFIER = 'T_OBJECT_IDENTIFIER';
const T_PROPERTY_IDENTIFIER = 'T_PROPERTY_IDENTIFIER';
const T_METHOD_IDENTIFIER = 'T_METHOD_IDENTIFIER';

const T_EXPRESSION = 'T_EXPRESSION';
const T_NOT_EXPRESSION = 'T_NOT_EXPRESSION';
const T_OPERATOR_EXPRESSION = 'T_OPERATOR_EXPRESSION';
const T_TERNARY_1_EXPRESSION = 'T_TERNARY_1_EXPRESSION';
const T_TERNARY_2_EXPRESSION = 'T_TERNARY_2_EXPRESSION';
const T_TERNARY_3_EXPRESSION = 'T_TERNARY_3_EXPRESSION';
const T_CALLABLE_CALL_EXPRESSION = 'T_CALLABLE_CALL_EXPRESSION';
const T_METHOD_CALL_EXPRESSION = 'T_METHOD_CALL_EXPRESSION';
const T_PROPERTY_EXPRESSION = 'T_PROPERTY_EXPRESSION';
const T_ARRAY_EXPRESSION = 'T_ARRAY_EXPRESSION';

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

const RE_ID = '[a-z_]\w*';
const RE_STRING = '[\'"].*[\'"]';
const RE_OPERATOR = '[\<\>\!\=\*/\+\-%\|\^\~]';
const RE_FUNCTION_ARGS = ' ,\w\.\=\(\)\[\]\'\"';

function isValidExpression($input) {
    pre($input);
    $pattern[] = sprintf('(?<NOT>(\!)(\w+)|(not)\s+(\w+))');
    $pattern[] = sprintf('(?<OPERATOR>(\w+)\s*(%s+)\s*(\w+)?)', RE_OPERATOR);
    // $pattern[] = sprintf('(?<TERNARY_3>(?:\s*(\(.+\))\s*(\?)\s*(\(.+\))\s*(:)\s*(.+?)))');
    // $pattern[] = sprintf('(?<TERNARY_2>(?:\s*(.+?)\s*(\?)\s*(\(.+\))\s*(:)\s*(.+?)))');
    // $pattern[] = sprintf('(?<TERNARY_1>(?:\s*(.+?)\s*(\?)\s*(.+?)\s*(:)\s*(.+?)))');
    $pattern[] = sprintf('(?<CALLABLE_CALL>(?:(new)\s+)?([a-z_]\w*)\s*(\()(.*)(\)))');
    $pattern[] = sprintf('(?<METHOD_CALL>(?:(new)\s+)?([a-z_][%s]*)\s*(\.)([a-z_]\w*)\s*(\()(.*)(\)))', RE_FUNCTION_ARGS);
    $pattern[] = sprintf('(?<PROPERTY>(?:[a-z_]\w*)\.(?:[a-z_]\w*)(?:\..+)?)');
    $pattern[] = sprintf('(?<ARRAY>(\[)(.*)(\]))');
    $pattern[] = sprintf('(?<SCOPE>(\()(.*)(\)))');
    $pattern = '~^(?:'. join('|', $pattern) .')$~ix';
    preg_match($pattern, $input, $matches);
    pre($matches);
    $return = [];
    foreach ($matches as $key => $value) {
        if ($key !== 0 && $value !== '') {
            is_string($key) ? $return['type'] = $key : $return[] = $value;
        }
    }
    var_dump($return); // var_dump
    return !empty($return) ? $return : null;
}

class Lexer
{
    private static $eol = PHP_EOL,
        $space = ' ',
        $indent = '    ',
        $indentLength = 4,
        $cache = []
    ;

    public function __construct(string $indent = null)
    {
        if ($indent) {
            self::$indent = $indent;
            self::$indentLength = strlen($indent);
        }
    }

    public function scan($file, $line, $input)
    {
        $lexer = new self(self::$indent);
        $lexer->file = $file;
        $lexer->line = $line;
        $pattern = '~
              (?:(^\s+)?(//)\s*(.+))                    # comment
            | (?:(^\s+)?([a-z_]\w*)\s*(=)\s*(.+))   # assign
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

    public function generateTokens(array $matches, $a=null)
    {
        $tokens = [];
        foreach ($matches as $match) {
            $value = is_array($match) ? $match[0] : $match;
            if ($value == self::$space) continue; // ?
            $indent = null;
            $length = strlen($value);
            $token  = [];
            if ($value != self::$eol && ctype_space($value)) {
                if ($length < self::$indentLength || $length % self::$indentLength != 0) {
                    throw new \Exception(sprintf('Indent error in %s line %s!', $this->file, $this->line));
                }
                $type = T_INDENT;
                $token['size'] = $length; // / self::$indentLength;
            } else {
                $type = $this->getType($value);
            }
            // $start = $match[1]; $end = $start + $length;
            $token += ['value' => $value, 'type' => $type, 'line' => $this->line,
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
                    if ($prevType == T_COMMENT_OPERATOR) {
                        $token->type = T_COMMENT;
                    } elseif ($nextType == T_ASSIGN_OPERATOR) {
                        $token->type = T_VAR_IDENTIFIER;
                    } elseif ($expression = isValidExpression($token->value)) {
                        switch ($expression['type']) {
                            case 'NOT': $token->type = T_NOT_EXPRESSION; break;
                            case 'OPERATOR': $token->type = T_OPERATOR_EXPRESSION; break;
                            case 'TERNARY_1': $token->type = T_TERNARY_1_EXPRESSION; break;
                            case 'TERNARY_2': $token->type = T_TERNARY_2_EXPRESSION; break;
                            case 'TERNARY_3': $token->type = T_TERNARY_3_EXPRESSION; break;
                            case 'CALLABLE_CALL': $token->type = T_CALLABLE_CALL_EXPRESSION; break;
                            case 'METHOD_CALL': $token->type = T_METHOD_CALL_EXPRESSION; break;
                            case 'PROPERTY': $token->type = T_PROPERTY_EXPRESSION; break;
                            case 'ARRAY': $token->type = T_ARRAY_EXPRESSION; break;
                            default: $token->type = T_EXPRESSION;
                        }
                        if ($token->type != T_EXPRESSION) {
                            // $children = $this->generateTokens(array_slice($expression, 2), 1);
                            // $children = array_slice($expression, 2);
                            // pre($children);
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
            case '=':           return T_ASSIGN_OPERATOR;
            case '.':           return T_DOT_OPERATOR;
            case ':':           return T_COLON_OPERATOR;
            case ',':           return T_COMMA_OPERATOR;
            case '?':           return T_QUESTION_OPERATOR;
            case '//':          return T_COMMENT_OPERATOR;
            case '(':           return T_OPEN_PARENTHESES;
            case ')':           return T_CLOSE_PARENTHESES;
            case '[':           return T_OPEN_BRACKET;
            case ']':           return T_CLOSE_BRACKET;
            case 'null':
                return T_NULL;
            case 'true': case 'false':
                return T_BOOLEAN;
            case 'for': case 'foreach': case 'while':
                return T_LOOP;
            case 'class': case 'interface': case 'trait':
                return T_OBJECT;
            case 'func': case 'function':
                return T_FUNCTION;
            case 'new': case 'abstract': case 'final': case 'static': case 'public': case 'private': case 'protected': case 'extends': case 'implements':
                return T_MODIFIER;
            case 'declare': case 'die': case 'echo': case 'empty': case 'eval': case 'exit': case 'include': case 'include_once': case 'isset': case 'list': case 'print': case 'require': case 'require_once': case 'unset': case '__halt_compiler':
                return T_FUNCTION_IDENTIFIER;
            default:
                $fChar = $value[0]; $lChar = substr($value, -1);
                if ($fChar == '(' && $lChar == ')') {
                    // return T_EXPRESSION; ?? // yukarda isValidExpression sorgusunu engelliyor
                }
                if ($fChar == "'" && $lChar == "'") {
                    return T_STRING;
                }
                if ($fChar == '"' && $lChar == '"') {
                    return T_STRING;
                }
                if (is_numeric($value)) {
                    return T_NUMBER;
                }
                if (preg_match(RE_OPERATOR, $value)) {
                    return T_OPERATOR;
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
