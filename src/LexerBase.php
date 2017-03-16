<?php
declare(strict_types=1); namespace Poffee;

const T_NONE = 'T_NONE'; // 0;
const T_EOL = 'T_EOL'; // -1;
const T_INDENT = 'T_INDENT'; // -2;
const T_SPACE = 'T_SPACE'; // -3;

const T_DECLARE = 'T_DECLARE', T_DECLARE_EXPR = 'T_DECLARE_EXPR';
const T_NAMESPACE = 'T_NAMESPACE', T_NAMESPACE_EXPR = 'T_NAMESPACE_EXPR';
const T_USE = 'T_USE', T_USE_EXPR = 'T_USE_EXPR';


const T_OPR = 'T_OPR';
const T_ASSIGN_OPR = 'T_ASSIGN_OPR';
const T_COMMENT_OPR = 'T_COMMENT_OPR', T_COMMENT_CONTENT = 'T_COMMENT_CONTENT';

const T_DOT = 'T_DOT';
const T_COMMA = 'T_COMMA';
const T_COLON = 'T_COLON';
const T_QUESTION = 'T_QUESTION';

const T_VAR = 'T_VAR';
const T_OBJECT = 'T_OBJECT';
const T_MODIFIER = 'T_MODIFIER';

const T_CONST = 'T_CONST';
const T_CLASS = 'T_CLASS';
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

abstract class LexerBase
{}
