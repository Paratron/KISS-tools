<?
/**
 * This is an example of how to use the kTester class.
 */

 require('kTester/kTester.php');

 //At first, we create an instance of the class. Nothing magic here.
 $do = new kTester();

 //Then we start our whole testing case. The String we are passing in defines our test headline.
 $do->start('This is my example test group');



/**
 * Tests are done in groups.
 * If one test in the group fails, then the whole group is marked as failed.
 */
 $do->group('Testing group 1');

 /**
 * Now, we are doing a test.
 * A test consists of a title, and the result TRUE/FALSE.
 */
 $do->test('Example test title', TRUE);

 /*
 * You can also pass additional data to the test function, to display it on the result page:
 */
 $do->test('Test with additional data', TRUE, array('a', 'b'));

 /*
 * So, wait you say! The class isn't actually testing anything?
 * Well - at this point, its not. But you don't have to make the result up by yourself (altough you can).
 *
 * Let me introduce you to the condition evaluator:
 */

 $something = 2;
 $result = $do->eval->is($something, 1); //This will return FALSE, since 2 is not 1.

 //Now, lets put this together!
 $do->test('Testing if 2 is 1', $do->eval->is($something, 1));
 //As you can see: the line is readable, pretty much like a normal sentence.
 //The Testing function


 /*
  * Have a look at the output of this example script - successful groups are collapsed by default.
  */
 $do->group('Successful group - its collapsed');
 $do->test('Test 1', TRUE);
 $do->test('Test 2', TRUE);
 $do->test('Test 3', TRUE);