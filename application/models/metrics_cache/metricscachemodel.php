<?php

require_once(dirname(__FILE__) . '/MetricsCache.php');
require_once(APPPATH . 'models/basemodel.php');

class MetricsCacheModel extends BaseModel {

    protected $table_name = 'metrics_cache';

    function create() {
        $obj = new MetricsCache();
        return $obj;
    }

    function get_metrics($as_objects = true){
        debug(__FILE__, "get_metrics() is called for MetricsCacheModel");

        $metrics = self::get();

        if ($as_objects){
            return $metrics;
        }

        $metric_array = array();
        foreach ($metrics as $metric_obj){
            $metric_array[] = $metric_obj->metric_name;
        }

        return $metric_array;
    }
}