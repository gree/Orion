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

	//TODO: pcockwell 
	//Needs to be removed when modular authentication is finished

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
