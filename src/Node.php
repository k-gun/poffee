<?php
declare(strict_types=1); namespace Poffee;

abstract class Node
{
    use Util\GetterTrait;

    const TYPE_ASSIGNMENT = 1;
    const TYPE_CONTROL = 2;
    const TYPE_CONDITION = 3;

    const NAME_USE = 'use';
    const NAME_IF = 'if';

    const ATTR_FINAL = 'final';
    const ATTR_STATIC = 'static';
    const ATTR_ABSTRACT = 'abstract';
    const ATTR_PUBLIC = 'public';
    const ATTR_PRIVATE = 'private';
    const ATTR_PROTECTED = 'protected';

    protected $parser;
    protected $type;
    protected $file;
    protected $line;
    protected $name; // class, function, for, foreach ...
    protected $value;
    protected $attributes = [];
    protected $childNodes = [];

    final public function __construct(Parser $parser, string $name, string $value = null,
        string $file, int $line, array $attributes = null)
    {
        $this->parser = $parser;
        $this->file = $file;
        $this->line = $line;
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes ?? [];
    }

    final public function setAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    final public function getAttribute(string $name)
    {
        return ($this->attributes[$name] ?? null);
    }

    final public function addChild(Node $node): self
    {
        $this->childNodes[] = $node;
        return $this;
    }
    final public function removeChild(Node $node): self
    {
        foreach ($this->childNodes as $i => $childNode) {
            if ($childNode == $node) {
                unset($this->childNodes[$i]);
                break;
            }
        }
        return $this;
    }
    final public function hasChildNodes(): bool
    {
        return !empty($this->childNodes);
    }

    abstract public function render();
    // abstract public function renderWith(string $value);
    abstract public function toString(): string;
}
