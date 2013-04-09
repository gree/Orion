<?php

class Links extends CI_Controller {
    private $user;
    private $data;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('link/LinkModel');
        $this->load->model('category/CategoryModel');
        $this->load->model('dashboard/DashboardModel');


        $auth_method = strtolower($this->orion_config['AUTHENTICATION_METHOD']);
        $auth_helper = $auth_method . '_authentication';

        $this->load->helper($auth_helper);

        $this->user = auth_get_user();

        $this->data['APPLICATION_TITLE'] = $this->orion_config['APPLICATION_TITLE'];

    }

    function index() {

        if ( !$this->UserModel->has_permission($this->user, 'create') ){
            redirect('orion/index');
            //show_error('Permission denied. User not authorized.', 401, 'Unauthorized');
        }

        $links = $this->LinkModel->get_all_links();

        $categories = $this->CategoryModel->get_all_categories();
        $active_categories = array();

        foreach ($categories as $category){
            if ( count($this->DashboardModel->get_dashboards_by_category_id($category->id)) >= 1){
                $active_categories[] = $category;
            }
        }

        $location = 'orion/links';
        $this->data['links'] = $links;
        $this->data['categories'] = $active_categories;
        $this->data['location'] = $location;
        $this->load->view('manage_links', $this->data);
        
    }

    function save_link() {

        //Access with index.php/links/save_link

        if ( !$this->UserModel->has_permission($this->user, 'update') ){
            //show_error('Permission denied. User not authorized.', 401, 'Unauthorized');
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Permission denied."}');
            return;
        }

        if (!$this->input->post()){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"No data was sent. Data must be sent as a POST request."}');
            return;
        }

        $link = new Link();

        if ($this->input->post('id')){
            $link->id = $this->input->post('id');
        }
        $link->category_id = $this->input->post('category_id');
        $link->display = $this->input->post('display');
        $link->url = $this->input->post('url');

        debug(__FILE__, print_r($link, true));

        if ( $link->id == null && count( $this->LinkModel->get_links_by_category_id_with_display($link->category_id, $link->display) ) >= 1 ){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"An link with that name already exists for that category"}');
            return;
        }

        if ($this->LinkModel->save($link)){
            if ( $link->id == null ){
                $link->id = $this->LinkModel->last_insert_id();
            }
            $this->output->set_output(json_encode($link));
        }else{
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Error while saving link."}');
        }
    }

    function delete_link() {

        //Access with index.php/links/delete_link
        //Optional: link_id via POST if you want to delete a dashboard

        if ( !$this->UserModel->has_permission($this->user, 'delete') ){
            //show_error('Permission denied. User not authorized.', 401, 'Unauthorized');
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Permission denied."}');
            return;
        }

        $link_id = $this->input->post('id');

        if ($link_id != null){

            if (!$this->LinkModel->delete_link($link_id)){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result":false, "error":"Error while deleting link."}');
            }else{
                $this->output->set_output('{"success":true}');
            }

        } else {
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"No ID specified."}');
        }

    }
}
