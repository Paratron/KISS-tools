<?php
/**
 * CurlInterface
 * ================
 * A classic interface to the curl methods.
 *
 * @author: Christian Engel <hello@wearekiss.com> 
 * @version: 1 01.03.14
 */

namespace Kiss;

class CurlInterface {
    var $basePath = '';
    var $ch;
    var $jsonMode = FALSE;
    private $lastRequest = '';

    function __construct($basePath = ''){
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $this->basePath = $basePath;
    }

    function getLastRequest(){
        return $this->lastRequest;
    }

    /**
     * Will execute a request with the given method and URL (basepath prepended) and return the result.
     * @param {String} $method
     * @param {String} $url
     * @param {Array} [$params]
     * @return mixed
     */
    function request($method, $url, $params = NULL){
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $url = $this->basePath . $url;

        switch(strtolower($method)){
            case 'get':
                if($params){
                    $url .= '?' . http_build_query($params);
                    curl_setopt($this->ch, CURLOPT_POSTFIELDS, NULL);
                }
            default:
                if($params){
                    if($this->jsonMode){
                        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
                    } else {
                        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
        }

        curl_setopt($this->ch, CURLOPT_URL, $url);

        $this->lastRequest = $url;
        if($params){
            $this->lastRequest .= ' | ' . json_encode($params);
        }

        $result = curl_exec($this->ch);
        $jResult = json_decode($result, TRUE);

        if($jResult !== NULL){
            return $jResult;
        }

        return $result;
    }

    /**
     * Executes a GET request to the given URL.
     * @param {String} $url basePath will be prefixed
     * @param {Array} [$params]
     * @return mixed
     */
    function get($url, $params = NULL){
        return $this->request('get', $url, $params);
    }

    function post($url, $params = NULL){
        return $this->request('post', $url, $params);
    }

    function put($url, $params = NULL){
        return $this->request('put', $url, $params);
    }

    function delete($url, $params = NULL){
        return $this->request('delete', $url, $params);
    }
}
 