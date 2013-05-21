<?php
/**
 * AppError
 * ==========
 * The AppError class is used to throw application specific error objects.
 *  *
 * @author: Christian Engel <hello@wearekiss.com>
 * @version: 1 12.01.13
 */

namespace Kiss;

class AppError extends \ErrorException{
    private $data = NULL;

    function __construct($message, $data = NULL, $code = 0){
        parent::__construct($message, $code);
        $this->data = $data;
    }

    function getData(){
        return $this->data;
    }
}
