<?php

require_once(dirname(__FILE__) . '/DashboardGraphs.php');
require_once(APPPATH . 'models/basemodel.php');

class DashboardGraphsModel extends BaseModel {

    protected $table_name = 'dashboard_graphs';

    function create() {
        $obj = new DashboardGraphs();
        return $obj;
    }

    function get_graphs_by_dashboard($dashboard_id){
        debug(__FILE__, "get_graphs_by_dashboard() is called for DashboardGraphsModel");

        $dashboard_graph_relations = self::get(array('dashboard_id' => $dashboard_id));
        return $dashboard_graph_relations;
    }

    function get_graph_ids_by_dashboard($dashboard_id){
        debug(__FILE__, "get_graph_ids_by_dashboard() is called for DashboardGraphsModel");

        $dashboard_graph_relations = $this->get_graphs_by_dashboard($dashboard_id);

        $graph_ids = array();

        foreach ($dashboard_graph_relations as $dg_relation){
            $graph_ids[] = $dg_relation->graph_id;
        }

        return $graph_ids;
    }

    function replace_graphs_for_dashboard($dashboard_id, $graph_objs){
        debug(__FILE__, "replace_metric_ids_for_graph() is called for DashboardGraphsModel");

        $graph_ids = array();

        foreach($graph_objs as $graph){
            $graph_ids[] = $graph->id;
        }

        $this->db->where('dashboard_id', $dashboard_id);
        $this->db->where_not_in('graph_id', $graph_ids);
        $this->db->delete($this->table_name);

        $cur_graphs_in_dashboard = $this->get_graph_ids_by_dashboard($dashboard_id);

        //Gets all values in graph_ids that are not in cur_graphs_in_dashboard (aka new graphs)
        $new_graphs = array_diff($graph_ids, $cur_graphs_in_dashboard);

        if (count($new_graphs) == 0){
            $result = true;
        }
        
        foreach ($graph_objs as $graph){
            $obj = $this->create();
            $obj->dashboard_id = $dashboard_id;
            $obj->graph_id = $graph->id;
            $obj->dashboard_order = $graph->dashboard_order;
            $result = self::save($obj);

            if ($result == false){
                break;
            }
        }

        return $result;
    }
}