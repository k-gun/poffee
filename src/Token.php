<?php
declare(strict_types=1); namespace Poffee;

class Token
{
    public  $tokens;

    public function __construct(TokenCollection $tokens, array $data)
    {
        $this->tokens = $tokens;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
    public function __get($name)
    {
        switch ($name) {
            case 'prev': return $this->prev();
            case 'next': return $this->next();
        }
    }
    public function hasPrev()
    {
        return $this->tokens->has($this->index - 1);
    }
    public function hasNext()
    {
        return $this->tokens->has($this->index + 1);
    }
    public function prev()
    {
        return $this->tokens->get($this->index - 1);
    }
    public function next()
    {
        return $this->tokens->get($this->index + 1);
    }
    public function remove()
    {
        $this->tokens->removeAt($this->index);
    }
    public function toArray(bool $clear = false)
    {
        $array = get_object_vars($this);
        if ($clear) {
            unset($array['tokens'], $array['index']);
        }
        return $array;
    }
}
