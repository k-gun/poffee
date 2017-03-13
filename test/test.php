<?php autoload();

$s = file_get_contents('book.poffee');
$f = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
$p = '~
     ((use)\s*([^\r\n]+))
    |(([a-z][a-z0-9_]+)\s*=\s*(.+))
    |((if|else|elseif)\s*(.*)\s*(:))
    |((\s{4})([a-z][a-z0-9_]+)\s*(.+))
~xi';
// $m = preg_split($p, $s, -1, $f);
// prd($m);


$parser = new Poffee\Parser();
$parser->parse('book.poffee');
// pre($parser);


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












function _p($arg) {
    if ($arg === null) $arg = 'NULL';
    elseif ($arg === true) $arg = 'TRUE';
    elseif ($arg === false) $arg = 'FALSE';
    return sprintf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($arg, true)));
}
function pre(...$args) {
    $print = '';
    foreach ($args as $arg) {
        $print .= _p($arg);
    }
    print $print;
}
function prd($arg) {
    print _p($arg); die;
}

function autoload() {
    return spl_autoload_register(function($objectName) {
        $objectName = str_replace('\\', '/', substr($objectName, 7));
        $objectFile = sprintf('../src/%s.php', $objectName);
        // pre($objectName, $objectFile); //die;
        require $objectFile;
    });
}
