<?php

class Orion extends CI_Controller {
    private $user;
    private $data;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('graphite/GraphiteModel');
        $this->load->model('category/CategoryModel');
        $this->load->model('dashboard/DashboardModel');
        $this->load->model('graph/GraphModel');
        $this->load->model('metric/MetricModel');
        $this->load->model('dashboard_graphs/DashboardGraphsModel');
        $this->load->model('graph_metrics/GraphMetricsModel');
        $this->load->model('link/LinkModel');
        $this->load->model('metrics_cache/MetricsCacheModel');
        $this->load->helper('dashboard');
        $this->load->helper('general');

        $auth_method = strtolower($this->orion_config['AUTHENTICATION_METHOD']);
        $auth_helper = $auth_method . '_authentication';

        $this->load->helper($auth_helper);

        $this->user = auth_get_user();

        $this->data['METRIC_PREFIX'] = ( $this->orion_config['METRIC_PREFIX'] == '' || ends_with($this->orion_config['METRIC_PREFIX'], '.') ) ? $this->orion_config['METRIC_PREFIX'] : $this->orion_config['METRIC_PREFIX'] . '.';
        $this->data['METRIC_CONFIG'] = $this->orion_config['METRIC_CONFIG'];
        $this->data['APPLICATION_TITLE'] = $this->orion_config['APPLICATION_TITLE'];
    }

    function index($category_name = null, $dashboard_name = null) {

        //Access with /
        //or access with index.php/orion/index/category_name/dashboard_name
        //Optional: category_name via URL (if you want to display a specific dashboard
        //Optional: dashboard_name via URL (if you want to display a specific dashboard)

        $navigation = array();
        $links = array();

        $all_categories = $this->CategoryModel->get_all_categories();

        foreach ( $all_categories as $category ){
            $category_id = $category->id;
            $name = $category->category_name;

            $dashboards = $this->DashboardModel->get_dashboards_by_category_id($category_id);
            foreach ( $dashboards as $dashboard ){
                if (!$dashboard->restricted){
                    $navigation[$name][] = $dashboard;
                }else if ( $this->UserModel->has_permission($this->user, 'restricted') ){
                    $navigation[$name][] = $dashboard;
                }
            }

            if (array_key_exists($name,$navigation)){
                $links[$name] = $this->LinkModel->get_links_by_category_id($category_id);
            }
        }

        $location = "orion/index/";

        $this->data['navigation'] = $navigation;
        $this->data['links'] = $links;
        $this->data['dashboard_json'] = null;

        if ( $category_name != null && $dashboard_name != null ){
            $location = $location . $category_name . "/" . $dashboard_name;

            $dashboard = json_decode(get_all_dashboard_data(urldecode($dashboard_name), urldecode($category_name)));
            if (!$dashboard->restricted){
                $this->data['dashboard_json'] = $dashboard;
            }else if ( $this->UserModel->has_permission($this->user, 'restricted') ){
               $this->data['dashboard_json'] = $dashboard;
            }
        }
        $this->data['location'] = $location;

        $this->load->view('home', $this->data);
    }

    function get_dashboard_graphs(){

        //Access with index.php/orion/get_dashboard_graphs
        //Requires: dashboard via GET (name)
        //Requires: category via GET (name)
        //Optional: from via GET (to request a specific start time for the data)
        //Optional: until via GET (to request a specific end time for the data)

        $dashboard_name = $this->input->get('dashboard');
        $category_name = $this->input->get('category');

        $from = null;
        if ( $this->input->get('from') ){
            $from = $this->input->get('from');
        }

        $until = null;
        if ( $this->input->get('until') ){
            $until = $this->input->get('until');
        }

        $dashboard = get_all_dashboard_data($dashboard_name, $category_name, $from, $until);
        $dashboard_obj = json_decode($dashboard);
        if (!$dashboard_obj->restricted){
            $this->output->set_output($dashboard);
        }else if ( $this->UserModel->has_permission($this->user, 'restricted') ){
            $this->output->set_output($dashboard);
        }else{
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Permission denied."}');
        }
    }

    function get_latest_data(){

        //Access with index.php/orion/get_latest_data
        //Requires: metric_name via GET (name)
        //Requires: from via GET as a positive number of hours to go back
        //Requires: until via GET as a positive number of hours to go back


        $metric_name = $this->input->get('metric_name');
        $from = $this->input->get('from');
        $until = $this->input->get('until');

        $new_data = $this->GraphiteModel->get_details($metric_name, array($from, GraphiteModel::HOURS), array($until, GraphiteModel::HOURS));
        echo json_encode($new_data);
    }

    function view_metric(){
        $metric_name = $this->input->get('metric');
        $from = $this->input->get('from');
        $until = $this->input->get('until');

        $dashboard = $this::_setup_view_metric($metric_name, $from, $until);

        $navigation = array();
        $links = array();

        $all_categories = $this->CategoryModel->get_all_categories();

        foreach ( $all_categories as $category ){
            $category_id = $category->id;
            $name = $category->category_name;
            $dashboards = $this->DashboardModel->get_dashboards_by_category_id($category_id);
            foreach ( $dashboards as $nav_dashboard ){
                if (!$nav_dashboard->restricted){
                    $navigation[$name][] = $nav_dashboard;
                }else if ( $this->UserModel->has_permission($this->user, 'restricted') ){
                    $navigation[$name][] = $nav_dashboard;
                }
            }

            if (array_key_exists($name,$navigation)){
                $links[$name] = $this->LinkModel->get_links_by_category_id($category_id);
            }
        }

        $location = "orion/view_metric/?metric=" . $metric_name . "&from=" . $from . "&until=" . $until;
        $this->data['navigation'] = $navigation;
        $this->data['links'] = $links;
        $this->data['dashboard_json'] = $dashboard;
        $this->data['location'] = $location;

        $this->load->view('home', $this->data);
    }

    function embed() {
        $metric_name = $this->input->get('metric');
        $from = $this->input->get('from');
        $until = $this->input->get('until');
        $function = $this->input->get('function');

        $from_suffix = $this->input->get('from_suffix') ? $this->input->get('from_suffix') : GraphiteModel::HOURS ;
        $until_suffix = $this->input->get('until_suffix') ? $this->input->get('until_suffix') : GraphiteModel::HOURS ;


        $dashboard = $this::_setup_view_metric($metric_name, $from, $from_suffix, $until, $until_suffix, $function);

        $this->data['dashboard_json'] = $dashboard;
        $this->data['location'] = "orion/embed/?metric=" . $metric_name . "&from=" . $from . "&until=" . $until;

        $this->load->view('embed', $this->data);
    }

    private function _setup_view_metric($metric_name, $from, $from_suffix, $until, $until_suffix, $function = null){

        $metric_names = explode(',', $metric_name);

        $dashboard = $this->DashboardModel->create();
        $dashboard->dashboard_name = ''; 
        $dashboard->category_name = remove_from_front($metric_name,$this->orion_config['METRIC_PREFIX']);
        $dashboard->restricted = 0;

        $graph = $this->GraphModel->create();
        $graph->is_half_size = 0;
        $graph->graph_name = ''; 
        $graph->order = 0;

        $metrics = array();
        $datapoints = $this->GraphiteModel->get_details($metric_names, array($from, $from_suffix), array($until, $until_suffix), $function);

        $transformed_metric_names = $metric_names;
        if( $function ) {
            $metric_name = implode( ",", $metric_names );
            $transformed_metric_names = array( $metric_name );
        }

        $i = 0;
        foreach($transformed_metric_names as $metric_name) {
            $metric = $this->MetricModel->create();
            $metric->metric_name = $metric_name;
            $metric->display_name = $metric_name;
            $metric->from = $from;
            $metric->until = $until;
            $metric->other_period_offsets = array(0);
            $metric->order = 0;
            $metric->datapoints = flip_time_series($datapoints[$i++]->datapoints);
            $metrics[] = $metric;
        }
        $graph->metrics = $metrics;
        $graphs = get_formatted_graph_object($graph);
        $dashboard->graphs = $graphs;
        return $dashboard;
    }


    function create_dashboard($dashboard_id = null) {

        //Access with index.php/orion/create_dashboard/dashboard_id
        //Optional: dashboard_id via URL if you want to edit a specific dashboard

        if ( !$this->UserModel->has_permission($this->user, 'create') ){
            redirect('orion/index');
        }

        $location = "orion/create_dashboard/";

        $this->data['metric_array'] = json_encode($this->MetricsCacheModel->get_metrics(false));
        $this->data['location'] = $location;

        if ($dashboard_id == null){

            $this->load->view('create_dashboard', $this->data);
            return;

        }
        $location = $location . $dashboard_id;

        $dashboard = $this->DashboardModel->get_by_id($dashboard_id);

        $category = $this->CategoryModel->get_by_id($dashboard->category_id);

        $dashboard_json = get_dashboard_json($dashboard->dashboard_name, $category->category_name);

        $this->data['dashboard_json'] = $dashboard_json;
        $this->data['location'] = $location;

        $this->load->view('create_dashboard', $this->data);
    }

    function delete_dashboard() {

        //Access with index.php/orion/delete_dashboard
        //Optional: dashboard_id via POST if you want to delete a dashboard

        if ( !$this->UserModel->has_permission($this->user, 'delete') ){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Permission denied."}');
            return;
        }

        $dashboard_id = $this->input->post('dashboard_id');

        if ($dashboard_id != null){

            if (!$this->DashboardModel->delete_dashboard($dashboard_id)){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result":false, "error":"Error while deleting dashboard."}');
            }else{
                $this->output->set_output('{"success":true}');
            }

        } else {
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"No ID specified."}');
        }

    }

    function save_dashboard(){

        //Access with index.php/orion/save_dashboard
        //Requires: dashboard_json sent via POST
        //Optional: dashboard_id via POST (if the dashboard already exists)

        $dashboard_id = $this->input->post('dashboard_id');
        $dashboard_obj = json_decode($this->input->post('dashboard_json'));
        $dot_star = $dashboard_obj->hasCatchAll;

        $dashboard = $this->DashboardModel->create();

        if ($dashboard_id){

            if ( !$this->UserModel->has_permission($this->user, 'update') ){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result":false, "error":"Permission denied."}');
                return;
            }
            $dashboard->id = $dashboard_id;
        }else if ( !$this->UserModel->has_permission($this->user, 'create') ){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result":false, "error":"Permission denied."}');
            return;
        }

        $dashboard->dashboard_name = $dashboard_obj->dashboard_name;
        $dashboard->restricted = $dashboard_obj->restricted;
        $category = $this->CategoryModel->get_by_name($dashboard_obj->category_name);

        if ($category == null){
            $category = $this->CategoryModel->create();
            $category->category_name = $dashboard_obj->category_name;
            $this->CategoryModel->save($category);
            $category->id = $this->CategoryModel->last_insert_id();
        }

        $dashboard->category_id = $category->id;

        $result = $this->DashboardModel->save($dashboard);
        if ($result == false){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result": ' . $result . ', "error":"Error while saving dashboard."}');
            return;
        }

        if (!$dashboard_id){
            $dashboard->id = $this->DashboardModel->last_insert_id();
        }

        $graph_objs = array();

        foreach ($dashboard_obj->graphs as $graph_obj){
            $graph = $this->GraphModel->create();
            if (isset($graph_obj->id)){
                $graph->id = $graph_obj->id;
            }

            $metric_objs = array();
            $graph->graph_name = $graph_obj->graph_name;
            $graph->is_half_size = $graph_obj->is_half_size;
            $result = $this->GraphModel->save($graph);
            if ($result == false){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result": ' . $result . ', "error":"Error while saving graph(s)."}');
                return;
            }

            if (!isset($graph->id)){
                $graph->id = $this->GraphModel->last_insert_id();
            }

            $graph->dashboard_order = $graph_obj->order;

            $graph_objs[] = $graph;
            if ($dot_star && count($graph_objs) > 1){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result": false, "error":"Too many graphs"}');
                return;
            }

            foreach($graph_obj->metrics as $metric_obj){
                $metric = $this->MetricModel->create();

                if (isset($metric_obj->id)){
                    $metric->id = $metric_obj->id;
                }

                $metric->metric_name = $metric_obj->metric_name;

                if (!$dot_star && strpos($metric->metric_name,'*') !== false ){
                    $this->output->set_status_header('500');
                    $this->output->set_output('{"result": false, "error":".* metric not expected. Exiting."}');
                    return;
                }else if ($dot_star && strpos($metric->metric_name,'*') === false){
                    $this->output->set_status_header('500');
                    $this->output->set_output('{"result": false, "error":"Graph must have either all .* metrics or none"}');
                    return;
                }

                if ($dot_star){
                    $metric_config = $this->orion_config['METRIC_CONFIG'];
                    $split_metric = $this->GraphiteModel->split_metric($metric->metric_name);

                    $wildcard_found = false;
                    foreach ( $metric_config as $index => $metric_segment ){
                        $contains_wildcard = strpos($split_metric[$metric_segment['name']],'*') !== false;

                        if ($contains_wildcard){
                            $wildcard_found = true;
                        }

                        if ($index == count($metric_config) - 1 && !$wildcard_found){
                            $this->output->set_status_header('500');
                            $this->output->set_output('{"result": false, "error":"One segment of the metric must contain a wildcard on a wildcard graph."}');
                            return;
                        }else if ( $contains_wildcard && !$metric_segment['allows_wildcard'] && $index != count($metric_config) - 1){
                            $this->output->set_status_header('500');
                            $this->output->set_output('{"result": false, "error": "' . $metric_segment['name'] . ' does not allow wildcard options"}');
                            return;
                        }
                    }
                }

                $metric->start_from = $metric_obj->from;
                $metric->until = $metric_obj->until;
                $metric->other_period_offsets = $metric_obj->other_period_offsets;

                if ($dot_star && count($metric->other_period_offsets) != 1 && $metric->other_period_offsets[0] != 0){
                    $this->output->set_status_header('500');
                    $this->output->set_output('{"result": false, "error":"No offsets are permitted for .* metrics"}');
                    return;
                }

                $result = $this->MetricModel->save($metric);
                if ($result == false){
                    $this->output->set_status_header('500');
                    $this->output->set_output('{"result": ' . $result . ', "error":"Error while saving metric(s)"}');
                    return;
                }

                if (!isset($metric->id)){
                    $metric->id = $this->MetricModel->last_insert_id();
                }

                $metric->graph_order = 0;
                if (isset($metric_obj->order)) {
                    $metric->graph_order = $metric_obj->order;
                }

                $metric_objs[] = $metric;
            }

            $result = $this->GraphMetricsModel->replace_metrics_for_graph($graph->id, $metric_objs);
            if ($result == false){
                $this->output->set_status_header('500');
                $this->output->set_output('{"result": ' . $result . ', "error":"Error while saving Graph to Metric relationships"}');
                return;
            }

        }

        $result = $this->DashboardGraphsModel->replace_graphs_for_dashboard($dashboard->id, $graph_objs);
        if ($result == false){
            $this->output->set_status_header('500');
            $this->output->set_output('{"result": ' . $result . ', "error":"Error while saving Dashboard to Graph relationships"}');
            return;
        }

        $dashboard_json = get_dashboard_json($dashboard->dashboard_name, $category->category_name);
        $this->output->set_output(json_encode($dashboard_json));

    }

    function users () {

        //Access with index.php/orion/users

        if ( !$this->UserModel->has_permission($this->user, 'delete') ){
            redirect('orion/index');
        }

        if ( $this->input->post('user') ){
            $user = json_decode($this->input->post('user'));

            //Cast user to a user object so we can save
            $user_obj = $this->UserModel->create();
            foreach ($user_obj as $field_name => $value){
                $user_obj->$field_name = $user->$field_name;
            }

            //Save and return user on success, or error message on failure
            if ( $this->UserModel->save($user_obj) ){
                $this->output->set_output(json_encode($this->UserModel->get_user_by_email($user_obj->email)));
            }else{
                $this->output->set_status_header('500');
                $this->output->set_output('{"result":false, "error":"Error while saving user to the database"}');
            }

        } else {

            $location = "orion/users/";
            $this->data['location'] = $location;

            $users = $this->UserModel->get_all_users();
            $this->data['users'] = $users;

            $this->load->view('users', $this->data);
        }

    }

}
