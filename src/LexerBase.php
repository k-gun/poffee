<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_DECLARE = 'T_DECLARE';
const T_MODULE = 'T_MODULE';
const T_USE = 'T_USE';
const T_CLASS = 'T_CLASS';
const T_INTERFACE = 'T_INTERFACE';
const T_TRAIT = 'T_TRAIT';

const T_OPR = 'T_OPR';
const T_ASSIGN = 'T_ASSIGN';
const T_COMMENT = 'T_COMMENT', T_COMMENT_CONTENT = 'T_COMMENT_CONTENT';

const T_DOT = 'T_DOT';
const T_COMMA = 'T_COMMA';
const T_COLON = 'T_COLON';
const T_QUESTION = 'T_QUESTION';

const T_OBJECT = 'T_OBJECT';
const T_FUN = 'T_FUN';
const T_FUN_ANON = 'T_FUN_ANON';
const T_CONST = 'T_CONST';

const T_EXTENDS = 'T_EXTENDS';
const T_IMPLEMENTS = 'T_IMPLEMENTS';
const T_ABSTRACT = 'T_ABSTRACT';

const T_FINAL = 'T_FINAL';
const T_STATIC = 'T_STATIC';
const T_PUBLIC = 'T_PUBLIC';
const T_PRIVATE = 'T_PRIVATE';
const T_PROTECTED = 'T_PROTECTED';
const T_VAR = 'T_VAR';
const T_RETURN = 'T_RETURN';
const T_IF = 'T_IF';
const T_ELSE = 'T_ELSE';
const T_ELSEIF = 'T_ELSEIF';
const T_FOR = 'T_FOR';
const T_BREAK = 'T_BREAK', T_CONTINUE = 'T_CONTINUE';
const T_IS = 'T_IS';
const T_ISE = 'T_ISE';
const T_NOT = 'T_NOT';
const T_AND = 'T_AND';
const T_OR = 'T_OR';
const T_IN = 'T_IN';

const T_PRNT_BLOCK = 'T_PRNT_BLOCK';
const T_OPEN_PRNT = 'T_OPEN_PRNT';
const T_CLOSE_PRNT = 'T_CLOSE_PRNT';
const T_BRKT_BLOCK = 'T_BRKT_BLOCK';
const T_OPEN_BRKT = 'T_OPEN_BRKT';
const T_CLOSE_BRKT = 'T_CLOSE_BRKT';

const T_ID = 'T_ID';
const T_VAR_ID = 'T_VAR_ID';
const T_FUN_ID = 'T_FUN_ID';
const T_CONST_ID = 'T_CONST_ID';
const T_OBJECT_ID = 'T_OBJECT_ID';
const T_METHOD_ID = 'T_METHOD_ID';
const T_MODULE_ID = 'T_MODULE_ID';

const T_EXPR = 'T_EXPR';
const T_NOT_EXPR = 'T_NOT_EXPR';
const T_OPR_EXPR = 'T_OPR_EXPR';
const T_CALLABLE_CALL_EXPR = 'T_CALLABLE_CALL_EXPR';
const T_METHOD_CALL_EXPR = 'T_METHOD_CALL_EXPR';
const T_PROPERTY_EXPR = 'T_PROPERTY_EXPR';
const T_ARRAY_EXPR = 'T_ARRAY_EXPR';
const T_FUN_ARGS_EXPR = 'T_FUN_ARGS_EXPR';
const T_RETURN_EXPR = 'T_RETURN_EXPR';
const T_VAR_EXPR = 'T_VAR_EXPR';

const T_NULL = 'T_NULL';
const T_STRING = 'T_STRING';
const T_NUMBER = 'T_NUMBER';
const T_BOOL = 'T_BOOL';
const T_ARRAY = 'T_ARRAY';
const T_THIS = 'T_THIS';

// const T_FUN_CALL = 'T_FUN_CALL';
const T_FUN_RET_TYPE = 'T_FUN_RET_TYPE';

const T_PHP_TAG_OPEN = 'T_PHP_TAG_OPEN', T_PHP_TAG_CLOSE = 'T_PHP_TAG_CLOSE';

const T_REQUIRE = 'T_REQUIRE', T_REQUIRE_ONCE = 'T_REQUIRE_ONCE';
const T_INCLUDE = 'T_INCLUDE', T_INCLUDE_ONCE = 'T_INCLUDE_ONCE';

const C_EOL = PHP_EOL;
const C_ASSIGN = '=';
const C_COLON = ':';
const C_EXTENDS = '>';
const C_IMPLEMENTS = '>>';
const C_STATIC = 's';
const C_PRIVATE = '@';
const C_PROTECTED = '@@';
const C_INC = '++', C_DEC = '--';
const C_PHP_OPEN = '<?php';
const C_PHP_CLOSE = '?>';

