<?php

require_once(dirname(__FILE__) . '/GraphMetrics.php');
require_once(APPPATH . 'models/basemodel.php');

class GraphMetricsModel extends BaseModel {

    protected $table_name = 'graph_metrics';

    function create() {
        $obj = new GraphMetrics();
        return $obj;
    }

    function get_metrics_by_graph($graph_id){
        debug(__FILE__, "get_metrics_by_graph() is called for GraphMetricsModel");

        $graph_metric_relations = self::get(array('graph_id' => $graph_id));
        return $graph_metric_relations;
    }

    function get_metric_ids_by_graph($graph_id){
        debug(__FILE__, "get_metric_ids_by_graph() is called for DashboardGraphsModel");

        $graph_metric_relations = $this->get_metrics_by_graph($graph_id);

        $metric_ids = array();

        foreach ($graph_metric_relations as $gm_relation){
            $metric_ids[] = $gm_relation->graph_id;
        }

        return $metric_ids;
    }

    function replace_metrics_for_graph($graph_id, $metric_objs){
        debug(__FILE__, "replace_metric_ids_for_graph() is called for GraphMetricsModel");

        $metric_ids = array();

        foreach($metric_objs as $metric){
            $metric_ids[] = $metric->id;
        }

        $this->db->where('graph_id', $graph_id);
        $this->db->where_not_in('metric_id', $metric_ids);
        $this->db->delete($this->table_name);

        $cur_metrics_in_graph = $this->get_metric_ids_by_graph($graph_id);
        $new_metrics = array_diff($metric_ids, $cur_metrics_in_graph);

        if (count($new_metrics) == 0){
            $result = true;
        }

        foreach ($metric_objs as $metric){
            $obj = $this->create();
            $obj->graph_id = $graph_id;
            $obj->metric_id = $metric->id;
            $obj->graph_order = $metric->graph_order;
            $result = self::save($obj);

            if ($result == false){
                break;
            }
        }

        return $result;
    }

}