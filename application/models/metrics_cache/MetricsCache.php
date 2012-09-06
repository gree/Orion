<?php

require_once(APPPATH . 'models/baseentity.php');

class MetricsCache extends BaseEntity {

    public static $_db_fields = array(
        "metric_name"   => array("string", "none", false)
    );

    public $metric_name;
}