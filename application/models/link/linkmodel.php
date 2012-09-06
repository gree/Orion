<?php

require_once(dirname(__FILE__) . '/Link.php');
require_once(APPPATH . 'models/basemodel.php');

class LinkModel extends BaseModel {

    protected $table_name = 'link';

    function create() {
        $obj = new Link();
        return $obj;
    }

    function get_all_links(){
        debug(__FILE__, "get_links() is called for LinkModel");

        return self::get();
    }

    function get_links_by_category_id($category_id){
        debug(__FILE__, "get_links_by_category_id() is called for LinkModel");

        return self::get(array('category_id' => $category_id));
    }

    function get_links_by_category_id_with_display($category_id, $display){
        debug(__FILE__, "get_links_by_category_id() is called for LinkModel");

        return self::get(array('category_id' => $category_id, "display" => $display));
    }

    function delete_link($id) {
        $result = $this->db->delete('link', array('id' => $id));
        return $result;
    }
}