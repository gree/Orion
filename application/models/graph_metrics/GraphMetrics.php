<?php

require_once(APPPATH . 'models/baseentity.php');

class GraphMetrics extends BaseEntity {

    public static $_db_fields = array(
        "graph_id"      => array("int", "none", false),
        "metric_id"     => array("int", "none", false),
        "graph_order"   => array("int", "none", false)
    );

    public $graph_id;
    public $metric_id;
    public $graph_order;
}