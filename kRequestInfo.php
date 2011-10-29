<?php
/**
 * Reads the HTTP Request and gathers some informations about the requester.
 * @version 1 12.10.2011
 * @autor Christian Engel <christian.engel@wearekiss.com>
 */
class kRequestInfo{
    /**
     * Tries to find out which operating system the user is running.
     * If no operating system could be determined, FALSE is returned.
     * @return string|FALSE
     */
    function os(){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') !== FALSE) return 'win';
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'Macintosh') !== FALSE) return 'osx';
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'Linux') !== FALSE) return 'linux';
        return FALSE;
    }

    /**
     * Returns an array with the preferred languages by the user.
     * The most important language is in index 0.
     * @param array $available (optional) Pass in here your available languages and the function will only return matches.
     * @param bool $find_best (optional) If set to TRUE, the first matched language will be returned as string.
     * @return array|string|FALSE
     */
    function preferred_language($available = NULL, $find_best = FALSE){
        if($available != NULL && !is_array($available)) return FALSE;

        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $out = array();
        foreach($langs as $k=>$v){
            $lang = substr($v, 0, strpos($v, ';'));
            if($available){
                if(!in_array($lang, $available)) continue;
            }
            if($find_best) return $lang;
            $out[] = $lang;
        }

        return $out;
    }

    /**
     * This looks if there is a signed request by facebook in the request header and returns the contents.
     * @return array|false
     */
    function signed_request(){
        if(!isset($_REQUEST['signed_request'])) return FALSE;
        $r = $_REQUEST['signed_request'];
        if(!$r) return FALSE;
        $parts = explode('.', $r);
		return json_decode(base64_decode($parts[1]), TRUE);
    }
}
