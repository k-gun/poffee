<?php
declare(strict_types=1); namespace Poffee;

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
    $pattern[] = sprintf('(?<CALLABLE_CALL>(?:(new)\s+)?([a-z_]\w*)\s*(\()(.*)(\)))');
    $pattern[] = sprintf('(?<METHOD_CALL>(?:(new)\s+)?([a-z_][%s]*)\s*(\.)([a-z_]\w*)\s*(\()(.*)(\)))', RE_FUNCTION_ARGS);
    $pattern[] = sprintf('(?<PROPERTY>(?:[a-z_]\w*)\.(?:[a-z_]\w*)(?:\..+)?)');
    $pattern[] = sprintf('(?<ARRAY>(?:(\[)(.*)(\])|([a-z_]\w*)(\[)(.+)(\])))');
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

class Lexer extends LexerBase
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
            | (?:(declare)\s+[\'"](.+)[\'"])          # declare
            | (?:(namespace)\s+(.+))          # namespace
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
                    } elseif ($prevType == T_DECLARE) {
                        $token->type = T_DECLARE_EXPRESSION;
                    } elseif ($prevType == T_NAMESPACE) {
                        $token->type = T_NAMESPACE_EXPRESSION;
                    } elseif ($nextType == T_ASSIGN_OPERATOR) {
                        $token->type = T_VAR_IDENTIFIER;
                    } elseif ($expression = isValidExpression($token->value)) {
                        $token->type = getTokenTypeFromConst($expression['type'].'_expression');
                        if ($token->type) {
                            // $token->children = $this->generateTokens(array_slice($expression, 2), 1);
                        }
                    } elseif ($prevType == T_ASSIGN_OPERATOR) {
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
            case 'declare': return T_DECLARE;
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

function getTokenTypeFromConst($name) {
    // $name = sprintf('%s\\T_%s', __namespace__, strtoupper($name));
    // if (defined($name)) {
    //     return $name; // @tmp // constant($name);
    // }
    // @tmp
    $name = strtoupper("t_{$name}");
    if (defined(__namespace__ .'\\'. $name)) {
        return $name; // @tmp // constant($name);
    }
}
