<?php
// @tmp
declare(strict_types=1); namespace Poffee;


const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_DECLARE = 'T_DECLARE';
const T_MODULE = 'T_MODULE';
const T_USE = 'T_USE';
const T_CLASS = 'T_CLASS';
const T_INTERFACE = 'T_INTERFACE';
const T_TRAIT = 'T_TRAIT';

const T_OPR = 'T_OPR';
const T_ASSIGN = 'T_ASSIGN';
const T_COMMENT = 'T_COMMENT', T_COMMENT_CONTENT = 'T_COMMENT_CONTENT';

const T_DOT = 'T_DOT';
const T_COMMA = 'T_COMMA';
const T_COLON = 'T_COLON';
const T_QUESTION = 'T_QUESTION';

const T_OBJECT = 'T_OBJECT';
const T_FUN = 'T_FUN';
const T_FUN_ANON = 'T_FUN_ANON';
const T_CONST = 'T_CONST';

const T_EXTENDS = 'T_EXTENDS';
const T_IMPLEMENTS = 'T_IMPLEMENTS';
const T_ABSTRACT = 'T_ABSTRACT';

const T_FINAL = 'T_FINAL';
const T_STATIC = 'T_STATIC';
const T_PUBLIC = 'T_PUBLIC';
const T_PRIVATE = 'T_PRIVATE';
const T_PROTECTED = 'T_PROTECTED';
const T_VAR = 'T_VAR';
const T_RETURN = 'T_RETURN';
const T_IF = 'T_IF';
const T_ELSE = 'T_ELSE';
const T_ELSEIF = 'T_ELSEIF';
const T_FOR = 'T_FOR';
const T_BREAK = 'T_BREAK', T_CONTINUE = 'T_CONTINUE';
const T_IS = 'T_IS';
const T_ISE = 'T_ISE';
const T_NOT = 'T_NOT';
const T_AND = 'T_AND';
const T_OR = 'T_OR';
const T_IN = 'T_IN';

const T_PRNT_BLOCK = 'T_PRNT_BLOCK';
const T_OPEN_PRNT = 'T_OPEN_PRNT';
const T_CLOSE_PRNT = 'T_CLOSE_PRNT';
const T_BRKT_BLOCK = 'T_BRKT_BLOCK';
const T_OPEN_BRKT = 'T_OPEN_BRKT';
const T_CLOSE_BRKT = 'T_CLOSE_BRKT';

const T_ID = 'T_ID';
const T_VAR_ID = 'T_VAR_ID';
const T_FUN_ID = 'T_FUN_ID';
const T_CONST_ID = 'T_CONST_ID';
const T_OBJECT_ID = 'T_OBJECT_ID';
const T_METHOD_ID = 'T_METHOD_ID';
const T_MODULE_ID = 'T_MODULE_ID';

const T_EXPR = 'T_EXPR';
const T_NOT_EXPR = 'T_NOT_EXPR';
const T_OPR_EXPR = 'T_OPR_EXPR';
const T_CALLABLE_CALL_EXPR = 'T_CALLABLE_CALL_EXPR';
const T_METHOD_CALL_EXPR = 'T_METHOD_CALL_EXPR';
const T_PROPERTY_EXPR = 'T_PROPERTY_EXPR';
const T_ARRAY_EXPR = 'T_ARRAY_EXPR';
const T_FUN_ARGS_EXPR = 'T_FUN_ARGS_EXPR';
const T_RETURN_EXPR = 'T_RETURN_EXPR';
const T_VAR_EXPR = 'T_VAR_EXPR';

const T_NULL = 'T_NULL';
const T_STRING = 'T_STRING';
const T_NUMBER = 'T_NUMBER';
const T_BOOL = 'T_BOOL';
const T_ARRAY = 'T_ARRAY';
const T_THIS = 'T_THIS';

// const T_FUN_CALL = 'T_FUN_CALL';
const T_FUN_RET_TYPE = 'T_FUN_RET_TYPE';

const T_PHP_TAG_OPEN = 'T_PHP_TAG_OPEN', T_PHP_TAG_CLOSE = 'T_PHP_TAG_CLOSE';

const T_REQUIRE = 'T_REQUIRE', T_REQUIRE_ONCE = 'T_REQUIRE_ONCE';
const T_INCLUDE = 'T_INCLUDE', T_INCLUDE_ONCE = 'T_INCLUDE_ONCE';

const C_EOL = PHP_EOL;
const C_ASSIGN = '=';
const C_COLON = ':';
const C_EXTENDS = '>';
const C_IMPLEMENTS = '>>';
const C_STATIC = 's';
const C_PRIVATE = '@';
const C_PROTECTED = '@@';
const C_INC = '++', C_DEC = '--';
const C_PHP_OPEN = '<?php';
const C_PHP_CLOSE = '?>';

const KEYWORDS = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break',
    'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default',
    'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif',
    'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach',
    'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof',
    'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
    'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw',
    'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield',
    'module', 'fun', 'in',
];
