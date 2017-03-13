<?php
declare(strict_types=1); namespace Poffee;

final class Lexer
{
    private $tokens = [];

    public function __construct()
    {}

    public function setInput($line, $content)
    {
        $this->reset();
        $this->line = $line;
        $this->tokens = array_merge((array) $this->tokens, $this->doScan($content));
    }

    public function reset()
    {
        $this->tokensIndex = 0;
    }

    protected function doScan($input)
    {
        // $pattern = '~
        //      ((\s+)?//\s*(?:[^\r\n]+))                # comment
        //     |((\s+)?(use)\s*([^\r\n]+))               # use
        //     |((\s+)?([a-z][a-z0-9_]+)\s*=\s*(.+))     # var
        //     |((\s+)?(if|else|elseif)\s*(.*)\s*(:))    # condition
        //     # |((\s+)?\s+|(.))                          # non-catchable
        // ~xi';
        $pattern = '~
             (?:(\s+)?//\s*([^\r\n]+))
            |(?:(\s+)?(use)\s*([^\r\n]+))
            |(?:(\s+)?(if|else|elseif)\s*(.+:))
        ~ix';

        $matches = preg_split($pattern, $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);
        // pre($matches);

        $tokens = [];
        foreach ($matches as $i => $match) {
            $indent = 0;
            if ($match[0][0] == ' ') {
                $indent = strlen($match[0]);
            }
            $value  = $match[0];
            $length = strlen($value);
            $start  = $match[1];
            $end    = $start + $length;
            $tokens[] = [
                'type'     => $this->getType($value),
                'value'    => $value,
                'length'   => $length,
                'start'    => $start,
                'end'      => $end,
                'indent'   => $indent,
            ];
        }

        $s = '';
        foreach ($tokens as $i => $token) {
            switch ($token['type']) {
                case 'T_INDENT':
                    $s .= $token['value'];
                    break;
                case 'T_USE':
                    $this->renderUse($tokens[$i + 1]['value']);
                    break;
            }
        }
        // prd($s);

        return $tokens;
    }

    public function getType(&$value)
    {
        $type = 'T_NONE';

        if ($value == '    ') {
            return 'T_INDENT';
        } elseif ($value == "\n") {
            return 'T_EOL';
        } else {
            switch ($value) {
                case 'use': return 'T_USE'; break;
            }
        }

        return $type;
    }

    public function renderUse($value)
    {
        var_dump($value);
        return str_replace('.', '->', $value);
    }
}
