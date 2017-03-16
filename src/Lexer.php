<?php
declare(strict_types=1); namespace Poffee;

const RE_ID = '[a-z_]\w*';
const RE_STRING = '[\'"].*[\'"]';
const RE_OPR = '[\<\>\!\=\*/\+\-%\|\^\~]';
const RE_FUNCTION_ARGS = ' ,\w\.\=\(\)\[\]\'\"';

function isValidExpression($input) {
    pre($input);
    $pattern[] = sprintf('(?<NOT>(\!)(\w+)|(not)\s+(\w+))');
    $pattern[] = sprintf('(?<OPR>(\w+)\s*(%s+)\s*(\w+)?)', RE_OPR);
    $pattern[] = sprintf('(?<CALLABLE_CALL>(?:(new)\s+)?([a-z_]\w*)\s*(\()(.*)(\)))');
    $pattern[] = sprintf('(?<METHOD_CALL>(?:(new)\s+)?([a-z_][%s]*)\s*(\.)([a-z_]\w*)\s*(\()(.*)(\)))', RE_FUNCTION_ARGS);
    $pattern[] = sprintf('(?<PROPERTY>(?:[a-z_]\w*)\.(?:[a-z_]\w*)(?:\..+)?)');
    $pattern[] = sprintf('(?<ARRAY>(?:(\[)(.*)(\])|([a-z_]\w*)(\[)(.+)(\])))');
    $pattern[] = sprintf('(?<SCOPE>(\()(.*)(\)))');
    $pattern = '~^(?:'. join('|', $pattern) .')$~ix';
    preg_match($pattern, $input, $matches);
    // pre($matches);
    $return = [];
    foreach ($matches as $key => $value) {
        if ($key !== 0 && $value !== '') {
            is_string($key) ? $return['type'] = $key : $return[] = $value;
        }
    }
    // var_dump($return); // var_dump
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
        if (!isValidColon($input)) {throw new \Exception(sprintf('Sytax error in %s line %s, expecting ":" for the end of line!', $file, $line));}
        $lexer = new self(self::$indent);
        $lexer->file = $file;
        $lexer->line = $line;
        $pattern = '~
              (?:(//)\s*(.+))                           # comment
            | (?:(declare)\s+[\'"](.+)[\'"])            # declare
            | (?:(module)\s+([a-z_]\w*)\s*(:))          # module (namespace)
            | (?:(use|const)\s+(.+))                    # use, const
            | (?:                                       # objects
                (?:(abstract|final)\s*)?                # descriptor
                (object|interface|trait)\s+([a-z_]\w*)  # class, interface, trait
                (?:\s*(>)\s+([a-z_]\w*))?               # extends
                (?:\s*(>>)\s+([a-z_](?:[\w,\s]*)))?     # implements
              (:))
            #| (?:(^\s+)                                # property
            #    (public|private|protected)\s+ beklesin
            #    (?:(static))
            #  )
            | (?:(^\s+)?([a-z_]\w*)\s*(=)\s*(.+))       # assign
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
            $value = is_array($match) ? $match[0] : $match;
            if ($value === self::$space) continue; // ?
            $token  = [];
            $indent = null;
            $length = strlen($value);
            if ($value !== self::$eol && ctype_space($value)) {
                if ($length < self::$indentLength or $length % self::$indentLength !== 0) {
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
                if ($token->type === T_NONE) {
                    $type = null;
                    switch ($prevType) {
                        case T_COMMENT_OPR:     $type = T_COMMENT_CONTENT; break;
                        case T_DECLARE:         $type = T_DECLARE_EXPR;    break;
                        case T_MODULE:          $type = T_MODULE_EXPR;  break;
                        case T_USE:             $type = T_USE_EXPR;        break;
                        case T_CONST:           $type = T_CONST_EXPR;      break;
                        case T_OBJECT:          $type = T_OBJECT_ID; break;
                        case T_OBJECT_ID:
                            if ($token->value === C_EXTENDS) {
                                $type = T_EXTENDS_MODF;
                                $nextType = T_OBJECT_ID;
                            }
                            elseif ($token->value === C_IMPLEMENTS) {
                                $type = T_IMPLEMENTS_MODF;
                                $nextType = T_OBJECT_ID;
                                // prd($token);
                            }
                            // else {
                            //     // throw new \Exception("..");
                            // }
                            break;
                    }
                    if (!$type) {
                        pre($token->value, $prevType, $nextType);
                    }
                    if (!$type) {
                        if ($nextType === T_ASSIGN_OPR) {
                            $type = T_VAR_ID;
                        } elseif ($expression = isValidExpression($token->value)) {
                            $type = getTokenTypeFromConst($expression['type'].'_expr');
                            if ($type) {
                                // $token->children = $this->generateTokens(array_slice($expression, 2));
                            }
                        } elseif ($prevType === T_ASSIGN_OPR) {
                            $type = T_EXPR;
                        }
                    }

                    $token->type = $type;
                    if ($prev) $prev->type = $prevType;
                    if ($next) $next->type = $nextType;
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
            case '=':           return T_ASSIGN_OPR;
            case '.':           return T_DOT;
            case ':':           return T_COLON;
            case ',':           return T_COMMA;
            case '?':           return T_QUESTION;
            case '//':          return T_COMMENT_OPR;
            case '(':           return T_OPEN_PRNT;
            case ')':           return T_CLOSE_PRNT;
            case '[':           return T_OPEN_BRKT;
            case ']':           return T_CLOSE_BRKT;

            // bunlar icin getTokenTypeFromConst() kullan sonra
            case 'declare': return T_DECLARE;
            case 'module': return T_MODULE;
            // case 'object': case 'interface': case 'trait': return T_OBJECT;
            // case 'extends': return T_EXTENDS_MODF;
            // case 'implements': return T_IMPLEMENTS_MODF;
            case 'abstract': return T_ABSTRACT_MODF;
            case 'final': return T_FINAL_MODF;

            case 'static': case 'global': case 'public': case 'private': case 'protected': return T_MODF;
            case 'func': return T_FUNCTION;

            case 'null': return T_NULL;
            case 'true': case 'false': return T_BOOLEAN;
            case 'for': case 'foreach': case 'while': return T_LOOP;

            case 'die': case 'echo': case 'empty': case 'eval': case 'exit': case 'include': case 'include_once': case 'isset': case 'list': case 'print': case 'require': case 'require_once': case 'unset': case '__halt_compiler':
                return T_FUNCTION_ID;
            default:
                $fChar = $value[0]; $lChar = substr($value, -1);
                if ($fChar == '(' && $lChar == ')') {
                    // return T_EXPR; ?? // yukarda isValidExpression sorgusunu engelliyor
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
                if (preg_match(RE_OPR, $value)) {
                    return T_OPR;
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
    throw new \Exception("Undefined constant: '$name'"); // @debug

}
