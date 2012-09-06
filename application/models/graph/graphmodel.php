<?php

require_once(dirname(__FILE__) . '/Graph.php');
require_once(APPPATH . 'models/basemodel.php');

class GraphModel extends BaseModel {

    protected $table_name = 'graph';

    function create() {
        $obj = new Graph();
        return $obj;
    }

    function get_by_id($id){
        debug(__FILE__, "get_by_id() is called for GraphModel");

        $graphs = self::get(array('id' => $id));
        if (empty($graphs)){
            return null;
        }else{
            return $graphs[0];
        }
    }
}