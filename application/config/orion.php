<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Orion Specific constants file
 */
$config = array();

/*
 * Application title
 *
 * This value will show up in the title bar for
 * your browser and as the title in the navigation bar
 */
$config['APPLICATION_TITLE'] = "Orion";

/*
 * The base name for the log files
 *
 * For example, setting this to 'orion', will produce
 * log files with the names:
 *
 * orion-YYYY-MM-DD.log
 * orion-errors-YYYY-MM-DD.log
 *
 */
$config['LOG_FILE_BASE'] = 'orion';

/*
 * Default admin user email
 *
 * NOTE: THIS IS NOT IMPLEMENTED YET
 *
 * The system will automatically create
 * an account for this email the first time
 * (if an account doesn't already exist)
 * the Orion Analytics System is accessed
 * and give this email full priviledges
 */
$config['DEFAULT_ADMIN_EMAIL'] = '';

/*
 * Metric Formatting Information
 */

/*
 * Metric prefix
 *
 * A prefix that is found on
 * the beginning of all metric names
 */
$config['METRIC_PREFIX'] = '';

/*
 * Metric configuration
 *
 * This is an array of associative
 * arrays. Each inner array has up to
 * 3 key/value pairs. These are:
 * - name
 * - display_order
 * - allows_wildcard
 *
 * name is the display name of the segment
 * of the metric
 *
 * display_order is the 0-based index
 * in which it should be ordered when
 * displayed on the create_dashboard
 * page - defaults to the index of the
 * segment array is in the config array
 *
 * allows_wildcard is a boolean value
 * that determines whether or not this
 * segment can be a substituted with a
 * wildcard - defaults to false
 *
 * The order of the inner arrays denotes
 * the order in which that segment appears
 * in the metric name (after the metric
 * prefix)
 */
$config['METRIC_CONFIG'] = array(
    array(
        "name" => "",
        "display_order" => 0,
        "allows_wildcard" => false
    ),
    array(
        "name" => "",
        "display_order" => 1,
        "allows_wildcard" => false
    ),
    array(
        "name" => "",
        "display_order" => 2,
        "allows_wildcard" => false
    ),
    array(
        "name" => "",
        "display_order" => 3,
        "allows_wildcard" => false
    ),
    array(
        "name" => "",
        "display_order" => 4,
        "allows_wildcard" => false
    )
);

/*
 * Unwanted metric segments
 *
 * This will determine in which order you
 * want the names above (and metric segments
 * they represent) to show up in the UI
 * and be used for filtering with metric names
 *
 * These values are 0 indexed integers
 * that represent the names listed above
 */
$config['UNWANTED_METRIC_STRINGS'] = array('carbon');

/*
 * Google OAuth Information
 *
 * You need to add the following URL to your
 * list of redirect URIs on your google APIs
 * console as accepted URIs
 *
 * BASE_URL/index.php/authenticate/googleoauth2callback
 */

/*
 * Application Name
 *
 * What you want displayed as your
 * application name when people log
 * in using google oauth
 */
$config['GOOGLE_OAUTH_APPLICATION_NAME'] = 'Orion Analytics';

/*
 * OAuth Client ID
 *
 * This is provided by google and is
 * your OAuth client identification key
 */
$config['GOOGLE_OAUTH_CLIENT_ID'] = '';

/*
 * OAuth Client Secret
 *
 * This is provided by google and is
 * your OAuth client secret key
 */
$config['GOOGLE_OAUTH_CLIENT_SECRET'] = '';

/*
 * OAuth 1 Site Name
 *
 * A URL that should be shown in Google's
 * OAuth 1 authentication screen
 */
$config['GOOGLE_OAUTH_1_SITE_NAME'] = '';

/*
 * Authentication Method
 *
 * This can be one of the following 
 * authentication methods:
 *
 * NOAUTH - No authentication is used
 * GOOGOAUTH2 - Uses Google OAuth2.0 
 *     authentication
 * 
 */
$config['AUTHENTICATION_METHOD'] = 'NOAUTH';

/*
 * Accepted Domain Names
 *
 * Domain names for which user authentication
 * is allowed. If empty, all domain names are
 * accepted.
 */
$config['ACCEPTED_DOMAIN_NAMES'] = array();

/*
 * The ENVIRONMENT variable is set in index.php
 *
 * This sets the constants based on which
 * ENVIRONMENT is in use
 */
if ( ENVIRONMENT == 'production'){

    /*
    * Database information
    */

    /*
    * The database access information
    *
    * If the database is on the same server as
    * the code base for Orion Analytics system,
    * then DB_HOSTNAME should be 'localhost'
    *
    * If you choose to change the value for
    * DB_DATABASE, you must also edit the values
    * for this in:
    * scripts/create_new_dump.sh
    * scripts/create_orion_db.sh
    */
    $config['DB_HOSTNAME'] = 'localhost';
    $config['DB_USERNAME'] = 'root';
    $config['DB_PASSWORD'] = '';
    $config['DB_DATABASE'] = 'orion';

    /*
    * The directory to log server calls to
    */
    $config['LOG_DIRECTORY'] = '/tmp';

    /*
     * The base URL to access the Graphite Server
     *
     * Accessing this url should load the base
     * Graphite Browser that comes with your
     * Graphite installation
     */
    $config['GRAPHITE_API_URL'] = '';

    /*
     * Any HTTP authentication required when accessing the Graphite server
     *
     * If your Graphite Installation is behind
     * any HTTP authentication, you can use this
     * to set the username and password. The format
     * for the 'header' key in this array is:
     *
     * 'Authorization: Basic " . base64_encode("username:password")
     */
    $config['GRAPHITE_CONTEXT'] = stream_context_create(
        array   (
            'http' => array( 'header'  => "Authorization: Basic " . base64_encode("username:password") )
        )
    );
} else if ( ENVIRONMENT == 'development' ){

    /*
    * Database information
    */

    /*
    * The database access information
    *
    * If the database is on the same server as
    * the code base for Orion Analytics system,
    * then DB_HOSTNAME should be 'localhost'
    *
    * If you choose to change the value for
    * DB_DATABASE, you must also edit the values
    * for this in:
    * scripts/create_new_dump.sh
    * scripts/create_orion_db.sh
    */
    $config['DB_HOSTNAME'] = 'localhost';
    $config['DB_USERNAME'] = 'root';
    $config['DB_PASSWORD'] = '';
    $config['DB_DATABASE'] = 'orion';

    /*
    * The directory to log server calls to
    */
    $config['LOG_DIRECTORY'] = '/tmp';

    /*
     * The base URL to access the Graphite Server
     *
     * Accessing this url should load the base
     * Graphite Browser that comes with your
     * Graphite installation
     */
    $config['GRAPHITE_API_URL'] = '';

    /*
     * Any HTTP authentication required when accessing the Graphite server
     *
     * If your Graphite Installation is behind
     * any HTTP authentication, you can use this
     * to set the username and password. The format
     * for the 'header' key in this array is:
     *
     * 'Authorization: Basic " . base64_encode("username:password")
     */
    $config['GRAPHITE_CONTEXT'] = stream_context_create(
        array   (
            'http' => array( 'header'  => "Authorization: Basic " . base64_encode("username:password") )
        )
    );
}

