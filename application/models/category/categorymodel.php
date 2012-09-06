<?php

require_once(dirname(__FILE__) . '/Category.php');
require_once(APPPATH . 'models/basemodel.php');

class CategoryModel extends BaseModel {

    protected $table_name = 'category';

    function create() {
        $obj = new Category();
        return $obj;
    }

    function get_all_categories(){
        debug(__FILE__, "get_all_categories() is called for CategoryModel");

        return self::get();
    }

    function get_by_id($id){
        debug(__FILE__, "get_by_id() is called for CategoryModel");

        $categories = self::get(array('id' => $id));
        if (empty($categories)){
            return null;
        }else{
            return $categories[0];
        }
    }

    function get_by_name($category_name){
        debug(__FILE__, "get_by_name() is called for CategoryModel");

        $categories = self::get(array('category_name' => $category_name));
        if (empty($categories)){
            return null;
        }else{
            return $categories[0];
        }
    }
}