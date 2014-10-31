<?php

require '../kTester/kTester.php';
require '../kSendmail2.php';

$do = new kTester('../kTester/kTester.css');
$do->start('kSendmail2 Tests');

$do->group('Basics');
$sm2 = new kSendmail();
$do->test('Object should be instance of Sendmail', ($sm2 instanceof kSendmail));
$do->test('Sender should be empty', !$sm2->get_sender());

$e = null;
try{
    $sm2->set_sender('wrong address');
}
catch(Exception $e){

}
$do->test('Exception should be thrown because invalid sender address.', ($e->getMessage() == 'This is not an E-Mail: wrong address'));

$sm2->set_sender('mail@example.com');
$do->test('Sender address should be set to mail@example.com', ($sm2->get_sender() == 'mail@example.com'));


$sm2->set_sender('payments@coastalforge.com');
$sm2->set_smtp('ssl://smtp.gmail.com', 'payments@coastalforge.com', 't8guz2bkhurlj98ji');

$sm2->add_receiver('christian.engel@wearekiss.com');

$log = '';
$dta = '';

$sm2->set_mode_html(TRUE);

$sm2->add_attachment('testmail.html', 'somefile.html');

try{
    $sm2->send('Test', file_get_contents('testmail.html'));
}
catch(Exception $e){
    $dta = 'Error ' . $e->getCode() . ': ' . $e->getMessage();
}

$do->test('No Error!', !$dta, $dta);