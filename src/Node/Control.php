<?php
declare(strict_types=1); namespace Poffee\Node;

abstract class Control extends \Poffee\Node
{
    protected $status = 0;

    final public function isOpen(): bool
    {
        return (0 == $this->status);
    }
    final public function isClosed(): bool
    {
        return (1 == $this->status);
    }
}

