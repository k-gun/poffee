<?php
declare(strict_types=1); namespace Poffee;

class TokenCollection implements \IteratorAggregate
{
    private $tokens = [];
    private $tokensIndex = 0;
    private $tokensIndexPointer = 0;

    public function __construct(array $tokens = null)
    {
        if ($tokens) foreach ($tokens as $token) {
            if (is_object($token)) {
                $token = $token->toArray();
            }
            $this->add($token);
        }
    }
    public function __get($name)
    {
        switch ($name) {
            case 'prev': return $this->prev();
            case 'next': return $this->next();
            case 'first': return $this->first();
            case 'last': return $this->last();
        }
    }

    public function add(array $data)
    {
        $token = new Token($this, $data);
        $token->index = $this->tokensIndex;
        $this->tokens[$this->tokensIndex] = $token;
        $this->tokensIndex++;
    }

    public function has(int $i)
    {
        return isset($this->tokens[$i]);
    }
    public function hasPrev()
    {
        return $this->has($this->tokensIndexPointer - 1);
    }
    public function hasNext()
    {
        return $this->has($this->tokensIndexPointer);
    }

    public function get(int $i)
    {
        return $this->tokens[$i] ?? null;
    }
    public function prev()
    {
        return $this->get($this->tokensIndexPointer--);
    }
    public function next()
    {
        return $this->get($this->tokensIndexPointer++);
    }

    public function first()
    {
        return $this->get(0);
    }
    public function last()
    {
        return $this->get($this->tokensIndex - 1);
    }

    public function remove(Token $token)
    {
        $i = 0;
        while (isset($this->tokens[$i])) {
            if ($this->tokens[$i] === $token) {
                unset($this->tokens[$i]);
                break;
            }
            $i++;
        }
    }
    public function removeAt(int $i)
    {
        unset($this->tokens[$i]);
    }

    public function tokensIndex()
    {
        return $this->tokensIndex;
    }
    public function tokensIndexPointer()
    {
        return $this->tokensIndexPointer;
    }

    public function reset()
    {
        $this->tokensIndexPointer = 0;
    }
    public function count()
    {
        return count($this->tokens);
    }
    public function isEmpty()
    {
        return empty($this->tokens);
    }
    public function toArray(bool $clear = false)
    {
        $array = [];
        foreach ($this->tokens as $token) {
            $array[] = $token->toArray($clear);
        }
        return $array;
    }
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->tokens);
    }
}
