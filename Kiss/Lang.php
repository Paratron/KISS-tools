<?php
/**
 * Lang
 * ==========
 * This class is used for inserting language strings in the UI.
 * If the requested string is not present, it tries to fall back into the default language.
 * If the string is not present there, the key is returned.
 *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 1 10.01.13
 */

namespace Kiss;

class Lang {
    private $lang_key = 'en';
    private $lang_default = 'en';
    private $view_name = '';

    public function set_lang($lang_name){
        $this->lang_key = $lang_name;
    }

    public function set_view($view_name){
        $this->view_name = $view_name;
    }

    /**
     * Tries to get a specific string from the language repository.
     * When you have set to a specific view before, you can simply fetch values like this:
     * "my_object.my_key", or directly "my_key".
     *
     * If you want to recieve something from a specific view, call "viewname:my_key".
     *
     * @param {String} $key The string selector.
     * @param {array} $data An associative array of strings to replace placeholders in the language string.
     * @return mixed|string
     */
    function get($key, $data = array()) {
        //Look if it targets a specific view.
        $p = explode(':', $key);
        if(count($p) == 2){
            //view has been selected
            $view_name = $p[0];
            $key = $p[1];
        } else {
            $key = $p[0];
            $view_name = $this->view_name;
        }
        $p = explode('.', $key);

        $result = '[' . $this->lang_key . '>' . $view_name . ':' . $key . ']';

        if (file_exists('lib/lang/' . $this->lang_key . '/' . $view_name . '.json')) {
            $file = 'lib/lang/' . $this->lang_key . '/' . $view_name . '.json';
        }
        else {
            if (file_exists('lib/lang/' . $this->lang_default . '/' . $view_name . '.json')) {
                $file = 'lib/lang/' . $this->lang_default . '/' . $view_name . '.json';
            }
            else {
                return $result;
            }
        }


        $scope = json_decode(file_get_contents($file), TRUE);
        while (count($p)) {
            $part = array_shift($p);
            if (!isset($scope[$part])) {
                break;
            }
            if(is_string($scope[$part])){
                $result = $scope[$part];
                break;
            }
            $scope = $scope[$part];
        }

        foreach($data as $k=>$v){
            $result = str_replace('{{' . $k . '}}', $v, $result);
        }

        return $result;
    }
}
