<?php

require_once(dirname(__FILE__) . '/Metric.php');
require_once(APPPATH . 'models/basemodel.php');

class MetricModel extends BaseModel {

    protected $table_name = 'metric';

    function create() {
        $obj = new Metric();
        return $obj;
    }

    function get_by_id($id){
        debug(__FILE__, "get_by_id() is called for MetricModel");

        $metrics = self::get(array('id' => $id));
        if (empty($metrics)){
            return null;
        }else{
            return $metrics[0];
        }
    }
}