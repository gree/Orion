<?php

require_once(dirname(__FILE__) . '/Dashboard.php');
require_once(APPPATH . 'models/basemodel.php');

class DashboardModel extends BaseModel {

    protected $table_name = 'dashboard';

    function create() {
        $obj = new Dashboard();
        return $obj;
    }

    function get_by_id($id){
        debug(__FILE__, "get_by_id() is called for DashboardModel");

        $dashboards = self::get(array('id' => $id));
        if (empty($dashboards)){
            return null;
        }else{
            return $dashboards[0];
        }
    }

    function get_dashboards_by_category_id($category_id){
        debug(__FILE__, "get_dashboards_by_category_id() is called for DashboardModel");

        return self::get(array('category_id' => $category_id));
    }

    function get_where($params){
        debug(__FILE__, "get_where() is called for DashboardModel");

        $dashboards = self::get($params);
        if (empty($dashboards)){
            return null;
        }elseif (count($dashboards) == 1){
            return $dashboards[0];
        }else{
            return $dashboards;
        }
    }

    function delete_dashboard($id) {
        $result = $this->db->delete('dashboard', array('id' => $id));
        return $result;
    }
}