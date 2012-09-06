<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/controllers.html
 */
class CI_Controller {
    public $orion_config;

	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		self::$instance =& $this;
		
		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach (is_loaded() as $var => $class)
		{
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');

		$this->load->initialize();

        $this->orion_config = $this->config->item('orion');
        $this->format_orion_config();
		
		log_message('debug', "Controller Class Initialized");
	}

	public static function &get_instance()
	{
		return self::$instance;
	}

    private function format_orion_config(){
        $index = 0;
        $new_metric_breakdown = array();
        foreach ($this->orion_config['METRIC_CONFIG'] as $metric_segment){
            $new_metric_segment = array();

            //If it isn't already an array, make it one, assuming the name is the only value provided
            if ( !is_array($metric_segment) ){
                $new_metric_segment['name'] = $metric_segment;
                $new_metric_segment['display_order'] = $index;
                $new_metric_segment['allows_wildcard'] = false;
            }else{
                //If it is an array, but doesn't have a 'name' key, give it a 'name' key
                if ( !array_key_exists('name', $metric_segment) ){
                    $new_metric_segment['name'] = $metric_segment[0];

                    //If a second element in the array exists, assume it is the 'display_order'
                    if ( count( $metric_segment ) >= 2 ){
                        $new_metric_segment['display_order'] = $metric_segment[1];

                        //If a third element exists, assume it is the 'allows_wildcard'
                        if ( count( $metric_segment ) >= 3 ){
                            $new_metric_segment['allows_wildcard'] = $metric_segment[2];
                        }else{
                            $new_metric_segment['allows_wildcard'] = false;
                        }

                    }else{
                        $new_metric_segment['display_order'] = $index;
                        $new_metric_segment['allows_wildcard'] = false;
                    }

                //If it is an array, has a 'name' key, but no 'display_order' key, set its index
                }else if ( !array_key_exists('display_order', $metric_segment) ){
                    $new_metric_segment['name'] = $metric_segment['name'];
                    $new_metric_segment['display_order'] = $index;
                    $new_metric_segment['allows_wildcard'] = false;

                //If it is an array, has a 'name' key, 'display_order' key,
                //but no 'allows_wildcard' key, set it to false
                }else if( !array_key_exists('allows_wildcard', $metric_segment) ){
                    $new_metric_segment['name'] = $metric_segment['name'];
                    $new_metric_segment['display_order'] = $metric_segment['display_order'];
                    $new_metric_segment['allows_wildcard'] = false;

                //Everything is all good
                }else{
                    $new_metric_segment = $metric_segment;
                }
            }

            $index++;
            $new_metric_breakdown[] = $new_metric_segment;
        }

        $this->orion_config['METRIC_CONFIG'] = $new_metric_breakdown;
    }
}
// END Controller class

/* End of file Controller.php */
/* Location: ./system/core/Controller.php */