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
        debug(__FILE__, "CACHE_REPOPULATE: Starting cache repopulate");
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
        //debug(__FILE__, print_r($initial_set,true));

        // todo: is this even necessary? this check looks to be performed in graphitemodel.php
        // foreach($initial_set as $path){
        //     foreach($this->orion_config['UNWANTED_METRIC_STRINGS'] as $unwanted_string) {
        //         if(strpos($path, $unwanted_string) !== FALSE){
        //             debug(__FILE__, "CACHE_REPOPULATE: Skipping " . $path . " because it contains " . $unwanted_string);
        //             continue 2;
        //         }
        //     }

        //     array_push($all_metric_array, $path);
        // };
        debug(__FILE__, "CACHE_REPOPULATE: all_metrics:");
        debug(__FILE__, "CACHE_REPOPULATE: found " . count($initial_set) . " metrics in graphite");
        self::update_mysql_table($initial_set);
        echo "REPOPULATION IS COMPLETE<br/>";

    }

    private function update_mysql_table($all_metric_array)
    {
        debug(__FILE__, "CACHE_REPOPULATE: Starting update_mysql_table");

        debug(__FILE__, "CACHE_REPOPULATE: querying existing cached metrics");
        $old_metrics = $this->MetricsCacheModel->get_metrics(false);

        debug(__FILE__, "CACHE_REPOPULATE: Create an associative array for old metrics = set all to false");
        $mysql_metrics_list = array();
        foreach ($old_metrics as $metric_name) {
            $mysql_metrics_list[$metric_name] = false;
        }

        debug(__FILE__, "CACHE_REPOPULATE: Build an in-memory hash set for the recently queried graphite metrics");
        $graphite_metric_array = array();
        foreach ($all_metric_array as $metric_name) {
            $graphite_metric_array[$metric_name] = 0;
        }

        debug(__FILE__, "CACHE_REPOPULATE: For every existing metric, check for existance in current graphite metric set, if found, remove from remove-list");
        foreach ($mysql_metrics_list as $metric_name => $value) {
            // why do an array search? both arrays are associatives arrays (i.e. hash sets)
            // answer: they're not both associative arrays
            // solution, let's build a hash set
            // $key = array_search($metric_name, $all_metric_array);
            // if ($key === 0 || $key) {
            //     unset($mysql_metrics_list[$metric_name]);
            //     unset($all_metric_array[$key]);
            // }

            if (isset($graphite_metric_array[$metric_name])) {
                unset($mysql_metrics_list[$metric_name]);
                unset($graphite_metric_array[$metric_name]);
            }
            
        }

        $metrics_to_delete = array_keys($mysql_metrics_list);

        debug(__FILE__, "CACHE_REPOPULATE: Deleting old metrics");
        if (!empty($metrics_to_delete)){
            $this->db->where_in('metric_name', $metrics_to_delete);
            $this->db->delete('metrics_cache');
        }

        debug(__FILE__, "CACHE_REPOPULATE: Iterating over metrics to add (should print every 1000)");
        $count = 0;
        $data = array();
        foreach($graphite_metric_array as $metric_name => $value){
            $data[] = array('metric_name'=> $metric_name);

            $count++;
            if ( ($count % 1000) == 0){
                debug(__FILE__, "--> inserting batch, count : " . $count);
                $this->db->insert_batch('metrics_cache', $data);

                $data = array();
                $count = 0;
            }
        }

        debug(__FILE__, "CACHE_REPOPULATE: Inserting new metrics into metric cache");
        if($count >= 1){
            $this->db->insert_batch('metrics_cache', $data);
        }
        echo "ADDED " . count($graphite_metric_array) . " NEW METRICS<br/>";
        echo "REMOVED " . count($metrics_to_delete) . " DEPRECATED METRICS<br/>";
    }
}