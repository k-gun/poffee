<?php
function _p($arg) {
    if ($arg === null) $arg = 'NULL';
    elseif ($arg === true) $arg = 'TRUE';
    elseif ($arg === false) $arg = 'FALSE';
    return sprintf('%s', preg_replace(
        ['~\[(\w+):.*?\:private\]~', '~Object\s*\*RECURSION\*~'],
        ['[\\1:private]', '{...}'],
        print_r($arg, true)
    ));
}
function pre(...$args) {
    $print = '';
    foreach ($args as $arg) {
        $print .= _p($arg). "\n";
    }
    print $print;
}
function prr(...$args) {
    $print = '';
    foreach ($args as $arg) {
        $print .= _p($arg) ." ";
    }
    print $print . "\n";
}
function prd($arg) {
    print _p($arg); die("\n");
}
function prf($arg, $f = 'ast.json') {
    // file_put_contents($f, _p($arg));
    file_put_contents($f, json_encode($arg, JSON_PRETTY_PRINT));
}

function autoload() {
    return spl_autoload_register(function($objectName) {
        $objectName = str_replace('\\', '/', substr($objectName, 7));
        $objectFile = sprintf('../src/%s.php', $objectName);
        // pre($objectName, $objectFile); //die;
        require $objectFile;
    });
}
