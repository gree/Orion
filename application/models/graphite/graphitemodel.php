<?php

class GraphiteModel {
    const MINUTES = 'minutes';
    const HOURS = 'hours';
    const DAYS = 'days';
    private $api_url;
    private $orion_config;

    public function __construct(){
        $CI =& get_instance();
        $this->orion_config = $CI->orion_config;
    }

    public function get_api_url() {
        return $this->orion_config['GRAPHITE_API_URL'];
    }

    public function get_auth_context(){
        return $this->orion_config['GRAPHITE_CONTEXT'];
    }

    public function get_sub_metrics($base_metric_name){
        $url = $this->get_api_url() . 'metrics/find?format=completer&query=' . $base_metric_name . '.*';
        if (ENVIRONMENT == "production"){
            $json_result = @file_get_contents($url, null);
        }else{
            $json_result = file_get_contents($url, null, $this->get_auth_context());
        }
        if (!$json_result){
            return array();
        }
        $metrics = json_decode($json_result);

        $metric_paths = array();
        foreach($metrics->metrics as $metric){

            foreach($this->orion_config['UNWANTED_METRIC_STRINGS'] as $unwanted_string) {
                if(strpos($metric->path, $unwanted_string) !== FALSE){
                    debug(__FILE__, "Skipping " . $metric->path . " because it contains " . $unwanted_string);
                    continue 2;
                }
            }

            if ($metric->is_leaf){
                $metric_paths[] = $metric->path;
            }else{
                $sub_metrics = $this->get_sub_metrics(substr($metric->path,0,-1));
                $metric_paths = array_merge($metric_paths,$sub_metrics);
            }
        }

        return $metric_paths;
    }

    public function get_metrics_with_format($metric_format){
        $url = $this->get_api_url() . 'metrics/find?format=completer&query=' . $metric_format;
        if (ENVIRONMENT == "production"){
            $json_result = @file_get_contents($url, null);
        }else{
            $json_result = file_get_contents($url, null, $this->get_auth_context());
        }
        if (!$json_result){
            return array();
        }
        $metrics = json_decode($json_result);

        $metric_paths = array();
        foreach($metrics->metrics as $metric){

            foreach($this->orion_config['UNWANTED_METRIC_STRINGS'] as $unwanted_string) {
                if(strpos($metric->path, $unwanted_string) !== FALSE){
                    debug(__FILE__, "Skipping " . $metric->path . " because it contains " . $unwanted_string);
                    continue 2;
                }
            }

            if ($metric->is_leaf){
                $metric_paths[] = $metric->path;
            }else{
                $sub_metrics = $this->get_sub_metrics(substr($metric->path,0,-1));
                $metric_paths = array_merge($metric_paths,$sub_metrics);
            }
        }

        return $metric_paths;
    }

    public function get_details($metric_paths, $from, $until = array('0',GraphiteModel::HOURS), $function = null ){
        if (!is_array($metric_paths)){
            $metric_paths = array($metric_paths);
        }

        $url = $this->get_api_url() . 'render?rawData=true&format=json';

        if( $function == null ) {
            foreach ($metric_paths as $path){
                $url = $url . '&target=' . $path;
            }
        }
        else {
            $url = $url . '&target=' . $function . '(' . implode( ",", $metric_paths ) . ')';
        }

        if (!is_array($from)){
            $from = array($from,GraphiteModel::HOURS);
        }

        if (!is_array($until)){
            $until = array($until,GraphiteModel::HOURS);
        }

        $url = $url . '&from=-' . abs($from[0]) . $from[1];
        $url = $url . '&until=-' . abs($until[0]) . $until[1];
        $details = json_decode(file_get_contents($url, null, $this->get_auth_context()));

        return $details;
    }

    public function split_metric($metric_name){
        $metric = array();
        if ( $this->orion_config['METRIC_PREFIX'] != '' && $this->orion_config['METRIC_PREFIX'] != NULL){
            $metric_name = remove_from_front($metric_name, $this->orion_config['METRIC_PREFIX'] . ".");
        }

        $metric_config = $this->orion_config['METRIC_CONFIG'];
        $metric_name_segments = array();

        foreach ( $metric_config as $metric_segment ){
            $metric_name_segments[] = $metric_segment['name'];
        }
        
        $split = explode(".", $metric_name, count($metric_name_segments));

        $i = 0;
        foreach ($metric_name_segments as $segment){
            $metric[$segment] = $split[$i];
            $i++;
        }

        return $metric;
    }

}