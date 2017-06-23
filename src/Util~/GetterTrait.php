<?php
declare(strict_types=1); namespace Poffee\Util;

trait GetterTrait
{
    final public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }
}
