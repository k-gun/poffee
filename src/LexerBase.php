<?php
declare(strict_types=1); namespace Poffee;

include 'const.php';

abstract class LexerBase
{
    function toAst(TokenCollection $tokens) { //return $tokens->toArray();
        // $tokens = $this->checkComments($tokens);
        // $tokens = $this->setObjectIds($tokens);
        // $tokens = $this->setFunctionIds($tokens);
        // $tokens = $this->setAssignIds($tokens);
        $array = [];
        foreach ($tokens as $token) {
            if (!$token->type and isExpr($token->value)) {
                $children = $this->generateTokens(parseExpr($token->value));
                if (!$children->isEmpty()) {
                    // $array = array_merge($array, $this->toAst($children));
                    $array = array_merge($array, $children->toArray(true));
                }
                $token->skip = true;
            }
            // no needed anymore
            unset($token->tokens);
            // skip expressions, cos all should be parsed above already
            if (!$token->skip) {
                $array[] = $token->toArray(true);
            }
        }
        $tokens = new TokenCollection($array);
        // $tokens = $this->setObjectIds($tokens);
        // $tokens = $this->setFunctionIds($tokens);
        // $tokens = $this->setAssignIds($tokens);
        $tokens = $this->prepareBlockStatements($tokens);
        return $tokens->toArray(true);
    }
    function checkComments(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            // drop empty comments
            if ($token->type === T_COMMENT and $token->next->type !== T_COMMENT_CONTENT) {
                $token->remove();
            }
        }
        return $tokens;
    }
    function setObjectIds(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_MODULE) {
                $token->next->type = T_MODULE_ID;
            } elseif ($token->type === T_OBJECT) {
                $next = $tokens->next();
                while ($next and $next->type !== T_COLON) {
                    if ($next->type === T_CLASS or $next->type === T_INTERFACE or $next->type === T_TRAIT) {
                        // pass
                    } elseif ($next->value === '>') {
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
    function setFunctionIds(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_FUNCTION) {
                $token->next->type = T_FUNCTION_ID;
            }
        }
        return $tokens;
    }
    function setAssignIds(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_CONST or $token->type === T_VAR) {
                $next = $token->next();
                if ($next->type === T_OPR) {
                    if ($next->value === C_PRIVATE) $next->type = T_PRIVATE;
                    elseif ($next->value === C_PROTECTED) $next->type = T_PROTECTED;
                } elseif (!$next->type) {
                    $next->type = ($token->type === T_CONST) ? T_CONST_ID : T_VAR_ID;
                }
            } elseif ($token->type === T_PRIVATE or $token->type === T_PROTECTED) {
                if (!$token->next->type) {
                    $token->next->type = ($token->prev->type === T_CONST) ? T_CONST_ID : T_VAR_ID;
                }
            } elseif ($token->type === T_ASSIGN) {
                if (!$token->prev->type) {
                    $token->prev->type = T_VAR_ID;
                }
            } elseif ($token->type === T_OPR) {
                $prev = $token->prev();
                if (in_array($token->value, C_ASSIGNS)) {
                    $prev->type = T_VAR_ID; // += -= *= **= /= .= %= &= |= ^= <<= >>=
                }
            }
                // pre($tokens);
        }
        return $tokens;
    }
    function prepareBlockStatements(TokenCollection $tokens) {
        $tokens->reset();
        while ($token = $tokens->next()) {
            if ($token->type === T_IF or $token->type === T_ELSEIF or $token->type === T_SWITCH or $token->type === T_CASE or $token->type === T_FOR) {
                $next = $tokens->next();
                while ($next and $next->type !== T_COLON) {
                    if (!$next->type) {
                        $nextNext = $next->next();
                        if ($nextNext) {
                            if ($nextNext->type === T_PAREN_OPEN) {
                                $next->type = T_FUNCTION_CALL;
                            } elseif ($nextNext->type === T_OPR) {
                                $next->type = T_VAR_ID;
                            } elseif ($nextNext->type === T_COLON) { // end
                                $next->type = T_VAR_ID;
                            }
                        }
                        pre($next->value, $nextNext->value);
                    }
                    $next = $tokens->next();
                }
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
            // numbers
            case isNumberChr($chr):
                $buffer = $chr; $bufferIndex = $i;
                while (isNumberChr($nextChr = ($expr[$i + 1] ?? '')) or $nextChr === '.') {
                    $buffer .= $nextChr;
                    $i++;
                    // float?
                    if (($expr[$i + 1] ?? '') === '.' and ($expr[$i + 2] ?? '') !== '=') {
                        $buffer .= '.';
                        $i++;
                    }
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
            // operator's: ?? ?:
            case '?':
                $buffer = $chr; $bufferIndex = $i;
                $nextChr = $expr[$i + 1] ?? '';
                if ($nextChr === '?' or $nextChr === ':') {
                    $buffer .= $nextChr;
                    $i++;
                }
                break;
            // operator's: ++ -- += -= *= **= <<= >>= <> @@
            case '+':
            case '-':
            case '*':
            case '<':
            case '>':
            case '&':
            case '|':
            case '@':
                $buffer = $chr; $bufferIndex = $i;
                $nextChr = $expr[$i + 1] ?? '';
                if ($nextChr === '>') {
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
            // operator's: .= /= %= ^= or // comment
            case '.':
            case '/':
            case '%':
            case '^':
                $buffer = $chr; $bufferIndex = $i;
                $nextChr = $expr[$i + 1] ?? '';
                if ($nextChr === '=') {
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
            // reset
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
