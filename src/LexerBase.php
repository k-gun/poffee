<?php
declare(strict_types=1); namespace Poffee;

include 'const.php';

abstract class LexerBase
{
    function toAst(TokenCollection $tokens) { //return $tokens->toArray();
        $tokens = $this->checkComments($tokens);
        $tokens = $this->setObjectIds($tokens);
        $tokens = $this->setAssignIds($tokens);
        // $tokens = $this->setFunIds($tokens); burdayim
        $array = [];
        foreach ($tokens as $i => $token) {
            unset($token->tokens);
            if (!$token->type and isExpr($token->value)) {
                $token->type = T_EXPR;
                $children = $this->generateTokens(parseExpr($token->value));
                if ($children) {
                    $token->children = $this->toAst($children);
                    // $array[$i]['children'] = $this->toAst($token->children); // or
                    // $array = array_merge($array, $this->toAst(new TokenCollection($token->children))); // or
                    // unset($token->children);
                }
            }
            // skip expressions, cos all should be parsed above already
            // if ($token->type !== T_EXPR) {
            //     $array[] = $token->toArray(true);
            // }
            $array[] = $token->toArray(true);
        }
        return $array;
    }
    function checkComments(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_COMMENT) {
                $token->value = sprintf('// %s', trim($token->next->value));
                $token->next->remove(); // drop comment content
            }
        }
        return $tokens;
    }
    function setObjectIds(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_MODULE) {
                $token->next->type = T_MODULE_ID;
            } elseif ($token->type === T_CLASS or $token->type === T_INTERFACE or $token->type === T_TRAIT or $token->type === T_FUN) {
                $token->next->type = T_OBJECT_ID;
            } elseif ($token->type === T_OBJECT_ID) {
                $next = $tokens->next();
                // bu while i yukari al // if type == T_OBJECT yap T_OBJECT_ID degil
                while ($next and $next->type !== T_COLON) {
                    if ($next->value === '>') {
                        $next->type = T_EXTENDS;
                    } elseif ($next->value === '>>') {
                        $next->type = T_IMPLEMENTS;
                    } else {
                        $next->type = T_OBJECT_ID;
                    }
                    $next = $tokens->next();
                }
            }
        }
        return $tokens;
    }
    function setAssignIds(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_CONST) {
                $token->next->type = T_CONST_ID;
            } elseif ($token->type === T_VAR) {
                $token->next->type = T_VAR_ID;
            } elseif ($token->type === T_ASSIGN and !$token->prev->type) {
                $token->prev->type = T_VAR_ID;
            }
            if ($tokens->children) {
                $token->children = $this->setAssignIds($token->children);
            }
        }
        return $tokens;
    }
}

// @todo move to Util
function isId($input) { return preg_match('~^(?:[a-z_]\w*)$~i', $input); }
function isKeyword($input) { return in_array($input, KEYWORDS); }
function isNumber($input) { return is_numeric($input); }
function isString($input) {
    $split = preg_split('~(?:([\'"])\s*([,+]))~', $input)[0] ?? null;
    if ($split) {
        $fChr = $split[0]; $lChr = substr($split, -1);
        return ($fChr === "'" and $lChr === "'") or ($fChr === '"' and $lChr === '"');
    }
    return false;
}
function isOpr($input) { return preg_match('~^[\?\^\~|&<>:!=%.@*/+-]+$~', $input); }
function isValue($input) { return isNumber($input) or isString($input); }
function isExpr($input) { return !isId($input) and !isKeyword($input) and !isValue($input); }
function isLetterChr($chr) { return ($chr >= 'a' and $chr <= 'z') or ($chr >= 'A' and $chr <= 'Z'); }
function isNumberChr($chr) { return ($chr >= '0' and $chr <= '9'); }
function isIdChr($chr) { return ($chr === '_') or isLetterChr($chr) or isNumberChr($chr); }

// operator'lerin hepsi belirlenmeli, aksi halde var id veya diger id'leri atamak cok sikinti (if token.next.type = x meselesi)!!!
// comment content i almiyor operator diyor, Lexer split yapmiyor galiba
function parseExpr($expr) {
    // $next = function($i) use($expr) {
    //     return ($expr[$i + 1] ?? '');
    // };
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
                    if ($nextChr === '?' or $nextChr === ':') {
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
