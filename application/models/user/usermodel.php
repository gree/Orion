<?php

require_once(dirname(__FILE__) . '/User.php');
require_once(APPPATH . 'models/basemodel.php');

class UserModel extends BaseModel {

    protected $table_name = 'user';

    public function __construct(){
        parent::__construct();
        $this->load->helper('authenticate');
    }

    function create() {
        $obj = new User();

		if ($this->orion_config['AUTHENTICATION_METHOD'] == 'NOAUTH'){
            $obj->perm_create = 1;
           	$obj->perm_read = 1;
       	    $obj->perm_update = 1;
   	        $obj->perm_delete = 1;
            $obj->perm_restricted = 1;			
		}else{
   	        $obj->perm_create = 0;
           	$obj->perm_read = 1;
       	    $obj->perm_update = 0;
   	        $obj->perm_delete = 0;
            $obj->perm_restricted = 0;
		}
        return $obj;
    }

    function authenticate($email){
        $user = array();
        if ($email != null ){
            $user = self::get(array('email' => $email));
        }

        if (empty($user)){
            $user = $this->create();
            $user->email = $email;

			if ($this->orion_config['AUTHENTICATION_METHOD'] != 'NOAUTH'){
        	    $email_split = explode("@",$email);
            	if ( empty($this->orion_config['ACCEPTED_DOMAIN_NAMES']) || in_array($email_split[1],$this->orion_config['ACCEPTED_DOMAIN_NAMES']) ){
        	        self::save($user);
                	$user->id = self::last_insert_id();
            	}else{
                	logout(false);
            	    show_error('Invalid domain name for user email. User not authorized', 401, 'Unauthorized');
            	}
			}
        }else{
            $user = $user[0];
        }

        return $user;
    }

    function has_permission($email, $permission){
        $permission = 'perm_'.$permission;
        $user = $this->authenticate($email);
        return $user->$permission == 1;
    }

    function get_all_users(){
        debug(__FILE__, "get_all_users() is called for UserModel");

        return self::get();
    }
}
