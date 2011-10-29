<?php


class kTester_tests{
    /**
     * Tests if $a is equal $b
     * @param mixed $a
     * @param mixed $b
     * @return boolean
     */
    function is($a, $b=TRUE){
        return array(($a == $b), $a.' should have been '.$b);
    }

    /**
     * Tests if $a is equal $b and equal in type.
     * @param mixed $a
     * @param mixed $b
     * @return boolean
     */
    function is_exact($a, $b=TRUE){
        return array(($a === $b), $a.' should have been exactly '.$b);
    }

    /**
     * Tests if $a is inequal $b.
     * @param mixed $a
     * @param bool $b
     * @return bool
     */
    function not($a, $b=FALSE){
        return array(($a != $b), $a.' should not have been '.$b);
    }

    /**
     * Tests if $a is inequal $b and inequal in type.
     * @param mixed $ab
     * @param bool $b
     * @return bool
     */
    function not_exact($a, $b=FALSE){
        return array(($a !== $b), $a.' should exactly not have been '.$b);
    }

    /**
     * Tests, if $a is lesser than $b;
     * @param mixed $a
     * @param mixed $b
     * @return boolean
     */
    function lt($a, $b){
        return array(($a < $b), $a.' should be less than '.$b);
    }

    /**
     * Tests, if $a is greater than $b;
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    function gt($a, $b){
        return array(($a > $b), $a.' should be greater than '.$b);
    }

    /**
     * Tests, if $a is less or equal $b
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    function lte($a, $b){
        return array(($a <= $b), $a.' should be less than or equal '.$b);
    }

    /**
     * Tests, if $a is greater or equal $b
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    function gte($a, $b){
        return array(($a >= $b), $a.' should be greater than or equal '.$b);
    }

    /**
     * Tests, if $needle is found inside $haystack;
     * @param mixed $needle
     * @param array $haystack
     * @return bool
     */
    function in($needle, $haystack){
        return array(in_array($needle, $haystack), $needle.' should be found inside '.print_r($haystack, TRUE));
    }

    /**
     * Tests, if $needle is not found inside $haystack.
     * @param $needle
     * @param $haystack
     * @return bool
     */
    function not_in($needle, $haystack){
        return array(!in_array($needle, $haystack), $needle.' should not be found in '.print_r($haystack, TRUE));
    }

    /**
     * Tests, if the number of elements in $in, or the string length of $in
     * @param array|string $in
     * @param integer $sum
     * @return bool
     */
    function length($in, $sum){
        if(is_array($in)) return array((count($in) == $sum), $in.' should have '.$sum.' elements (has '.count($in).')');
        if(is_string($in)) return array((strlen($in) == $sum), $in.' should have '.$sum.' characters (has '.strlen($in).')');
    }
}

/**
 * Klasse für einfaches Testing.
 * @autor Christian Engel <christian.engel@wearekiss.com>
 * @version 1
 */
class kTester {
    private $tests = array();
    private $tStart = 0;
    private $totalTests = 0;
    var $currentTest = 1;
    private $title = 'kTester - UnitTesting';

    private $totalGroups = 0;
    private $okGroups = 0;
    private $failGroups = 0;
    private $groupSuccess = NULL;

    var $eval;

    function __construct(){
        $this->eval = new kTester_tests();
    }

    function __destruct(){
        $this->render();
    }

    /**
     * Startet den Test Vorgang und setzt den test Titel.
     * @param string $title
     * @return void
     */
    function start($title){
        ob_start(); //Jede Ausgabe von Daten vermeiden. Wird später bei render() abgefangen.
        $this->title = $title;
        $this->tStart = microtime(TRUE);
    }

    /**
     * Startet eine neue Testgruppe.
     * @param string $groupname
     * @return void
     */
    function group($groupname){
        //Gruppen-Name, Bestandene Tests, Testdetails
        $this->totalGroups++;
        if($this->groupSuccess !== NULL){
            if(!count($this->tests[count($this->tests)-2][2])) $this->groupSuccess = FALSE;
            if($this->groupSuccess) $this->okGroups++; else $this->failGroups++;
        }
        $this->groupSuccess = TRUE;
        $this->tests[] = array(
            'group_title' =>$groupname,
            'ok_tests' => 0,
            'nearly_tests' => 0,
            'failed_tests' => 0,
            'tests' => array()); //Enthält die einzelnen Testergebnisse
    }

