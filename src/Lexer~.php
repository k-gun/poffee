<?php
declare(strict_types=1); namespace Poffee;

final class Lexer
{
    const T_EOL                 = 'T_EOL';
    const T_EOF                 = 'T_EOF';
    const T_SPACE               = 'T_SPACE';
    const T_NONE                = 'T_NONE';
    const T_STRING              = 'T_STRING';
    const T_NUMBER              = 'T_NUMBER';
    const T_PAREN_CLOSETHESIS   = 'T_PAREN_CLOSETHESIS';
    const T_PAREN_OPENTHESIS    = 'T_PAREN_OPENTHESIS';
    const T_COMMA               = 'T_COMMA';
    const T_COLON               = 'T_COLON';
    const T_SEMICOLON           = 'T_SEMICOLON';
    const T_DIVIDE              = 'T_DIVIDE';
    const T_DOT                 = 'T_DOT';
    const T_EQUALS              = 'T_EQUALS';
    const T_GREATER_THAN        = 'T_GREATER_THAN';
    const T_LOWER_THAN          = 'T_LOWER_THAN';
    const T_MINUS               = 'T_MINUS';
    const T_MULTIPLY            = 'T_MULTIPLY';
    const T_NEGATE              = 'T_NEGATE';
    const T_PLUS                = 'T_PLUS';
    const T_OPEN_CURLY_BRACE    = 'T_OPEN_CURLY_BRACE';
    const T_CLOSE_CURLY_BRACE   = 'T_CLOSE_CURLY_BRACE';

    const T_COMMENT             = 'T_COMMENT';
    const T_IDENTIFIER          = 'T_IDENTIFIER'; // ?
    const T_DECLARE             = 'T_DECLARE';
    const T_NAMESPACE           = 'T_NAMESPACE';
    const T_USE                 = 'T_USE';

    public function __construct()
    {
    }

    public function setInput($input)
    {
        $this->input = $input;
        $this->tokens = [];
        $this->reset();
        $this->scan($input);
    }

    public function reset()
    {
        $this->lookahead = null;
        $this->token = null;
        $this->peek = 0;
        $this->position = 0;
    }

    protected function scan($input)
    {
        $regex = '~
             (//\s*([^\r\n]+))
            |((use)\s*([^\r\n]+))
            |(([a-z][a-z0-9_]+)\s*=\s*(.+))
            |((if|else|elseif)\s*(.*)\s*(:))
            |((\s{4})([a-z][a-z0-9_]+)\s*(.+))
            |(\s+|(.)) # non-catchable
        ~xi';

        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $matches = preg_split($regex, $input, -1, $flags);
        // prd($matches);

        foreach ($matches as $match) {
            // Must remain before 'value' assignment since it can change content
            $type = $this->getType($match[0]);
            $this->tokens[] = array(
                'value' => $match[0],
                'type'  => $type,
                'lenght' => strlen($match[0]),
                'position' => $match[1],
            );
        }
    }

    protected function getType(&$value)
    {
        $type = self::T_NONE;

        // Recognizing numeric values
        if (is_numeric($value)) {
            return self::T_NUMBER;
        }

        // Differentiate between quoted names, identifiers, input parameters and symbols
        if ($value[0] == "'") {
            $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));
            return self::T_STRING;
        } else if ($value == '//') {
            return self::T_COMMENT;
        } else if (preg_match('~^([a-z][a-z0-9_]+)$~', $value)) {
            $name = 'self::T_' . strtoupper($value);
            if (defined($name)) {
                return constant($name);
            }
            return self::T_IDENTIFIER;
        } elseif ($value[0] == "\r" || $value[0] == "\n") {
            $value = "\n";
            return self::T_EOL;
        } else {
            switch ($value) {
                case '.': return self::T_DOT;
                case ',': return self::T_COMMA;
                case '(': return self::T_PAREN_OPENTHESIS;
                case ')': return self::T_PAREN_CLOSETHESIS;
                case '=': return self::T_EQUALS;
                case '>': return self::T_GREATER_THAN;
                case '<': return self::T_LOWER_THAN;
                case '+': return self::T_PLUS;
                case '-': return self::T_MINUS;
                case '*': return self::T_MULTIPLY;
                case '/': return self::T_DIVIDE;
                case '!': return self::T_NEGATE;
                case '{': return self::T_OPEN_CURLY_BRACE;
                case '}': return self::T_CLOSE_CURLY_BRACE;
                default:
                    // Do nothing
                    break;
            }
        }

        return $type;
    }
}
