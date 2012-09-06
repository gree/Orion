<?php
require_once(APPPATH .'models/baseentity.php');

abstract class BaseModel extends CI_Model {

    protected $table_name;

    function __construct()
    {
        parent::__construct();

        if (!isset($this->table_name)) {
            throw new Exception(get_class($this) . " has undefined database table name");
        }
    }

    abstract protected function create();

    private function set_field($object, $field_type, $field_key, $field_value) {
        if($field_key == 'payload') {
            // Never set payload as a field on the object (instead its expanded and it's sub-data are set on the object)
        } else if(is_null($field_value)) {
            $object->$field_key = $field_value;
        } else if($field_type == 'int') {
            $object->$field_key = (int) $field_value;
        } else if($field_type == 'float') {
            $object->$field_key = (float) $field_value;
        } else if($field_type == 'string') {
            $object->$field_key = (string) $field_value;
        } else if($field_type == 'array') {
            $object->$field_key = json_decode((string)$field_value);
        } else {
            $object->$field_key = $field_value;
        }
    }

    private function map_to_object($data)
    {
        $obj = $this->create();
        $db_fields = $obj::$_db_fields;

        foreach ($data as $dkey => $dval) {

            if(array_key_exists($dkey, $db_fields))
            {
                $field_type = $db_fields[$dkey][0];
                $this->set_field($obj, $field_type, $dkey, $dval);
            }

        }

        return $obj;
    }

    private function map_to_object_list($results) {

        $obj_list = array();

        foreach ($results as $row) {
        	$obj_list[] = $this->map_to_object($row);
        }

        return $obj_list;
    }

    public function get($where = null, $order_by = null, $limit = null, $offset = null)
    {
        $this->db->from($this->table_name);

        if (!is_null($where)) {
            $this->db->where($where);
        }

        if (!is_null($limit)) {
            $this->db->limit($limit, $offset);
        }

        if (!is_null($order_by)) {
            foreach ($order_by as $k => $v) {
                $this->db->order_by($k, $v);
            }
        }

        $object_list = $this->map_to_object_list($this->db->get()->result());

        return $object_list;
    }

    public function count($where = null, $limit = null)
    {
        $this->db->from($this->table_name);

        if (!is_null($where)) {
            $this->db->where($where);
        }

        if (!is_null($limit)) {
            list($start, $offset) = explode(',', $limit);
            $this->db->limit($offset, $start);
        }

        return $this->db->count_all_results();
    }

    public function save(&$obj)
    {
        if (!$obj instanceof BaseEntity) {
            throw new Exception("Invalid Object Type");
        }

        foreach ($obj::$_db_fields as $field_name => $field_info){
            if ($field_info[0] == 'array'){
                $obj->$field_name = json_encode($obj->$field_name);
            }
        }

        $result = null;

        if (!isset($obj->id)) {
            $data = array();
            foreach($obj as $field => $value){
                $data[$field] = $value;
            }
            $result = $this->db->on_duplicate($this->table_name, $obj);
        } else {
            $this->db->where('id', $obj->id);
            $result = $this->db->update($this->table_name, $obj);
        }

        $is_success = $result ? true : false;

        return $is_success;
    }

    public function last_insert_id(){
        return $this->db->insert_id();
    }
}