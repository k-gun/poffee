<?php
declare(strict_types=1); namespace Poffee\Util;

class Util
{
    final public static function getFileName($file): string
    {
        $file = basename($file);
        return substr($file, 0, strpos($file, '.'));
    }
}
