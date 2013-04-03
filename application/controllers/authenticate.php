<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pcockwell
 * Date: 5/29/12
 * Time: 4:11 PM
 * To change this template use File | Settings | File Templates.
 */

class Authenticate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $auth_method = strtolower($this->orion_config['AUTHENTICATION_METHOD']);
        $auth_helper = $auth_method . '_authentication';
        $this->load->helper($auth_helper);

        session_start();
    }

    public function index(){

        $token = $this->session->userdata('token');
        //If user logged in, log them out
        if ($token) {
            self::logout();
        }

        //Otherwise log them in
        self::login();

    }

    private function login(){

        auth_login($this->input->get());

    }

    public function logout(){
        auth_logout( true );
    }

    public function authenticate_callback(){
        auth_callback($this->input->get());
    }

}
