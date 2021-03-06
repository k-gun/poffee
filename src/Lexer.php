<?php
declare(strict_types=1); namespace Poffee;

const RE_ID = '[a-z_]\w*';
const RE_STRING = '[\'"].*[\'"]';
const RE_OPR = '[\<\>\!\=\*/\+\-%\|\^\~]';
const RE_FUNCTION_ARGS = ' ,\w\.\=\(\)\[\]\'\"';

function isValidExpression($input) {
    // pre($input);
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
    protected static $eol = PHP_EOL, $space = ' ', $comment = '//', $indent = '    ', $indentLength = 4, $cache = [];
    protected $file, $line;

    public function __construct(string $indent = null)
    {
        if ($indent) {
            self::$indent = $indent;
            self::$indentLength = strlen($indent);
        }
    }

    public function scan($file, $line, $input, $inputs = null)
    {
        $this->file = $file;
        $this->line = $line;
        $pattern = '~(?<![\'"])(?:
              (?:(^\s+)?(//)([^\r\n]*))                        # comment
            | (?:(declare)\s+([\'"].+[\'"]))                   # declare
            | (?:(module)\s+([a-z_]\w*)\s*(:))                 # module (namespace)
            | (?:(use)\s+(.+))                                 # use
            | (?:(const)\s+([a-z_]\w*)\s*(=)\s*(.+))           # const
            | (?:                                              # objects
                (object)
                (?:\s+(abstract|final)\s*)?                    # descriptor
                (?:\s+(class|interface|trait)\s+([a-z_]\w*))   # class, interface, trait
                (?:\s*(>)\s+([a-z_]\w*))?                      # extends
                (?:\s*(>>)\s+([a-z_](?:[\w,\s]*)))?            # implements
              (:))                                             # colon
            | (?:(^\s+)                                        # const
                (?:(const)
                    (?:\s+(@|@@))?                             # private, protected
                       \s+([a-z_]\w*)                          # name
                    (?:\s*(=)\s*(.+))                          # value
                )
              )
            | (?:(^\s+)                                        # property
                (?:(var)
                    (?:\s+(@|@@))?                             # private, protected
                    (?:\s+(static))?                           # static
                       \s+([a-z_]\w*)                          # name
                    (?:\s*(=)\s*(.+))?                         # value
                )
              )
            | (?:(^\s+)                                        # method
                (?:(fun)
                    (?:\s+(@|@@))?                             # private, protected
                    (?:\s*(final|abstract|static)?             # final or abstract, static
                       \s*(static|final|abstract)?             # static, final or abstract
                    )?
                       \s+([a-z_]\w*)                          # name
                    (?:\s*(\(.*\)))                            # arguments
                )
                (:)                                            # colon
                (?:\s*([a-z_]\w*))?                            # return type
              )
            | (?:(^\s+)?                                       # function
                (?:([a-z_]\w*)\s*(=)\s*)?                      # anon name
                (?:(fun)
                   (?:\s+([a-z_]\w*))?                         # real name
                   (?:\s*(\(.*\)))                             # arguments
                   (:)                                         # colon
                   (?:\s*([a-z_]\w*))?                         # return type
                )
              )
            | (?:(^\s+)?(?:(if|elseif|switch|case)              # if, elseif, switch, case
                \s+(.+)|(else))                                 # else
                \s*(:)                                          # colon
              )
            | (?:(^\s+)?(for)\s+(?:(var)\s+)?(?:
                | ([a-z_]\w*)\s+(in)\s+(.+)                     # for key in ..
                | ([a-z_]\w*)\s*(,)\s*([a-z_]\w*)\s+(in)\s+(.+) # for key, value in ..
                | (.*)\s*(;)\s*(.*)\s*(;)\s*(.*)                # for i = 0; i < 10; i++:
               )
               \s*(:))                                          # colon
            | (?:(^\s+)?(require|include(?:_once)?)\s*(.*))     # require, include ..
            | (?:(^\s+)?(return)\s*(.*))                        # return
            | (?:(^\s+)?(?:(var)\s+)?([a-z_]\w*)\s*(=)\s*(.+))  # assign
            | (?:(^\s+)?([a-z_]\w*)\s*([\^\~<>!=%.&*/+-]=(?:\s*)(@)?)\s*(.+)) # operators with assign
            | (?:(^\s+)?(.+))                                   # all others
        )~ix';
        $matches = $this->getMatches($pattern, $input);
        pre(json_encode($matches));
        return $this->generateTokens($matches);
    }

    public function generateTokens(array $matches, $debug = false)
    {
        $tokens = [];
        foreach ($matches as $i => $match) {
            // if ($debug) pre($match);
            // $value = is_array($match) ? $match[0] : $match;
            $value = $match[0];
            if ($value === self::$space) continue; // ?
            $type = null;
            $length = strlen($value);
            if ($value !== self::$eol) {
                if (ctype_space($value)) {
                    if ($length < self::$indentLength or $length % self::$indentLength !== 0) {
                        throw new \Exception(sprintf('Indent error in %s line %s!', $this->file, $this->line));
                    }
                    $type = T_INDENT;
                } elseif (($tokens[$i - 1]['type'] ?? '') === T_COMMENT) { // skip comments
                    $type = T_COMMENT_CONTENT;
                }
            }
            if (!$type) $type = $this->getType($value);
            // $start = $match[1]; $end = $start + $length;
            $token = ['value' => $value, 'type' => $type, // 'line' => $this->line,
                // 'length' => $length, 'start' => $start, 'end' => $end, // 'children' => null
            ];
            // if ($type === T_INDENT) $token['size'] = $length / self::$indentLength;
            $tokens[] = $token;
        }

        $tokens = new TokenCollection($tokens);
        return $tokens;

        /***********************************************************************************
         ***********************************************************************************
         ***********************************************************************************/

        /* if (!$tokens->isEmpty()) {
            while ($token = $tokens->next()) {
                $prev = $token->prev(); $next = $token->next();
                switch ($token->type) {
                    case T_COMMENT:
                        $next->type = T_COMMENT_CONTENT;
                        break;
                    case T_DECLARE:
                        $next->type = T_EXPR;
                        break;
                    case T_MODULE:
                        $next->type = T_MODULE_ID;
                        break;
                    case T_USE:
                        $next->type = T_EXPR;
                        break;
                    case T_OBJECT:
                        while (($t = $tokens->next()) && $t->value !== C_COLON) {
                            if ($t->type) continue;
                            if ($t->value === C_EXTENDS) {
                                $t->type = T_EXTENDS;
                            } elseif ($t->value === C_IMPLEMENTS) {
                                $t->type = T_IMPLEMENTS;
                            } else {
                                $t->type = T_OBJECT_ID;
                            }
                        }
                        break;
                    case T_CONST:
                        while (($t = $tokens->next()) && $t->value !== C_ASSIGN) {
                            if ($t->type) continue;
                            if ($t->value === C_PRIVATE) {
                                $t->type = T_PRIVATE;
                            } elseif ($t->value === C_PROTECTED) {
                                $t->type = T_PROTECTED;
                            } else {
                                $t->type = T_CONST_ID;
                            }
                        }
                        break;
                    case T_VAR:
                        while (($t = $tokens->next()) && $t->value !== C_EOL) {
                            if ($t->type) continue;
                            if ($t->value === C_PRIVATE) {
                                $t->type = T_PRIVATE;
                            } elseif ($t->value === C_PROTECTED) {
                                $t->type = T_PROTECTED;
                            } elseif ($t->next->type === T_ASSIGN) {
                                $t->type = T_VAR_ID;
                            // } elseif ($t->prev->type === T_ASSIGN) {
                            //     if (isExpr($t->value)) {
                            //         $t->type = T_VAR_EXPR;
                            //         $t->children = $this->generateTokens(parseExpr($t->value));
                            //     }
                            }
                        }
                        break;
                    case T_FUNCTION:
                        while (($t = $tokens->next()) && $t->value !== C_EOL) {
                            if ($t->type) continue;
                            if ($t->value === C_PRIVATE) {
                                $t->type = T_PRIVATE;
                            } elseif ($t->value === C_PROTECTED) {
                                $t->type = T_PROTECTED;
                            } elseif ($t->value[0] === '(') {
                                $t->type = T_FUNCTION_ARGS_EXPR;
                                if ($t->prev->prev->type === T_ASSIGN) {
                                    $token->type = T_FUNCTION_ANON; // fix token type
                                } else {
                                    $t->prev->type = T_FUNCTION_ID;
                                }
                            } elseif (isId($t->value)) {
                                $t->type = T_FUNCTION_RET_TYPE;
                            }
                        }
                        break;
                    case T_FOR:
                    case T_IF:
                    case T_ELSEIF:
                    case T_IS:
                    case T_ISE:
                    case T_NOT:
                        if ($next && !$next->type) {
                            $next->type = T_EXPR;
                        }
                        break;
                    case T_RETURN:
                        if ($next && !$next->type) {
                            $next->type = T_RETURN_EXPR;
                        }
                        break;
                    case T_OPR:
                        if ($next && !$next->type) {
                            if ($token->value === C_INC || $token->value === C_DEC) {
                                $next->type = T_VAR_ID;
                            } elseif (isId($next->value)) {
                                if ($next->next && $next->next->type === T_PAREN_OPEN) {
                                    $next->type = T_FUNCTION_ID;
                                } else {
                                    $next->type = T_VAR_ID;
                                }
                            }
                        }
                    case T_ASSIGN:
                        if ($prev) {
                            if (isId($prev->value)) {
                                $prev->type = T_VAR_ID;
                            }
                        }
                        if ($next && !$next->type) {
                            if (isExpr($next->value)) {
                                $next->type = T_VAR_EXPR;
                            }
                        }
                        break;
                    // case T_VAR_EXPR:
                    //     if (isExpr($token->value)) {
                    //         $token->children = $this->generateTokens(parseExpr($token->value), true);
                    //     }
                        break;
                    default:
                        if (!$token->type) {
                            // pre($token->value);
                            if ($prev) {
                                // pre($prev);
                            } elseif ($next) {
                                // pre($token->value);
                                if ($next->type === T_ASSIGN) {
                                    $token->type = T_VAR_ID;
                                } elseif ($next->type === T_PAREN_OPEN && isId($token->value)) {
                                    $token->type = T_FUNCTION_ID;
                                } elseif (isId($token->value)) {
                                    $token->type = T_VAR_ID;
                                }
                            }
                        }
                }

                if ($token->type === T_VAR_EXPR) {
                    $token->children = $this->generateTokens(parseExpr($token->value));
                    // while (($t = $token->children->next())) {
                    //     pre($t->value);
                    // }
                }
                // if($next) pre($next->type,$next->value);
                // if no type error?
            }
        } */
        return $tokens;
    }

    public function getType($value)
    {
        $value = strval($value);
        switch ($value) {
            case self::$eol:    return T_EOL;
            case self::$space:  return T_SPACE;
            case self::$indent: return T_INDENT;
            case '=':           return T_ASSIGN;
            case '.':           return T_DOT;
            case ':':           return T_COLON;
            case ';':           return T_SEMICOLON;
            case ',':           return T_COMMA;
            case '?':           return T_QUESTION;
            case '//':          return T_COMMENT;
            case '(':           return T_PAREN_OPEN;
            case ')':           return T_PAREN_CLOSE;
            case '[':           return T_BRACK_OPEN;
            case ']':           return T_BRACK_CLOSE;

            // bunlar icin getTokenTypeFromConst() kullan sonra
            case 'declare': return T_DECLARE;
            case 'module': return T_MODULE;
            case 'use': return T_USE;
            case 'abstract': return T_ABSTRACT;
            case 'final': return T_FINAL;
            case 'object': return T_OBJECT;
            case 'class': return T_CLASS;
            case 'interface': return T_INTERFACE;
            case 'trait': return T_TRAIT;
            case 'const': return T_CONST;
            case 'var': return T_VAR;
            case 'fun': return T_FUNCTION;
            case 'this': return T_THIS;
            case 'return': return T_RETURN;
            case 'static': return T_STATIC;
            case 'global': return T_GLOBAL;
            case 'null': return T_NULL;
            case 'true': case 'false': return T_BOOL;
            case 'if': return T_IF; case 'else': return T_ELSE; case 'elseif': return T_ELSEIF;
            case 'switch': return T_SWITCH; case 'case': return T_CASE;
            case 'for': return T_FOR; case 'in': return T_IN;
            case 'break': return T_BREAK; case 'continue': return T_CONTINUE;
            case 'is': return T_IS; case 'ise': return T_ISE; case 'not': return T_NOT;
            case 'and': return T_AND; case 'or': return T_OR;
            case 'require': return T_REQUIRE; case 'require_once': return T_REQUIRE_ONCE;
            case 'include': return T_INCLUDE; case 'include_once': return T_INCLUDE_ONCE;
            case 'die': case 'echo': case 'empty': case 'eval': case 'exit':
            case 'isset': case 'list': case 'print': case 'unset': case '__halt_compiler': return T_FUNCTION_ID;

            default:
                if (isNumber($value)) {
                    return T_NUMBER;
                }
                if (isString($value)) {
                    return T_STRING;
                }
                if (isOpr($value)) {
                    return T_OPR;
                }
                // $fChr = $value[0]; $lChr = substr($value, -1);
                // burasi sikintili gibi, bakilacak
                // if ($fChr === '[' && $lChr === ']') {
                //     return T_ARRAY_EXPR;
                // }
                // if ($fChr === '(' && $lChr === ')') {
                //     return T_EXPR; ?? // yukarda isValidExpression sorgusunu engelliyor
                // }
        }
        return null;
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
