<?php
include '_inc.php';
autoload();

$reader = new Poffee\FileReader('book.poffee');
$writer = new Poffee\FileWriter('book.php');
$parser = new Poffee\Parser();
$tokens = $parser->parse($reader);
// $writer->write($tokens);
prf($tokens);


// pre(defined("T_SPACE"));
// // $cs = get_defined_constants();
// $cs = array_filter(get_defined_constants(), function($k) {
//     return substr($k, 0, 2) == 'T_';
// }, ARRAY_FILTER_USE_KEY);
// $cv = "";
// foreach ($cs as $key => $value) {
//     $cv .= "[$key] => $value\n";
// }
// file_put_contents('ast', $cv);



// $poffee = new Poffee\Poffee(__dir__);
// $parser = $poffee->parse('book.poffee');

// pre($parser);
// pre($parser->toString());



// $s = file_get_contents('book.poffee');
// $f = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
// $p = '~
//      ((use)\s*([^\r\n]+))
//     |(([a-z][a-z0-9_]+)\s*=\s*(.+))
//     |((if|else|elseif)\s*(.*)\s*(:))
//     |((\s{4})([a-z][a-z0-9_]+)\s*(.+))
// ~xi';
// $m = preg_split($p, $s, -1, $f);
// prd($m);