const KEYWORDS = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
    'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default',
    'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif',
    'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach',
    'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof',
    'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
    'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
    'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield',
    'module', 'fun', 'in',
];

abstract class LexerBase
{
    function toAst(Tokens $tokens) { //return $tokens->toArray();
        $tokens = $this->setAssignIds($tokens);
        // $tokens = $this->setFunIds($tokens); burdayim
        $array = [];
        foreach ($tokens as $i => $token) {
            $array[$i] = $token->toArray(true);
            unset($token->tokens);
            if (!$token->type && isExpr($token->value)) {
                $token->type = T_EXPR;
                $token->children = $this->generateTokens(parseExpr($token->value));
                if ($token->children) {
                    $array[$i]['children'] = $this->toAst($token->children);
                    // $array = array_merge($array, $this->toAst($token->children));
                    // unset($token->children);
                }
            }
            // skip expressions, cos all should be parsed above already
            if ($token->type !== T_EXPR) {
                $array = $token->toArray(true);
            }
        }
        return $array;
    }

    function setAssignIds(Tokens $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_CONST) {
                $token->next->type = T_CONST_ID;
            } elseif ($token->type === T_VAR) {
                $token->next->type = T_VAR_ID;
            } elseif ($token->type === T_ASSIGN && !$token->prev->type) {
                $token->prev->type = T_VAR_ID;
            }
            if ($tokens->children) {
                $token->children = $this->setAssignIds($token->children);
            }
        }
        return $tokens;
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
    public function remove()
    {
        $this->tokens->removeAt($this->index);
    }
    public function toArray(bool $clear = false)
    {
        $array = get_object_vars($this);
        if ($clear) {
            unset($array['tokens']);
        }
        return $array;
    }
}

class Tokens implements \IteratorAggregate
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

    public function remove(Token $token)
    {
        $i = 0;
        while (isset($this->tokens[$i])) {
            if ($this->tokens[$i] === $token) {
                unset($this->tokens[$i]);
                break;
            }
            $i++;
        }
    }
    public function removeAt(int $i)
    {
        unset($this->tokens[$i]);
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
    public function toArray(bool $clear = false)
    {
        $array = [];
        foreach ($this->tokens as $token) {
            $array[] = $token->toArray($clear);
        }
        return $array;
    }
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->tokens);
    }
}

// cache these? sil!
function isValidColon($input) {
    // bunlari galiba tokenize den sonraya alicaz
    // return $input && $input !== PHP_EOL &&
    //     preg_match('~^(?:\s+)?(?:module|abstract|final|class|func|if|else|elseif|for)~', $input)
    //         ? (C_COLON === substr(chop($input), -1)) : true;
    if ($input && $input !== C_EOL) {
        $input = chop($input);
        // check descriptors
        if (preg_match('~^(?:\s+)?(?:module|abstract|final|class|fun|if|else|elseif|for).*(:)$~', $input)) {
            return true;
        }
        // check functions with return types
        if (preg_match('~^(?:\s+)?(?:fun)\s+(?:.+)\s*(?::)\s*([a-z_]\w*)$~', $input, $matches)) {
            return isId($matches[1]) && !isKeyword($matches[1]);
        }
    }
    return true;
}
function isValidColonBody($input, array $inputArray, int $line) {
    if (!isset($inputArray[$line], $inputArray[$line - 1])) {
        return true;
    }
    if (C_COLON === substr(chop($input), -1)) {
        $currLine = $input; $nextLine = $inputArray[$line];
        $currIndent = strlen(preg_replace('~^(\s*).*~', '\\1', $currLine)) - 1;
        $nextIndent = strlen(preg_replace('~^(\s*).*~', '\\1', $nextLine)) - 1;
        return $nextIndent > $currIndent && ('' !== trim($nextLine))
            && '    ' === substr($nextLine, 0, 4); // bunu degistir sonra clas icine alinca fn'i, self::$indent'i kullan
    }
    return true;
}

