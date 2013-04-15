<?php

require_once(APPPATH . 'models/baseentity.php');

class DashboardGraphs extends BaseEntity {

    public static $_db_fields = array(
        "dashboard_id"      => array("int", "none", false),
        "graph_id"          => array("int", "none", false),
        "dashboard_order"   => array("int", "none", false)
    );

    public $dashboard_id;
    public $graph_id;
    public $dashboard_order;
}