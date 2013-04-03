<?php

require_once(APPPATH . 'models/baseentity.php');

class Category extends BaseEntity {

    public static $_db_fields = array(
        "id"            => array("int", "none", false),
        "category_name" => array("string", "none", false)
    );

    public $id;
    public $category_name;
}