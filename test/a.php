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

function parseCommaExpr2($input) {
    $ret = [];
    $i = $ip = $in = 0;
    while ($i < strlen($input)) {
        $c = $input[$i++];
        // $cp = $input[$ip];
        if ($c === '"') {
            while (($cn = $input[$$i]) !== '"') {
                pre($cn);
            }
        }
        $ip = $i - 1; // prev index
        $in = $i + 1; // next index
    }
    prr($i, $ip, $in);
    return $ret;
}
prd(parseCommaExpr2('1, "2,"'));

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
$expr = '"1 " + ("1")';
// $expr = 'foo("1")';

$tokens = parse($expr);
foreach ($tokens as &$token) {
    // prr($token[0], ":", isString($token[0]), ":", isStringExpr($token[0]));
    if (isFunctionExpr($token[0])) {
        pre("...");
        $token['_'] = parseFunctionExpr($token[0]);
    }
}
pre($tokens);

function parse($expr) {
    if (isFunctionExpr($expr)) {
        prd($expr);
    } elseif (isStringExpr($expr)) {
        return parseStringExpr($expr);
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
    return preg_split(RE_FUNCTION_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
function parseCommaExpr($expr) {
    return preg_split(RE_COMMA_EXPR, $expr, -1,
        PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
}
