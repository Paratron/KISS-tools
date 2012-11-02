<?php

require '../kTester/kTester.php';
require '../kUtils.php';

$do = new kTester('../kTester/kTester.css');
$u = new kUtils();

$do->start('kUtils Testing');

//Testing the array_map function.
$do->group('array_map()');


//Mapping a bool
$result = $u->array_map(
    array('input_1' => 'true'),
    array('input_1' => 'bool')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be TRUE', $do->eval->is_exact($result['input_1'], TRUE));


//Mapping a bool
$result = $u->array_map(
    array('input_1' => 0),
    array('input_1' => 'bool')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be FALSE', $do->eval->is_exact($result['input_1'], FALSE));


//Mapping a bool
$result = $u->array_map(
    array('input_1' => 'what?'),
    array('input_1' => 'bool')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be FALSE', $do->eval->is_exact($result['input_1'], FALSE));


//Mapping a string
$result = $u->array_map(
    array('input_1' => 'Hello World!'),
    array('input_1' => 'string')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be a the String "Hello World!"', $do->eval->is_exact($result['input_1'], 'Hello World!'));

//Mapping and trimming a string.
$result = $u->array_map(
    array('input_1' => '  Hello World!  '),
    array('input_1' => 'string|trim')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be a the String "Hello World!"', $do->eval->is_exact($result['input_1'], 'Hello World!'));

//Mapping an array to string
$result = $u->array_map(
    array('input_1' => array(1,2,3)),
    array('input_1' => 'string')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "Array"', $do->eval->is_exact($result['input_1'], 'Array'));

//Mapping a number to string
$result = $u->array_map(
    array('input_1' => 123.222),
    array('input_1' => 'string')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "Array"', $do->eval->is_exact($result['input_1'], '123.222'));


//Mapping a Number
$result = $u->array_map(
    array('input_1' => 123),
    array('input_1' => 'integer')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 123));


//Mapping a Decimal
$result = $u->array_map(
    array('input_1' => 123.123),
    array('input_1' => 'integer')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 123));


//Mapping a String
$result = $u->array_map(
    array('input_1' => "123"),
    array('input_1' => 'integer')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 123));

//Mapping a not-number String
$result = $u->array_map(
    array('input_1' => "Test"),
    array('input_1' => 'integer')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 0));


//Mapping a Number intro a Range
$result = $u->array_map(
    array('input_1' => 50),
    array('input_1' => 'integer|range|0,100')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 50));


//Mapping a Number intro a Range (outside the range)
$result = $u->array_map(
    array('input_1' => 200),
    array('input_1' => 'integer|range|0,100')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be 123', $do->eval->is_exact($result['input_1'], 100));

//Mapping a URL
$result = $u->array_map(
    array('input_1' => 'http://google.com'),
    array('input_1' => 'url')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "http://google.com"', $do->eval->is($result['input_1'], 'http://google.com'));


//Mapping a complex URL
$result = $u->array_map(
    array('input_1' => 'ftp://subdomain.example.com/I/am/a/script.php?what=the&hell#hashtag'),
    array('input_1' => 'url')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "ftp://subdomain.example.com/I/am/a/script.php?what=the&hell#hashtag"', $do->eval->is($result['input_1'], 'ftp://subdomain.example.com/I/am/a/script.php?what=the&hell#hashtag'));


//Mapping a  broken URL
$result = $u->array_map(
    array('input_1' => 'http://googlecom'),
    array('input_1' => 'url')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', is_null($result['input_1']));
$do->test('Result should be NULL', $do->eval->is($result['input_1'], NULL));


//Mapping a E-Mail
$result = $u->array_map(
    array('input_1' => 'test@example.com'),
    array('input_1' => 'email')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "test@example.com"', $do->eval->is($result['input_1'], 'test@example.com'));



//Mapping a stranger E-Mail
$result = $u->array_map(
    array('input_1' => 'I-am+a_test@example.com'),
    array('input_1' => 'email')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "I-am+a_test@example.com"', $do->eval->is($result['input_1'], 'I-am+a_test@example.com'));

//Mapping a broken E-Mail
$result = $u->array_map(
    array('input_1' => 'I am no mail @ whatever . domain!'),
    array('input_1' => 'email')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', is_null($result['input_1']));
$do->test('Result should be NULL', $do->eval->is_exact($result['input_1'], NULL));


//Mapping an array
$result = $u->array_map(
    array('input_1' => array(1,2,3)),
    array('input_1' => 'array')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be Array(1,2,3)', $do->eval->is($result['input_1'], Array(1,2,3)));


//Mapping an array and casting it to integer
$result = $u->array_map(
    array('input_1' => array(1,"honey",3)),
    array('input_1' => 'array|int')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be Array(1,2,3)', $do->eval->is($result['input_1'], Array(1,0,3)));


//Mapping an array which is no array
$result = $u->array_map(
    array('input_1' => "meep"),
    array('input_1' => 'array')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be Array("meep")', $do->eval->is_exact($result['input_1'], array('meep')));


//Mapping a regex
$result = $u->array_map(
    array('input_1' => "The Date was 21.10.2012 when we got there."),
    array('input_1' => 'regex|#\d\d\.\d{2}\.\d{4}#')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "21.10.2012"', $do->eval->is_exact($result['input_1'], '21.10.2012'));


//Mapping a regex and picking the month
$result = $u->array_map(
    array('input_1' => "The Date was 21.10.2012 when we got there."),
    array('input_1' => 'regex|#\d\d\.(\d{2})\.\d{4}#|1')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be "10"', $do->eval->is_exact($result['input_1'], '10'));


//Mapping a regex and extracting the groups
$result = $u->array_map(
    array('input_1' => "The Date was 21.10.2012 when we got there."),
    array('input_1' => 'regex|#(\d\d)\.(\d{2})\.(\d{4})#|extract')
);
$do->test('Result should be an Array', is_array($result));
$do->test('Result should contain key "input_1"', isset($result['input_1']));
$do->test('Result should be Array("21","10","2012")', $do->eval->is_exact($result['input_1'], array("21", "10", "2012")));


// ==============================================================================================

$do->group('template()');

$tpl = 'Hi, my name is {{first_name}} {{name}}';
$result = $u->template($tpl, array('first_name' => 'Chris', 'name' => 'Engel'));
$do->test('Result should be "Hi, my name is Chris Engel"', $do->eval->is($result, 'Hi, my name is Chris Engel'));


$result = $u->template($tpl, array('first_name' => 'Chris'));
$do->test('Result should be "Hi, my name is Chris "', $do->eval->is($result, 'Hi, my name is Chris '));