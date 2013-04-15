<?php

require_once(APPPATH . 'models/baseentity.php');

class Graph extends BaseEntity {

    public static $_db_fields = array(
        "id"            => array("int", "none", false),
        "graph_name"    => array("string", "none", false),
        "is_half_size"  => array("int", "none", false)
    );

    public $id;
    public $graph_name;
    public $is_half_size;
}