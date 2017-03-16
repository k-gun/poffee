<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_DECLARE = 'T_DECLARE', T_DECLARE_EXPR = 'T_DECLARE_EXPR';
const T_MODULE = 'T_MODULE', T_MODULE_EXPR = 'T_MODULE_EXPR';
const T_USE = 'T_USE', T_USE_EXPR = 'T_USE_EXPR';
const T_CONST = 'T_CONST', T_CONST_EXPR = 'T_CONST_EXPR';
const T_CLASS = 'T_CLASS';


const T_OPR = 'T_OPR';
const T_ASSIGN_OPR = 'T_ASSIGN_OPR';
const T_COMMENT_OPR = 'T_COMMENT_OPR', T_COMMENT_CONTENT = 'T_COMMENT_CONTENT';

const T_DOT = 'T_DOT';
const T_COMMA = 'T_COMMA';
const T_COLON = 'T_COLON';
const T_QUESTION = 'T_QUESTION';

const T_VAR = 'T_VAR';
const T_OBJECT = 'T_OBJECT';

const T_MODF = 'T_MODF';
const T_EXTENDS_MODF = 'T_EXTENDS_MODF';
const T_IMPLEMENTS_MODF = 'T_IMPLEMENTS_MODF';
const T_ABSTRACT_MODF = 'T_ABSTRACT_MODF';
const T_FINAL_MODF = 'T_FINAL_MODF';

const T_RETURN = 'T_RETURN';
const T_IF = 'T_IF';
const T_ELSE = 'T_ELSE';
const T_ELSE_IF = 'T_ELSE_IF';
const T_LOOP = 'T_LOOP';
const T_PRNT_BLOCK = 'T_PRNT_BLOCK';
const T_OPEN_PRNT = 'T_OPEN_PRNT';
const T_CLOSE_PRNT = 'T_CLOSE_PRNT';
const T_BRKT_BLOCK = 'T_BRKT_BLOCK';
const T_OPEN_BRKT = 'T_OPEN_BRKT';
const T_CLOSE_BRKT = 'T_CLOSE_BRKT';

const T_ID = 'T_ID';
const T_VAR_ID = 'T_VAR_ID';
const T_FUNCTION_ID = 'T_FUNCTION_ID';
const T_OBJECT_ID = 'T_OBJECT_ID';
const T_PROPERTY_ID = 'T_PROPERTY_ID';
const T_METHOD_ID = 'T_METHOD_ID';

const T_EXPR = 'T_EXPR';
const T_NOT_EXPR = 'T_NOT_EXPR';
const T_OPR_EXPR = 'T_OPR_EXPR';
const T_CALLABLE_CALL_EXPR = 'T_CALLABLE_CALL_EXPR';
const T_METHOD_CALL_EXPR = 'T_METHOD_CALL_EXPR';
const T_PROPERTY_EXPR = 'T_PROPERTY_EXPR';
const T_ARRAY_EXPR = 'T_ARRAY_EXPR';

const T_NULL = 'T_NULL';
const T_STRING = 'T_STRING';
const T_NUMBER = 'T_NUMBER';
const T_BOOLEAN = 'T_BOOLEAN';

const T_FUNCTION = 'T_FUNCTION';
const T_FUNCTION_CALL = 'T_FUNCTION_CALL';

const C_COLON = ':';
const C_EXTENDS = '>';
const C_IMPLEMENTS = '>>';

abstract class LexerBase
{}

class Token
{
    public function __construct(Tokens $tokens, array $data)
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
    public function toArray()
    {
        return get_object_vars($this);
    }
}

class Tokens
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
    public function toArray()
    {
        return array_filter($this->tokens); // array_filter?
    }
}

// cache these?
function isValidID($input) {
    return preg_match('~^(?:[a-z_]\w*)$~i', $input);
}
function isValidColon($input) {
    return $input && $input !== PHP_EOL &&
        preg_match('~^(?:(\s+)?module|abstract|final|object|func|if|else|elseif|for|while)~', $input)
            ? (C_COLON === substr(chop($input), -1)) : true;
}