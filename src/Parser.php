<?php
declare(strict_types=1); namespace Poffee;

final class Parser
{
    use Util\GetterTrait;

    const REGEXP_CONTROL = '~^(use|class|interface|trait|function|do|while|for|foreach|if|else|elseif)\s+(.+)~i';
    const REGEXP_ASSIGNMENT = '~^(\$?[\w]+)\s*=\s*(.+)~i';

    private $dir;
    private $file;
    private $nodes = [];

    private $indent = '    ';

    final public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    final public function parse(string $file)
    {
        $this->file = basename($file);
        if (!is_file($this->file)) {
            throw new ParserException("No such file {$this->file} found!");
        }

        $contents = file(sprintf('%s/%s', $this->dir, $this->file));
        // pre($contents);

        foreach ($contents as $i => $content) {
            $line = $i + 1;
            // $content = trim($content);
            if (preg_match(self::REGEXP_CONTROL, $content, $match)) {
                $this->renderControl($line, $match[1], $match[2]);
            } elseif (preg_match(self::REGEXP_ASSIGNMENT, $content, $match)) {
                $this->renderAssignment($line, $match[1], $match[2]);
            }
        }

        return $this;
    }

    final private function renderControl(int $line, string $name, string $value)
    {
        switch ($name) {
            case Node::NAME_USE:
                $node = new Node\UseNode($this, $name, $value, $this->file, $line);
                break;
            case Node::NAME_IF:
                $node = new Node\IfNode($this, $name, $value, $this->file, $line);
                break;
        }

        if (isset($node)) {
            $this->nodes[] = $node->render();
        }
    }

    final private function renderAssignment(int $line, string $name, string $value)
    {
        $node = new Node\VarNode($this, $name, $value, $this->file, $line);
        $this->nodes[] = $node->render();
    }

    final public function toString(): string
    {
        $contents = '';

        return "..";
    }

    // final public function getDir(): string
    // {
    //     return $this->dir;
    // }
    // final public function getFile()
    // {
    //     return $this->file;
    // }
    // final public function getLine()
    // {
    //     return $this->line;
    // }
}
