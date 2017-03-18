<?php
include '_inc.php';

// const RE_EXPR = '~^(?![\'"]).*$~';
const RE_ARR = '[.*]';
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
function isExpr($input) {
    $input = trim($input);
    if ($input and strlen($input) > 1) {
        if (isOprLike($input)) return true;
        if (isStringExpr($input)) return false; // ?
    }
    return false;
}

const RE_EXPR = '~(?:
    \s*(,)?
    \s*(\()?
    \s*(?:
        \s*([a-z_]\w*)
        \s*(?:
           \s*((?:[!%.*/+-]+|ise?|not|and|or)?=(?:[&@]+)?)
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

const RE_STR_EXPR = '~(?:
    \s*(,)?
    \s*(\()?
    \s*(?:
        ...
    )
    \s*(,)?
    \s*(\))?
)~ix';

$expr = '1';
$expr = 'a = 1';
$expr = 'a = 1, b = 2';
$expr = 'a = 1, b = "2", c = foo(3, "..")';
// $expr = 'a = a + "1"';
// $expr = 'a = "ab" + c + foo(3, "..")';
// $expr = '"ab" + "c" + foo(3, "..")';
// $expr = '"c" + foo(3, "..")';
// $expr = 'foo(3, "..")';

if (isStringExpr($expr)) {
    $tokens = parseStringExpr($expr);
} else {
    $tokens = parseExpr($expr);
}

foreach ($tokens as &$token) {
    // prr($token[0], ":", isString($token[0]), ":", isStringExpr($token[0]));
    // if (isExpr($token[0])) {
    //     $token['_'] = parseExpr($token[0]);
    // }
}
pre($tokens);

function parseExpr($expr) {
    return preg_split(RE_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
function parseStringExpr($expr) {
    return preg_split(RE_STR_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
