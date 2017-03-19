<?php
include '_inc.php';

function isLetter($c) { return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z'); }
function isNumber($c) { return ($c >= '0' && $c <= '9'); }
function isId($c) { return ($c === '_') || isLetter($c) || isNumber($c); }

function parseBlockExpr($expr, $cStart, $cEnd) {
    if (false === strpos($expr, $cEnd)) {
        return $expr;
    }

    $expr = preg_split('~(.+\s*\\'. $cEnd .')\s*(,)?\s*(.+)?$~',
        $expr, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)[0];

    $i = 0; $stack = []; $buffer = ''; $depth = 0;
    while ('' !== ($c =@ $expr[$i++])) {
        switch ($c) {
            case $cStart:
                $depth++;
                break;
            case ',':
                if (!$depth) {
                    if ($buffer !== '') {
                        $stack[] = $buffer;
                        $buffer = '';
                    }
                    continue 2;
                }
                break;
            case ' ':
                if (!$depth) {
                    continue 2;
                }
                break;
            case $cEnd:
                if ($depth) {
                    $depth--;
                } else {
                    $stack[] = $buffer . $c;
                    $buffer = '';
                    continue 2;
                }
                break;
        }
        $buffer .= $c;
    }

    if ($buffer !== '') {
        $stack[] = $buffer;
    }

    return $stack;
}

function parseExpr($expr) {
    pre($expr);
    $stack = [];
    $i = 0; $stack = []; $buffer = ''; $depth = 0;
    while ('' !== ($c =@ $expr[$i++])) {
        switch ($c) {
            case '"':
            case "'":
                $buffer = $c;
                if ($c === '"') {
                    while (($cs =@ $expr[$i++]) !== '' && ($cs !== '"' || $cs === '\\')) {
                        $buffer .= $cs;
                        if ($expr[$i - 1] === '\\') { // escape
                            $buffer .= '\\';
                            $i++;
                        }
                    }
                } else {
                    while (($cs =@ $expr[$i++]) !== '' && ($cs !== "'" || $cs === '\\')) {
                        $buffer .= $cs;
                        if ($expr[$i - 1] === '\\') { // escape
                            $buffer .= '\\';
                            $i++;
                        }
                    }
                }
                $buffer .= $c;
                break;
            // case '[':
            //     $buffer = parseBlockExpr(substr($expr, $i-1), '[', ']')[0];
            //     $i = $i + strlen($buffer) - 1;
            //     break;
            case ' ':
                if (!$depth) {
                    continue 2;
                }
                break;
            case isId($c): // id leri yakalamiyor
                $buffer = $c;
                while (isId($cs =@ $expr[$i])) {
                    $buffer .= $cs;
                    $i++;
                }
                $stack[] = $buffer;
                $buffer = '';
                break;
            default:
                if ($depth) {
                    $depth--;
                } else {
                    $stack[] = $c;
                    $buffer = '';
                    continue 2;
                }
        }

        if ($buffer !== '') {
            $stack[] = $buffer;
        }
    }

    return $stack;
}
$expr = "1, ' '";
// $expr = '1, " "';
// $expr = '(11,1,[3],10,[1,[2,[3]]])';
// $expr = '11, [2]';
// $expr = '1, 2, [3,[4]], 10';
// $expr = '1,2,[3,[4]],10';
// $expr = 'aaa, [1, [2, ["3, 1"]]], end';
// $expr = '"1", 2, [1, [2, [3]]], 3';
// $expr = '11, a, "c"';
// $expr = '"1..1\"..", 1, []';
// $expr = '1,[1,2,3,[4,"5",["aşkaşlka"]]], 1, "1111", foo("1", a)';
// $expr = 'foo("aa"), 1, "str", ["a"]';
$expr = '1, [aa, "aa", [1]]';

$tokens = parseExpr($expr);
pre($tokens);

// $expr = 'a = 1';
// $expr = 'a = 1, b = 2';
// $expr = 'a = 1, b = "2", c = foo(3, "..")';
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
// $expr = 'foo("1") + "1"';
// $expr = '("1") + "1"';
// $expr = '"1" + foo("1", a)';
// $expr = 'a = foo("1") + a';