function isId($input) { return preg_match('~^(?:[a-z_]\w*)$~i', $input); }
function isKeyword($input) { return in_array($input, KEYWORDS); }
function isNumber($input) { return is_numeric($input); }
function isString($input) {
    $split = preg_split('~(?:([\'"])\s*([,+]))~', $input)[0] ?? null;
    if ($split) {
        $fChr = $split[0]; $lChr = substr($split, -1);
        return ($fChr === "'" && $lChr === "'") || ($fChr === '"' && $lChr === '"');
    }
}
function isValue($input) { return isNumber($input) || isString($input); }
function isExpr($input) {
    // if (preg_match('~^(?:[a-z_]\w*[+-]{2}|[+-]{2}[a-z_]\w*)$~', $input)) { // i++ and ++i
    //     return false;
    // }
    return !isId($input) && !isKeyword($input) && !isValue($input);
}
// var_dump(isExpr('(i++) + 1'));
// die;
function isOpr($input) { return preg_match('~^[\?\^\~|&<>:!=%.@*/+-]+$~', $input); }
function isLetterChr($chr) { return ($chr >= 'a' && $chr <= 'z') || ($chr >= 'A' && $chr <= 'Z'); }
function isNumberChr($chr) { return ($chr >= '0' && $chr <= '9'); }
function isIdChr($chr) { return ($chr === '_') || isLetterChr($chr) || isNumberChr($chr); }

// @link http://php.net/manual/en/language.operators.precedence.php
function parseExpr($expr) {
    $next = function($i) use($expr) {
        return ($expr[$i + 1] ?? '');
    };
    $stack = []; $depth = 0; $buffer = null; $bufferIndex = null;
    for ($i = 0; isset($expr[$i]); $i++) {
        $chr = $expr[$i];
        switch ($chr) {
            // space
            case ' ':
                if (!$depth) {
                    continue 2;
                }
                break;
            // id's
            case isIdChr($chr):
                $buffer = $chr; $bufferIndex = $i;
                while (isIdChr($nextChr = ($expr[$i + 1] ?? ''))) {
                    $buffer .= $nextChr;
                    $i++;
                }
                break;
            // string's
            case "'":
            case '"':
                $buffer = $chr; $bufferIndex = $i;
                while (isset($expr[$i])) {
                    $nextChr = ($expr[$i + 1] ?? '');
                    $nextNextChr = ($expr[$i + 2] ?? '');
                    if ($nextChr !== '\\' and $nextNextChr === $chr) {
                        $buffer .= $nextChr;
                        $i++;
                        break;
                    }
                    $buffer .= $nextChr;
                    $i++;
                }
                $buffer .= $chr;
                $i++;
                break;
            // operator's: ++ -- += -= *= **= <<= >>= <>
            case '+':
            case '-':
            case '*':
            case '<':
            case '>':
            case '&':
            case '|':
            case '?':
                $buffer = $chr; $bufferIndex = $i;
                $nextChr = $expr[$i + 1] ?? '';
                if ($chr === '?') {
                    if ($nextChr === '?' || $nextChr === ':') {
                        $buffer .= $nextChr;
                        $i++;
                    }
                } elseif ($nextChr === '>') {
                    $buffer .= $nextChr;
                    $i++;
                } else {
                    if ($nextChr === $chr) {
                        $buffer .= $nextChr;
                        $i++;
                    }
                    $nextChr = $expr[$i + 1] ?? '';
                    if ($nextChr === '=') {
                        $buffer .= $nextChr;
                        $i++;
                    }
                }
                break;
            // operator's: .= /= %= ^=
            case '.':
            case '/':
            case '%':
            case '^':
                $buffer = $chr; $bufferIndex = $i;
                if (($nextChr = $expr[$i + 1] ?? '') === '=') {
                    $buffer .= $nextChr;
                    $i++;
                }
                break;
            // operator's: = == == != !==
            case '=':
            case '!':
                $buffer = $chr; $bufferIndex = $i;
                $nextChr = $expr[$i + 1] ?? '';
                if ($nextChr === '=') {
                    $buffer .= $nextChr;
                    $i++;
                }
                $nextChr = $expr[$i + 1] ?? '';
                if ($nextChr === '=') {
                    $buffer .= $nextChr;
                    $i++;
                }
                break;
            // all others
            default:
                if ($depth) {
                    $depth--;
                } else {
                    $stack[] = [$chr, $i];
                    $buffer = null;
                    continue 2;
                }
        }

        if ($buffer !== null) {
            $stack[] = [$buffer, $bufferIndex];
            $buffer = $bufferIndex = null;
        }
    }

    return $stack;
}

// include_once '../test/_inc.php';
// $exprs = [
//     'a = 1',
//     'a .= "bc"',
//     '"ab\"c"'
// ];
// foreach ($exprs as $expr) {
//     pre(parseExpr($expr));
//     pre('...');
// }

// operator'lerin hepsi belirlenmeli, aksi halde var id veya diger id'leri atamak cok sikinti (if token.next.type = x meselesi)!!!
