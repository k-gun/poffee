<?php autoload();

$poffee = new Poffee\Poffee(__dir__);
$parser = $poffee->parse('book.poffee');

pre($parser);
pre($parser->toString());












function _pre($arg) {
    if ($arg === null) $arg = 'NULL';
    elseif ($arg === true) $arg = 'TRUE';
    elseif ($arg === false) $arg = 'FALSE';
    return sprintf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($arg, true)));
}
function pre(...$args) {
    $print = '';
    foreach ($args as $arg) {
        $print .= _pre($arg);
    }
    print $print;
}

function autoload() {
    return spl_autoload_register(function($objectName) {
        $objectName = str_replace('\\', '/', substr($objectName, 7));
        $objectFile = sprintf('../src/%s.php', $objectName);
        // pre($objectName, $objectFile); //die;
        require $objectFile;
    });
}
