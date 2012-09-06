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
        $this->load->helper('authenticate');
        session_start();
    }

    public function index(){

        $client = new apiClient();
        $client->setApplicationName($this->orion_config['GOOGLE_OAUTH_APPLICATION_NAME']);
        $oauth2 = new apiOauth2Service($client);

        $token = $this->session->userdata('token');
        if ($token) {
            $this->session->unset_userdata('token');
            $this->session->unset_userdata('name');
            $this->session->unset_userdata('user');
            redirect('orion');
        } else {
            if ( $this->input->get('location') ){
                $client->setState($this->input->get('location'));
            }
            $authUrl = $client->createAuthUrl();
        }

        redirect($authUrl);
    }

    public function logout(){
        logout(true);
    }

    public function googleoauth2callback(){
        $client = new apiClient();
        $client->setApplicationName($this->orion_config['GOOGLE_OAUTH_APPLICATION_NAME']);
        $oauth2 = new apiOauth2Service($client);

        if ($this->input->get('code')) {
            $client->authenticate();
            $this->session->set_userdata(array('token' => $client->getAccessToken()));

            $user_info = $oauth2->userinfo->get();
            $this->session->set_userdata(array('name' => $user_info['name']));

            // These fields are currently filtered through the PHP sanitize filters.
            // See http://www.php.net/manual/en/filter.filters.sanitize.php
            $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
            $user = $this->UserModel->authenticate($email);
            $this->session->set_userdata(array('user' => json_encode($user)));

            $redirect = urldecode($this->input->get('state'));
            if (!$redirect){
                $redirect = "orion";
            }
            redirect($redirect);
        }else if ( $this->input->get('error') == 'access_denied' ){
            redirect('orion');
        }
        redirect('orion');
    }

}
