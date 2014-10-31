<?php
/**
 * Inxmail
 * =======
 *
 *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 1 31.05.13
 */

namespace kiss;

class Inxmail {
    private $servlet;
    private $testDriveResult = NULL;
    private $listName;
    private $dumpFile = '';

    /**
     * Sets up a Hook to a new Inxmail Servlet.
     * @param {String} $servletName
     * @param string [$server=web.inxmail.com]
     */
    function __construct($servletName, $server = 'web.inxmail.com') {
        $this->servlet = 'http://' . $server . '/' . $servletName . '/subscription/servlet';
    }

    /**
     * @param $listName
     */
    function useList($listName) {
        $this->listName = $listName;
    }

    /**
     * Will dump all responses from Inxmail into the given file.
     * @param {String} $filename
     */
    function dumpResponse($filename){
        $this->dumpFile = $filename;
    }

    /**
     * Lets the send() method use a test-script instead of inxmail.
     * @param {String} $scriptURL The URL the data should be POSTed to.
     * @param {Bool} [$testDriveResult] Optional outcome definition of the test.
     */
    function testDrive($scriptURL, $testDriveResult = TRUE){
        $this->servlet = $scriptURL;
        $this->testDriveResult = $testDriveResult;
    }

    /**
     * After you selected a list, you can send data to Inxmail with this method.
     * Pass an array of values you want to send to Inxmail.
     * @param {array} $data Associative array with data to send to Inxmail.
     * @return {bool} Will return TRUE on a successful send; FALSE when Inxmail returned an error.
     */
    function send($data) {
        $c = curl_init($this->servlet);

        $data['INXMAIL_SUBSCRIPTION'] = $this->listName;
        $data['INXMAIL_HTTP_REDIRECT'] = 'http://example.com/success/';
        $data['INXMAIL_HTTP_REDIRECT_ERROR'] = 'http://example.com/error/';
        $data['INXMAIL_CHARSET'] = 'utf-8';

        curl_setopt($c, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($c, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($c, CURLOPT_POST, TRUE);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, FALSE);


        //curl_setopt($c, CURLOPT_PROXY, '127.0.0.1:8888');

        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_HEADER, TRUE);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
                                                 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                                                 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
                                                 'Accept-Encoding: gzip,deflate,sdch',
                                                 'Accept-Language: de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4',
                                                 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
                                                 'Connection: keep-alive',
                                                 'Origin: null',
                                                 'Cache-Control: max-age=0'
                                            ));


        $result = curl_exec($c);

        if($this->dumpFile){
            $f = fopen($this->dumpFile, 'a');
            fwrite($f, $result);
            fclose($f);
        }

        if($this->testDriveResult !== NULL){
            return $this->testDriveResult;
        }

        return stristr($result, 'http://example.com/error/') ? FALSE : TRUE;
    }
}
