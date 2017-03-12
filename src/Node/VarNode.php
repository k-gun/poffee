<?php
declare(strict_types=1); namespace Poffee\Node;

use Poffee\NodeException;

final class VarNode extends Assignment
{
    final public function render(): self
    {
        if ('$' == substr($this->name, 0, 1)) {
            throw new NodeException("Dollar sign is forbidden, use plain PHP if you want!",
                $this->file, $this->line, 0);
        }

        $this->type = self::TYPE_ASSIGNMENT;

        return $this;
    }

    final public function toString(): string
    {
        return sprintf('$%s = %s;', $this->name, $this->value);
    }
}
