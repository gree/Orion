<?php

require_once(APPPATH . 'models/baseentity.php');

class Link extends BaseEntity {

    public static $_db_fields = array(
        "id"            => array("int", "none", false),
        "category_id"   => array("int", "none", false),
        "display"       => array("string", "none", false),
        "url"           => array("string", "none", false)
    );

    public $id;
    public $category_id;
    public $display;
    public $url;
}