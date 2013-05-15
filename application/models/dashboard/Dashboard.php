<?php

require_once(APPPATH . 'models/baseentity.php');

class Dashboard extends BaseEntity {

    public static $_db_fields = array(
        "id"                => array("int", "none", false),
        "category_id"       => array("int", "none", false),
        "dashboard_name"    => array("string", "none", false),
        "restricted"        => array("int", "none", false)
    );

    public $id;
    public $category_id;
    public $dashboard_name;
    public $restricted;
}