<?php

require_once(APPPATH . 'models/baseentity.php');

class Metric extends BaseEntity {

    public static $_db_fields = array(
        "id"                    => array("int", "none", false),
        "metric_name"           => array("string", "none", false),
        "start_from"            => array("int", "none", false),
        "until"                 => array("int", "none", false),
        "other_period_offsets"  => array("array", "none", false)
    );

    public $id;
    public $metric_name;
    public $start_from;
    public $until;
    public $other_period_offsets;
}