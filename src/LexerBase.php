<?php
declare(strict_types=1); namespace Poffee;

include 'const.php';

abstract class LexerBase
{
    function toAst(TokenCollection $tokens) { //return $tokens->toArray();
        $tokens = $this->setAssignIds($tokens);
        // $tokens = $this->setFunIds($tokens); burdayim
        $array = [];
        foreach ($tokens as $i => $token) {
            $array[$i] = $token->toArray(true);
            unset($token->tokens);
            if (!$token->type and isExpr($token->value)) {
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
        return ($fChr === "'" and $lChr === "'") || ($fChr === '"' and $lChr === '"');
    }
    return false;
}
function isValue($input) { return isNumber($input) || isString($input); }
function isExpr($input) { return !isId($input) and !isKeyword($input) and !isValue($input); }
function isOpr($input) { return preg_match('~^[\?\^\~|&<>:!=%.@*/+-]+$~', $input); }
function isLetterChr($chr) { return ($chr >= 'a' and $chr <= 'z') || ($chr >= 'A' and $chr <= 'Z'); }
function isNumberChr($chr) { return ($chr >= '0' and $chr <= '9'); }
function isIdChr($chr) { return ($chr === '_') || isLetterChr($chr) || isNumberChr($chr); }

// operator'lerin hepsi belirlenmeli, aksi halde var id veya diger id'leri atamak cok sikinti (if token.next.type = x meselesi)!!!
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
