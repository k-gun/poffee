<?php
include '_inc.php';

function isLetter($c) { return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z'); }
function isNumber($c) { return ($c >= '0' && $c <= '9'); }
function isId($c) { return ($c === '_') || isLetter($c) || isNumber($c); }

function parseExpr($expr) {
    pre($expr);
    $stack = [];
    $i = 0; $stack = []; $depth = 0; $buffer = ''; $bufferIndex = null;
    while ('' !== ($c =@ $expr[$i++])) {
        switch ($c) {
            case '"':
            case "'":
                $buffer = $c;
                $bufferIndex = $i - 1;
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
            case '?': // ?: and ?? expressions
                $buffer = $c;
                $bufferIndex = $i - 1;
                if ($expr[$i] === ':' || $expr[$i] === '?') {
                    $buffer .= $expr[$i];
                    $i++;
                }
                break;
            case ' ':
                if (!$depth) {
                    continue 2;
                }
                break;
            case isId($c):
                $buffer = $c;
                while (isId($cs =@ $expr[$i])) {
                    $buffer .= $cs;
                    $i++;
                }
                $stack[] = [$buffer, $i - 1];
                $buffer = '';
                break;
            default:
                if ($depth) {
                    $depth--;
                } else {
                    $stack[] = [$c, $i - 1];
                    $buffer = '';
                    continue 2;
                }
        }

        if ($buffer !== '') {
            $stack[] = [$buffer, $bufferIndex];
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
// $expr = '1, [aa, "aa", [a??1]]';
$expr = '"1", a';

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



