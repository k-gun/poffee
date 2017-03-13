<?php
declare(strict_types=1); namespace Poffee\Node;

use Poffee\Parser;
use Poffee\NodeException;

class IfNode extends Condition
{
    final public function render()
    {
        if (':' != substr($this->value, -1)) {
            throw new NodeException("Conditions must be end with ':' sign!",
                $this->file, $this->line);
        }

        $this->value = chop($this->value, ':');
        $this->value = Parser::updateVariables($this->value);
        $this->value = Parser::updateConditions($this->value);

        pre($this->value);
        pre($this->toString());
        die;

        $this->type = self::TYPE_CONDITION;

        return $this;
    }

    final public function toString(): string
    {
        return sprintf('%s (%s) {', $this->name, $this->value);
    }
}