    /**
     * Testet, ob $condition TRUE oder FALSE ist. Wenn FALSE, ist der Test fehlgeschlagen.
     * @param string $title Name des Tests
     * @param bool|mixed $condition
     * @param mixed $expected (optional)
     * @return bool
     */
    function test($title, $evaluation, $additional_data = NULL){
        $this->currentTest++;
        $debug_out = ob_get_contents();
        ob_end_clean();

        if(is_bool($evaluation)) $evaluation = array($evaluation, 'no information avaliable');
        $this->tests[count($this->tests)-1]['tests'][] = array(
            'title' => $title,
            'passed' => $evaluation[0],
            'pass_info' => $evaluation[1],
            'is_optional' => FALSE,
            'additional_data' => $additional_data,
            'debug_data' => $debug_out);
        ob_start();
        return $evaluation[0];
    }

    /**
     * This adds an optional Test
     * @param $title
     * @param $condition
     * @param null $expected
     * @param bool $exact_compare
     * @return bool
     */
    function optest($title, $evaluation){
        if(is_bool($evaluation)) $evaluation = array($evaluation, 'no information avaliable');
        $this->tests[count($this->tests)-1]['tests'][] = array(
            'title' => $title,
            'passed' => $evaluation[0],
            'pass_info' => $evaluation[1],
            'is_optional' => TRUE);
        return $evaluation[0];
    }

    function count_all(){
        foreach($this->tests as $k=>$v){
            $groupsuccess = TRUE;
            $this->totalGroups++;
            foreach($v['tests'] as $w){
                $this->totalTests++;
                if($w['passed']){
                    $this->tests[$k]['ok_tests']++;
                } else {
                    if($w['is_optional']){
                        $this->tests[$k]['nearly_tests']++;
                    } else {
                        $this->tests[$k]['failed_tests']++;
                        $groupsuccess = FALSE;
                    }
                }
            }
            if($groupsuccess){
                $this->okGroups++;
            } else {
                $this->failGroups++;
            }
        }
    }

    /**
     * Rendert das Testergebnis in HTML
     * @return void
     */
    function render(){
        $this->count_all();
        $tcnt = 1;

        /*echo '<pre>';
        print_r($this->tests);
        echo '</pre>'; */

        ?><!DOCTYPE html>
    <html>
    <head>
        <title><?=$this->title?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <link href="lib/php/kTester/kTester.css" rel="stylesheet" type="text/css">
        <script>
            function toggle(table_id){
                var obj = document.getElementById(table_id);
                if(obj.style.display == 'none'){
                    obj.style.display = 'table';
                } else {
                    obj.style.display = 'none';
                }
            }
        </script>
    </head>
    <body>
        <h1><?=$this->title?></h1>
        <p>Total amount of <?=$this->totalTests?> tests run in <?=(microtime(TRUE)-$this->tStart)?> Seconds.</p>
        <p><?= $this->okGroups ?> of <?= $this->totalGroups ?> Groups are working well. This means there are <b><?=round((100 / $this->totalGroups) * $this->failGroups)?>%</b> of groups left to fix.</p>
        <? foreach($this->tests as $gruppe):
            $theid = uniqid();
            $groupDebug = array();
            ?>
            <div class="testgroup <? if(count($gruppe['tests']) == 0) echo 'empty'; else echo ($gruppe['ok_tests'] == count($gruppe['tests'])) ? 'passed' : (($gruppe['failed_tests'] == 0) ? 'nearly' : 'failed') ?>">
            <h2><?=$gruppe['group_title']?> (<?=$gruppe['ok_tests']+$gruppe['nearly_tests']?>/<?=count($gruppe['tests'])?>) <button onclick="toggle('<?=$theid?>')">Details</button></h2>
            <table style="display: <?= ($gruppe['ok_tests']+$gruppe['nearly_tests'] == count($gruppe['tests'])) ? 'none' : 'table' ?>;" id="<?=$theid?>">
                <? foreach($gruppe['tests'] as $test):
                    if($test['debug_data']) $groupDebug[] = array($tcnt, $test['debug_data']);
                ?>
                <tr class="<?= ($test['passed']) ? 'passed' : 'failed' ?><?= ($test['is_optional']) ? ' nearly' : '' ?>">
                    <td><?= $tcnt++.' - '.$test['title']?></td>
                    <td><?=($test['passed']) ? 'Passed' : 'Failed => '.$test['pass_info'] ?><?= ($test['is_optional']) ? ' (optional)' : '' ?><? if($test['additional_data']) echo '<br>'.$test['additional_data']?></td>
                </tr>
                <? endforeach; ?>
            </table>
            </div>
            <? if(count($groupDebug)): ?>
                <div class="testgroup debug">
                <table>
                <? foreach($groupDebug as $v): ?>
                <tr>
                    <td>Test <?=$v[0]?></td>
                    <td><pre><?=$v[1]?></pre></td>
                </tr>
                <? endforeach; ?>
            </table>
            </div>
            <? endif; ?>
        <? endforeach; ?>
    </body>
    </html><?
    }
}
