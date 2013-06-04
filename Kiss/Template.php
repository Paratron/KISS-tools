<?php
/**
 * Template
 * ===========
 * Use this to load templates, serve them with variables and render their result.
 *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 1 23.05.13
 */

namespace Kiss;

class Template {
    private $basePath;
    private $templateSource;
    private $templateVariables = array();
    private $subTemplates = array();
    private $isPHP = FALSE;

    /**
     * Loads a given template and its included sub-templates.
     * Sub-templates will get the templates basepath prefixed.
     * @param {String} $filename
     */
    function __construct($filename) {
        if(!file_exists($filename)){
            throw new \ErrorException('Template "' . $filename . '" not found');
        }
        $this->basePath = dirname($filename) . '/';
        $this->templateSource = file_get_contents($filename);

        $this->isPHP = strtolower(substr($filename, -4)) === '.php';

        preg_match_all('#\[\[(.+?)\]\]#', $this->templateSource, $matches);

        foreach ($matches[1] as $v) {
            $this->subTemplates[$v] = new Template($this->basePath . $v);
        }
    }

    /**
     * Set the variables that should be placed inside the template.
     * @param {array} $data
     * @param {bool} [$flattened] Used in recursion
     */
    function setVariables($data) {
        $this->templateVariables = $data;
        foreach ($this->subTemplates as $t) {
            $t->setVariables($data);
        }
    }

    /**
     * Will render the template and return the result.
     * @return {string}
     */
    function render() {
        if ($this->isPHP) {
            $tmp = tempnam(sys_get_temp_dir(), 'inc.');
            file_put_contents($tmp, $this->templateSource);
            ob_start();
            $this->capsedRender($tmp, $this->templateVariables);
            $result = ob_get_contents();
            ob_end_clean();
        }
        else {
            $result = $this->templateSource;
        }

        $vars = $this->flattenArray($this->templateVariables);

        foreach ($vars as $k => $v) {
            if(strpos($result, '{{' . $k . '}}') !== FALSE){
                $result = str_replace('{{' . $k . '}}', $v, $result);
            }
        }

        foreach ($this->subTemplates as $k => $t) {
            $result = str_replace('[[' . $k . ']]', $t->render(), $result);
        }

        return $result;
    }

    private function capsedRender($tName, $vars){
        include $tName;
    }

    //=========================================

    /**
     * Will make a multi-dimensional array one-dimensional.
     * Items in sub-arrays will be accessable via dot syntax, like so:
     *
     *     $myArray['levelOne']['subItem']
     *
     * will become:
     *
     *     $myFlatArray['levelOne.subItem']
     *
     * ---------------------------------------------
     *
     *     $myArray['levelOne'][0]
     *
     * will become:
     *
     *     $myFlatArray['levelOne[0]']
     *
     * @param {array} $inArray
     * @param {string} [$prefix] Used in recursion
     * @return {array}
     */
    private function flattenArray($inArray, $prefix = '') {
        $return = array();

        foreach ($inArray as $k => $v) {
            if(is_integer($k)){
                $uk = '[' . $k . ']';
            } else {
                $uk = $k;
            }
            if (is_array($v)) {
                $r = $this->flattenArray($v, $prefix ? $prefix . (is_integer($k) ? '' : '.') . $uk : $uk);
                $return = array_merge($return, $r);
                continue;
            }
            $return[$prefix ? $prefix . (is_integer($k) ? '' : '.') . $uk : $uk] = $v;
        }

        return $return;
    }
}
