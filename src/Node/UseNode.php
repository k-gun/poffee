<?php
declare(strict_types=1); namespace Poffee\Node;

use Poffee\Util\Util;
use Poffee\NodeException;

final class UseNode extends Control
{
    final public function render(): self
    {
        $all = strpos($this->value, '.*');
        if ($all) {
            $glob = $this->glob(substr($this->value, 0, $all));
            if (empty($glob)) {
                throw new NodeException("No files found to use!", $this->file, $this->line);
            }

            $objects = [];
            $objectsBase = null;
            foreach ($glob as $file) {
                $fileName = Util::getFileName($file);
                if ($objectsBase == null) {
                    $objectsBase = $fileName;
                }
                $objects[] = $fileName;
            }

            $this->value = $objectsBase .'\\'. (
                count($objects) > 1 ? '{'. join(', ', $objects) .'}' : join('', $objects)
            );
        } else {
            $this->value = str_replace('.', '\\', $this->value);
        }

        $this->type = self::TYPE_CONTROL;

        return $this;
    }

    final public function toString(): string
    {
        return sprintf('%s %s;', $this->name, $this->value);
    }

    final private function glob($subdir): array
    {
        $glob = glob(sprintf('%s/%s/*.poffee', $this->parser->dir, $subdir));
        // @todo recursive
        // @todo check class encapsulations before this
        return $glob;
    }
}
