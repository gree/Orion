<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pcockwell
 * Date: 5/29/12
 * Time: 4:11 PM
 * To change this template use File | Settings | File Templates.
 */

class Cache extends CI_Controller {

    private $unwanted_strings;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('graphite/GraphiteModel');
        $this->load->model('metrics_cache/MetricsCacheModel');
        $this->load->helper('general');
    }

    public function repopulate(){
        debug(__FILE__, "in repopulate");
        $metric_base = ( $this->orion_config['METRIC_PREFIX'] == '' || ends_with($this->orion_config['METRIC_PREFIX'], '.') ) ? $this->orion_config['METRIC_PREFIX'] : $this->orion_config['METRIC_PREFIX'] . '.';

        $first = true;
        for ($i = 0; $i < count($this->orion_config['METRIC_CONFIG']) - 1; $i++){
            if ( !$first ){
                $metric_base = $metric_base . '.';
            }
            $metric_base = $metric_base . '*';
            $first = false;
        }

        $initial_set = $this->GraphiteModel->get_metrics_with_format($metric_base);
        $all_metric_array = array();

        foreach($initial_set as $path){
            foreach($this->orion_config['UNWANTED_METRIC_STRINGS'] as $unwanted_string) {
                if(strpos($path, $unwanted_string) !== FALSE){
                    debug(__FILE__, "Skipping " . $path . " because it contains " . $unwanted_string);
                    continue 2;
                }
            }

            array_push($all_metric_array, $path);
        };
        self::update_mysql_table($all_metric_array);
        echo "REPOPULATION IS COMPLETE<br/>";

    }

    private function update_mysql_table($all_metric_array)
    {

        $old_metrics = $this->MetricsCacheModel->get_metrics(false);

        $mysql_metrics_list = array();
        foreach ($old_metrics as $metric_name) {
            $mysql_metrics_list[$metric_name] = false;
        }

        foreach ($mysql_metrics_list as $metric => $value) {
            $key = array_search($metric, $all_metric_array);
            if ($key === 0 || $key) {
                unset($mysql_metrics_list[$metric]);
                unset($all_metric_array[$key]);
            }
        }

        $metrics_to_delete = array_keys($mysql_metrics_list);

        if (!empty($metrics_to_delete)){
            $this->db->where_in('metric_name', $metrics_to_delete);
            $this->db->delete('metrics_cache');
        }

        $count = 0;
        $data = array();
        foreach($all_metric_array as $metric){
            $data[] = array('metric_name'=> $metric);

            $count++;
            if ( ($count % 1000) == 0){
                $this->db->insert_batch('metrics_cache',$data);

                $data = array();
                $count = 0;
            }
        }

        if($count >= 1){
            $this->db->insert_batch('metrics_cache',$data);
        }
        echo "ADDED " . count($all_metric_array) . " NEW METRICS<br/>";
        echo "REMOVED " . count($metrics_to_delete) . " DEPRECATED METRICS<br/>";
    }
}