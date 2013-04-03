<?php
if (!defined('googoauth2_authentication_helper')) {
    define('googoauth2_authentication_helper', TRUE);

    function auth_logout($redirect = true){

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

    function auth_login($input){

        $CI =& get_instance();

        $client = new apiClient();
        $client->setApplicationName($CI->orion_config['GOOGLE_OAUTH_APPLICATION_NAME']);
        $oauth2 = new apiOauth2Service($client);
        if ( array_key_exists( 'location', $input ) ){
            $client->setState($input['location']);
        }
        $authUrl = $client->createAuthUrl();
        redirect($authUrl);

    }

    function auth_callback($input){

        $CI =& get_instance();
        $CI->load->library('session');
        $CI->load->model('user/UserModel');

        $client = new apiClient();
        $client->setApplicationName($CI->orion_config['GOOGLE_OAUTH_APPLICATION_NAME']);
        $oauth2 = new apiOauth2Service($client);

        if (array_key_exists('code',$input)) {
            $client->authenticate();
            $CI->session->set_userdata(array('token' => $client->getAccessToken()));

            $user_info = $oauth2->userinfo->get();
            $CI->session->set_userdata(array('name' => $user_info['name']));

            // These fields are currently filtered through the PHP sanitize filters.
            // See http://www.php.net/manual/en/filter.filters.sanitize.php
            $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
            $user = $CI->UserModel->authenticate($email);
            $CI->session->set_userdata(array('user' => json_encode($user)));

            $redirect = urldecode($input['state']);
            if (!$redirect){
                $redirect = "orion";
            }
            redirect($redirect);
        }else if ( $input['error'] == 'access_denied' ){
            redirect('orion');
        }
        redirect('orion');
    }

    function auth_get_user(){

        $CI =& get_instance();
        $CI->load->library('session');
        $CI->load->model('user/UserModel');

        $client = new apiClient();
        $client->setApplicationName($CI->orion_config['GOOGLE_OAUTH_APPLICATION_NAME']);
        $oauth2 = new apiOauth2Service($client);
        $token = $CI->session->userdata('token');
        if ($token){
            $client->setAccessToken($token);
            try{
                $user_info = $oauth2->userinfo->get();
                $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
                $user = $CI->UserModel->authenticate($email);
            }catch(apiServiceException $e){
                $user = $CI->UserModel->create();
            }
        }else{
            $user = $CI->UserModel->create();
        }

        return $user;

    }

}
?>
