<?php
if (!defined('general_helper')) {
    define('general_helper', TRUE);

    function ends_with($str,$test){
        if ($str == '' || strlen($test) > strlen($str)){
            return false;
        }
        return substr_compare($str, $test, -strlen($test), strlen($test)) === 0;
    }

    function remove_from_front($str,$substr){
        if ( strlen($substr) == 0 ){
            return $str;
        }

        if (substr_compare($str, $substr, 0, strlen($substr)) === 0){
            return substr($str, strlen($substr));
        }else{
            return $str;
        }
    }
}
?>