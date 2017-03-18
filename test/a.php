<?php
include '_inc.php';

// const RE_EXPR = '~^(?![\'"]).*$~';
// const RE_ARR = '[.*]';
const RE_OPR = '(?:[\^\~<>!=%.&@*/+-]+|ise?|not|and|or)';

function isOpr($input) {
    return preg_match('~^'. RE_OPR .'$~', $input);
}
function isOprLike($input) {
    return preg_match('~'. RE_OPR .'(?:.+)~', $input);
}
function isString($input) {
    $fChr = $input[0]; $lChr = substr($input, -1);
    return ($fChr === "'" and $lChr === "'") or ($fChr === '"' and $lChr === '"');
}
function isStringExpr($input) {
    return !!preg_match('~(?:(?!\()[\'"].*[\'"]\s*\+|\+\s*[\'"].*[\'"](?!\)))~', $input);
}
function isFunctionExpr($input) {
    return !!preg_match('~^(?:\(.*\))$~i', $input);
}

function isExpr($input) {
    $input = trim($input);
    if ($input and strlen($input) > 1) {
        if (isOprLike($input)) return true;
        // if (isStringExpr($input)) return false; // ?
    }
    return false;
}

const RE_EXPR = '~(?:
    \s*(,)?
    \s*(\()?
    \s*(?:
        \s*([a-z_]\w*)
        \s*(?:
            \s*(?:
                  ((?:[-+!%.*/]+|and|or|ise?|not|ise?\s+not)?(=)([&@]+)?)
                | ((?:[-+!%.*/]+|and|or|ise?|not|ise?\s+not|[&@]+))
            )
            \s*(?:\s*(?:
                 (\w+|[\'"]\w+[\'"])\s*
               | ([\'"].*[\'"]?)\s*
               | ([a-z_]\w*)?\s*(\(.*\))\s*
           ))
        )?
    )
    \s*(,)?
    \s*(\))?
)~ix';
const RE_STRING_EXPR = '~(?:
    \s*(?:
          (.+)\s*(=)\s*(.+)\s*(\+)\s*(.+)\s*
        |              (.+)\s*(\+)\s*(.+)\s*
        |                     (\+)\s*(.+)\s*
        #|                         \s*(.+)\s*
    )
)~ix';
const RE_FUNCTION_EXPR = '~(?:
    \s*(\()
    \s*(?:
          (.+)\s*(=)\s*(.+)\s*(,)\s*(.+)\s*
        |              (.+)\s*(,)\s*(.+)\s*
        |                     (,)\s*(.+)\s*
        #|                        \s*(.+)\s*
    )?
    \s*(\))
)~ix';
const RE_COMMA_EXPR = '~\(([^()]|(?R))+\)|\[[^\]]*\]|\'[^\'\\\]*\'|\"[^\"\\\]*\"|[^(),\s]*~';
// $s ='1, (a*2), ["1", \'2\'], \'2,\', ".,.", ".\".", \'.\\\'.\', 111';
// $s = '"1", 2';
// prd(parseCommaExpr($s));

$expr = '1';
$expr = 'a = 1';
$expr = 'a = 1, b = 2';
$expr = 'a = 1, b = "2", c = foo(3, "..")';
// $expr = 'a = "ab" + c + foo(3, "..")';
// $expr = '"ab" + "c" + foo(3, "..")';
// $expr = '"c" + foo(3, "..")';
// $expr = 'foo(3, "..")';
// $expr = '+ "1"';
// $expr = 'a + "1"';
// $expr = 'a = a + "1"';
// $expr = 'a = "a" + foo("1")';
// $expr = '"1" + a';
// $expr = 'a = "1" + a';
// $expr = 'a = foo("1") + "1"';
$expr = 'foo("1") + "1"';
$expr = '("1") + "1"';
$expr = '"1" + foo("1", a)';
$expr = 'a = foo("1") + a';
// prd(isStringExpr($expr));

$tokens = parse($expr);
// foreach ($tokens as &$token) {
//     if (isFunctionExpr($token[0])) {
//         $tokens += parseFunctionExpr($token[0]);
//     } elseif (isStringExpr($token[0])) {
//         $tokens += parseStringExpr($token[0]);
//     } elseif (isExpr($token[0])) {
//         $tokens += parseExpr($token[0]);
//     }
// }
pre($tokens);

function parse($expr) {
    if (isFunctionExpr($expr)) {
        // return parseFunctionExpr($expr);
    } elseif (isStringExpr($expr)) {
        // return parseStringExpr($expr);
    } else {
        return parseExpr($expr);
    }
}
function parseExpr($expr) {
    return preg_split(RE_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
function parseStringExpr($expr) {
    return preg_split(RE_STRING_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
function parseFunctionExpr($expr) {
    return parseCommaExpr($expr);
}
function parseCommaExpr($expr) {
    $return = [];
    if (preg_match_all(RE_COMMA_EXPR, $expr, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            if (!empty($match[0])) {
                $return[] = $match;
                $return[] = [',', $match[1] + strlen($match[0]) /* add index */];
            }
        }
        array_pop($return);
    }
    return $return;
}
