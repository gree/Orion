<?php
if (!defined('dashboard_helper')) {
    define('dashboard_helper', TRUE);

    function get_metrics(){
        $CI = & get_instance();
        $CI->load->model('metrics_cache/MetricsCacheModel');

        return $CI->MetricsCacheModel->get_metrics();
    }

    function get_all_dashboard_data($dashboard_name, $category_name, $from = null, $until = null){
        $dashboard_object = get_dashboard_json($dashboard_name, $category_name);
        $dashboard_payload = json_decode($dashboard_object['payload']);
        $graphs = $dashboard_payload->graphs;

        $is_dot_star = false;
        if (count($graphs) == 1){
            foreach($graphs[0]->metrics as $metric){
                if ( strpos($metric->metric_name, "*") !== false ){
                    $is_dot_star = true;
                }else if($is_dot_star){
                    //Do something cause we have a problem
                }
            }
        }

        if ($is_dot_star){
            return get_dot_star_data($dashboard_payload, $from, $until);
        }

        foreach ($graphs as $graph){

            get_all_graph_data($graph, $from, $until);

        }

        return json_encode($dashboard_payload);
    }

    function get_all_graph_data($graph, $from = null, $until = null){
        $CI = & get_instance();
        $CI->load->model('graphite/GraphiteModel');
        $CI->load->helper('general');

        if ( !property_exists($graph, "metrics") ){
            
            $CI->load->model('metric/MetricModel');
            $CI->load->model('graph_metrics/GraphMetricsModel');

            $graph_metric_objs = $CI->GraphMetricsModel->get_metrics_by_graph($graph->id);
            $graph->metrics = array();

            foreach ($graph_metric_objs as $graph_metric){
                $metric = $CI->MetricModel->get_by_id($graph_metric->metric_id);
                $metric->order = $graph_metric->graph_order;
                $metric->from = $metric->start_from;
                unset($metric->start_from);
                $graph->metrics[] = $metric;
            }
        }

        $other_period_metrics_array = array();

        foreach ($graph->metrics as $metric){

            $metric->display_name = $metric->metric_name;

            if ($from != null){
                $from = abs($from) * -1;
                $metric->from = $from;
            }
            if ($until != null){
                $until = abs($until) * -1;
                $metric->until = $until;
            }

            $original_series = $CI->GraphiteModel->get_details($metric->metric_name, array($metric->from, GraphiteModel::HOURS), array($metric->until, GraphiteModel::HOURS));
            $flip_series = flip_time_series($original_series[0]->datapoints);
            $metric->datapoints = $flip_series;

            $add_offset_metric = false;
            if (property_exists($metric, 'other_period_offsets')){
                if ( !is_array( $metric->other_period_offsets ) ){
                    $metric->other_period_offsets = array( $metric->other_period_offsets );
                }
                foreach ($metric->other_period_offsets as $offset){
                    if ($offset != 0){
                        $add_offset_metric = true;
                    }
                }
            }

            $index = 0;
            if ($add_offset_metric){
                while ($index < count($metric->other_period_offsets)){
                    if ($metric->other_period_offsets[$index] != 0){
                        $offset_metric = get_offset_metric($metric, $metric->other_period_offsets[$index]);
                        $original_series = $CI->GraphiteModel->get_details($offset_metric->metric_name, array($offset_metric->from, GraphiteModel::HOURS), array($offset_metric->until, GraphiteModel::HOURS));
                        $shifted_series = shift_time_series($offset_metric->offset, $original_series[0]->datapoints);
                        $flip_series = flip_time_series($shifted_series);
                        $offset_metric->datapoints = $flip_series;
                        $other_period_metrics_array[] = $offset_metric;
                    }
                    $index++;
                }
            }

        }

        $graph->metrics = array_merge($graph->metrics, $other_period_metrics_array);

    }

    function get_dot_star_data($dashboard_payload, $from = null, $until = null){
        $CI = & get_instance();
        $CI->load->model('graphite/GraphiteModel');
        $CI->load->helper('general');

        $graphs = $dashboard_payload->graphs;
        $new_graphs = array();
        $graph_num = 0;

        foreach ($graphs as $graph){

            foreach ($graph->metrics as $metric){
                $sub_metrics = $CI->GraphiteModel->get_metrics_with_format($metric->metric_name);
                $cur_metrics_on_graph = 0;

                $new_graph_obj = clone $graph;
                $new_graph_obj->metrics = array();
                foreach ($sub_metrics as $sub_metric_name){

                    if ($cur_metrics_on_graph == 4){
                        $new_graph_obj->order = $graph_num;
                        $new_graph_obj->is_half_size = 1;
                        $new_graphs[] = $new_graph_obj;
                        unset($new_graph_obj);
                        $new_graph_obj = clone $graph;
                        $new_graph_obj->metrics = array();
                        $cur_metrics_on_graph = 0;
                        $graph_num += 1;
                    }

                    $new_metric_obj = clone $metric;
                    $new_metric_obj->metric_name = $sub_metric_name;
                    $new_metric_obj->display_name = $sub_metric_name;

                    if ($from != null){
                        $from = abs($from) * -1;
                        $new_metric_obj->from = $from;
                    }
                    if ($until != null){
                        $until = abs($until) * -1;
                        $new_metric_obj->until = $until;
                    }

                    $original_series = $CI->GraphiteModel->get_details($new_metric_obj->metric_name, array($new_metric_obj->from, GraphiteModel::HOURS), array($new_metric_obj->until, GraphiteModel::HOURS));
                    $flip_series = flip_time_series($original_series[0]->datapoints);
                    $new_metric_obj->datapoints = $flip_series;

                    $new_graph_obj->metrics[] = $new_metric_obj;
                    unset($new_metric_obj);
                    $cur_metrics_on_graph += 1;
                }

                $new_graph_obj->order = $graph_num;
                $new_graph_obj->is_half_size = 1;
                $new_graphs[] = $new_graph_obj;
                unset($new_graph_obj);
                $graph_num += 1;

            }

        }
        $dashboard_payload->graphs = $new_graphs;
        return json_encode($dashboard_payload);

    }

    function get_offset_metric($metric, $offset){
        $offset_metric = new stdClass();
        if ($offset == 1){
            $offset_metric->display_name = $metric->metric_name . ".last_period";
            $offset_metric->metric_name = $metric->metric_name;
            $offset_metric->from = get_hour_value($metric->from) - get_period_length($metric);
            $offset_metric->until = get_hour_value($metric->until) - get_period_length($metric);
            $offset_metric->offset = get_period_length($metric) * -1;
        }else{
            if (($offset % -24) == 0){
                $metric_tag_value = $offset / -24;
                $metric_tag_unit = "days";
            }else{
                $metric_tag_value = $offset / -1;
                $metric_tag_unit = "hours";
            }
            $offset_metric->display_name = $metric->metric_name . "." . $metric_tag_value . "_" . $metric_tag_unit . "_ago";
            $offset_metric->metric_name = $metric->metric_name;
            $offset_metric->from = get_hour_value($metric->from) + $offset;
            $offset_metric->until = get_hour_value($metric->until) + $offset;
            $offset_metric->offset = $offset;
        }
        return $offset_metric;
    }

    function get_period_length($metric){
        $from_value = get_hour_value($metric->from);
        $until_value = get_hour_value($metric->until);

        return $until_value - $from_value;
    }

    function get_hour_value($hour_string){
        $hour_array = explode('h',$hour_string,2);
        $hour_value = $hour_array[0];
        return $hour_value;
    }

    function shift_time_series($negative_hours_offset, $time_series){
        $negative_sec_offset = $negative_hours_offset * 60 * 60;
        $return_array = array();
        foreach ($time_series as $array){
            $new_array = array( $array[0], $array[1] - $negative_sec_offset );
            $return_array[] = $new_array;
        }

        return $return_array;
    }

    function flip_time_series($time_series){
        $return_array = array();
        foreach ($time_series as $array){
            $new_array = array( $array[1]*1000, $array[0]);
            $return_array[] = $new_array;
        }
        //log_line("Original Count:" . sizeof($time_series) . " Morphed Count:" . sizeof($return_array) );
        return $return_array;
    }

    function get_dashboard_json($dashboard_name, $category_name){
        $CI = & get_instance();
        $CI->load->model('category/CategoryModel');
        $CI->load->model('dashboard/DashboardModel');
        $CI->load->model('graph/GraphModel');
        $CI->load->model('metric/MetricModel');
        $CI->load->model('dashboard_graphs/DashboardGraphsModel');
        $CI->load->model('graph_metrics/GraphMetricsModel');

        $category = $CI->CategoryModel->get_by_name($category_name);
        $dashboard = $CI->DashboardModel->get_where( array('dashboard_name' => $dashboard_name, 'category_id' => $category->id ));
        $dashboard_graph_objs = $CI->DashboardGraphsModel->get_graphs_by_dashboard($dashboard->id);

        $dashboard->graphs = array();

        foreach ($dashboard_graph_objs as $dashboard_graph){
            $graph = $CI->GraphModel->get_by_id($dashboard_graph->graph_id);
            $graph->order = $dashboard_graph->dashboard_order;
            $graph_metric_objs = $CI->GraphMetricsModel->get_metrics_by_graph($graph->id);

            $graph->metrics = array();

            foreach ($graph_metric_objs as $graph_metric){
                $metric = $CI->MetricModel->get_by_id($graph_metric->metric_id);
                $metric->order = $graph_metric->graph_order;
                $metric->from = $metric->start_from;
                unset($metric->start_from);
                $graph->metrics[] = $metric;
            }

            $dashboard->graphs[] = $graph;
        }

        $dashboard->category_name = $category->category_name;

        return array( "payload" => json_encode($dashboard), "dashboard_id" => $dashboard->id);
    }

    function dashboard_restricted($dashboard_name, $category_name){
        $CI = & get_instance();
        $CI->load->model('category/CategoryModel');
        $CI->load->model('dashboard/DashboardModel');

        $category = $CI->CategoryModel->get_by_name($category_name);
        $dashboard = $CI->DashboardModel->get_where( array('dashboard_name' => $dashboard_name, 'category_id' => $category->id ));

        return $dashboard->restricted;
    }
}
?>
