<?php
if (!defined('authenticate_helper')) {
    define('authenticate_helper', TRUE);

    function logout($redirect = true){

        $CI =& get_instance();
        $CI->load->library('session');

        $token = $CI->session->userdata('token');
        if ($token) {
            $CI->session->unset_userdata('token');
            $CI->session->unset_userdata('name');
            $CI->session->unset_userdata('user');
        }
        if ($redirect){
            redirect('orion');
        }else{
            return;
        }
    }
}
?>
