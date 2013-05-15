[Orion]
=================

Orion provides an easy way to create and manage custom dashboards of your Graphite data.

Key features:

+ Dashboards can contain multiple graphs and display multiple tables
+ Data is visualized with all the power and flexibility of Highcharts and Highstock
+ Dashboards are created and edited in a powerful and intuitive interface, using Backbone and Bootstrap
+ Organize dashboards into high level categories
+ Provide custom external links for each category
+ Orion is easily adaptable to any Graphite tree structure with a few simple config changes
+ Granular access permissions are provided via Google Account OAuth


Getting Started
---------------

1. Create the orion database (Install mysql first)-
    
    ./scripts/create_orion_db.sh 

2. Set ENVIRONMENT to 'development' in index.php. 

3. Setup the correct config params in config/orion.php - 

+ $config['METRIC_CONFIG'] -  This is used to determine the number of indents your metrics have based on the metric pattern your graphite setup follows. It is used to display labels in the create dashboard forms.

For example if your metric follows the pattern
```
    *.*.*.*
```
Make the array to be like this - 
```php
$config['METRIC_CONFIG'] = array(
    array(
        "name" => "a",
        "display_order" => 0,
        "allows_wildcard" => false
    ),
    array(
        "name" => "b",
        "display_order" => 1,
        "allows_wildcard" => false
    ),
    array(
        "name" => "c",
        "display_order" => 2,
        "allows_wildcard" => false
    ),
    array(
        "name" => "d",
        "display_order" => 3,
        "allows_wildcard" => false
    )
);
```

The names a,b,c and d are the various levels of your metric pattern. For example the carbon metrics follow the pattern - 
```
carbon.agents.server_name.metric_name.sub_metric_name
```
For this an appropriate METRIC_CONFIG would be - 
```php
$config['METRIC_CONFIG'] = array(
    array(
        "name" => "carbon",
        "display_order" => 0,
        "allows_wildcard" => false
    ),
    array(
        "name" => "agents",
        "display_order" => 1,
        "allows_wildcard" => false
    ),
    array(
        "name" => "server_name",
        "display_order" => 2,
        "allows_wildcard" => false
    ),
    array(
        "name" => "metric_name",
        "display_order" => 3,
        "allows_wildcard" => false
    ),
    array(
        "name" => "sub_metric_name",
        "display_order" => 4,
        "allows_wildcard" => false
    )
);
```

If there any more sub_sub_metrics then they will appear appended with the sub_metric. Therefore `carbon.agents.server_name.metric_name.sub_metric_name.sub_sub_metric` will also appear as a valid metric.

+ $config['UNWANTED_METRIC_STRINGS'] - This is used to ignore any metrics and all their child metrics. For example -
```php
$config['UNWANTED_METRIC_STRINGS'] = array('carbon'); 
```
Will ignore all the metrics that have carbon as a parent. 

+ $config['GRAPHITE_API_URL'] - Set this to the url/ip of the graphite server you want to connect orion with. Make sure you add a trailing slash. Example -

```php
$config['GRAPHITE_API_URL'] = 'http://graphite.wikidot.com/';
```

4. IMPORTANT - Run the cache repopulate php script/view. If your orion setup is available at http://localhost/orion then the view is located at
```
http://localhost/orion/index.php/cache/repopulate
```

Warning - This view will take a long time to load if you have a large number of metrics and/or the network connection between orion server and the graphite server is slow. After it runs succesfully, you should see something like this in your browser. 
```
ADDED 1409 NEW METRICS

REMOVED 24 DEPRECATED METRICS

REPOPULATION IS COMPLETE
```

This script will talk with your graphite server and create a local cache of all the metrics in the mysql db. Note that this script will IGNORE ALL THE METRICS mentioned in `$config['UNWANTED_METRIC_STRINGS']` variable. 

The create/edit dashboard links will only parse the metrics available in the cache. If you have new metrics that you want to resync the database, just rerun this view.

5. Determine what type of authentication you want to use. Currently supported in the default code base are Google OAuth 2.0, or a No Authentication system. Set the value of the `$config['AUTHENTICATION_METHOD']` variable in config/orion.php.

+ $config['AUTHENTICATION_METHOD'] - This is used to determine the which type of authentication system to use. Currently valid values are "NOAUTH" or "GOOGOAUTH2".

If you would like to use a different method for authentication, it is possible to set this up. In order to do so, determine a short form name for you authentication method (in all capital letters). For the following examples, assume your short form name is chosen to be "NEWAUTHMETHOD".

Create a file in the helpers folder named after your short form name. For example, `newauthmethod_authentication_helper.php`. Make sure you include the trailing `_authentication_helper.php` in the file name. You will then need to define the functionality for 3 (or potentially 4) methods, and place them in this file.

```php
function auth_get_user(){
    
    $CI =& get_instance();
    $CI->load->library('session');
    $CI->load->model('user/UserModel');

    //Parse session variables here to determine if user is logged in 
    // (for example, look for an auth token)

    $logged_in = true; //Set this variable to reflect whether the user is logged in

    if ( $logged_in ){
        //Determine the users email address
        $user_email = "myname@domain.com"; 

        $email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
        $user = $CI->UserModel->authenticate($email);
    }else{
        $user = $CI->UserModel->create();
    }

    return $user;
}

function auth_logout($redirect = true){

    $CI =& get_instance();
    $CI->load->library('session');

    //Determine if the user is logged in. A good way to do so,
    // if you set a token value during login, is to grab this
    // value from the session. The value of the $token variable
    // will be false if no such variable exists in the session
    $token = $CI->session->userdata('token');
    if ($token) {

        //If the user is logged in, unset all the session variables
        $CI->session->unset_userdata('token');
        $CI->session->unset_userdata('name');
        $CI->session->unset_userdata('user');
    }

    //Do not change this
    if ($redirect){
        redirect('orion');
    }else{
        return;
    }
}

function auth_login($input){
    //The input variable is an array of parameters passed to the login
    // script via the GET method.
    // If using an external authentication method that allows the hand off
    // of state variables, you can use the $input['location'] variable to
    // pass the desired location of the user through the authentication
    // method, and redirect the user to this location upon logging in
    
    $CI =& get_instance();

    //This is where you do your work to log the user in
    
    //If you need to go to an external source, you will likely
    // need a callback function. For this, see below.
}

function auth_callback($input){
    //This is only used if you need to go to an external source for
    // authentication purposes

    //The input variable is an array of parameters passed to the login
    // script via the GET method.    

    //If you need to provide a callback URL to the external source,
    // assuming your base instance is 'http://localhost/orion/', your 
    // authentication callback will be 
    // 'http://localhost/orion/index.php/authenticate/authenticate_callback/'
    
    $CI =& get_instance();
    $CI->load->library('session');
    $CI->load->model('user/UserModel');

    //This is where you do your work, given the information from the external
    // source to log the user in

    $external_auth_success = true; //Set this variable to reflect whether the user was logged in by the external source

    if ( $external_auth_success ){
        //Though not necessary, the following few lines are recommended
        // If you have a token, it is wise to save the token in the session
        // Similarly, if you know the user's name, you can save it in the
        // session, and the UI will reflect this when the user is logged in
        $CI->session->set_userdata(array('token' => $token));
        $CI->session->set_userdata(array('name' => $user_real_name));

        //These are necessary lines. You must in some way or another, set these
        // values. First, you determine the user's email, and authenticate them
        // (which will create a new user if not present in the DB). This will
        // return the authenticated user. You need to save this user as a json
        // encoded object in the session under the key 'user'. This is already
        // done in the lines below, all you need to do is set the $user_email variable 
        $email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
        $user = $CI->UserModel->authenticate($email);
        $CI->session->set_userdata(array('user' => json_encode($user)));

        //If you used the location value in the auth_login function, and passed this
        // through the external authentication source, you can use the following lines
        // to redirect to the new location.
        $redirect = $external_source_value; //Set the $redirect variable to reflect whether the value passed back by the external source
        if (!$redirect){
            $redirect = "orion";
        }
        redirect($redirect);

    }

    redirect('orion');
}
```

After creating this file and defining the functionality for these methods, change the value of the `$config['AUTHENTICATION_METHOD']` variable to what you chose as your short form name (in this example it was "NEWAUTHMETHOD"). It should look like this
```php
$config['AUTHENTICATION_METHOD'] = 'NEWAUTHMETHOD';
```

Test your new authentication method.

Libraries / Dependencies (a.k.a. standing on the shoulders of giants)
------------------------

+ CodeIgniter [http://codeigniter.com/]
+ Highcharts and Highstock [http://www.highcharts.com/] *NOTE: Not free for commercial use.  See this page for licensing details: http://shop.highsoft.com/highcharts.html*
+ Backbone.js [http://documentcloud.github.com/backbone/]
+ Underscore.js [http://documentcloud.github.com/underscore/]
+ jQuery [http://jquery.com/]
+ jQuery UI [http://jqueryui.com/]
+ Mustache.js [https://github.com/janl/mustache.js]
+ Twitter Bootstrap [http://twitter.github.com/bootstrap/]
+ HTML5Boilerplate [http://html5boilerplate.com/]
+ Google APIs Client Library for PHP [http://code.google.com/p/google-api-php-client]
+ Chosen [http://harvesthq.github.com/chosen/]
+ KLogger [https://github.com/katzgrau/KLogger]

Library Modifications
---------------------

###CodeIgniter

#####system/core/Controller.php
+ Added format_orion_config() function to create the orion_config variable for the controllers on load
+ Modified __construct to call format_orion_config() after getting necessary config variables

#####system/database/DB_active_rec.php
+ Added on_duplicate() functionality in order to more easily support multiple column keys and update when key violations occur
+ Code obtained from http://web.archive.org/web/20090221091226/http://codeigniter.com/forums/viewthread/80958/

#####system/database/drivers/mysql/mysql_driver.php
+ Added _duplicate_insert() functionality similar for same reason as above - this is part of the same change
+ Code obtained from http://web.archive.org/web/20090221091226/http://codeigniter.com/forums/viewthread/80958/

#####system/core/Loader.php
+ In _ci_autoloader() added the functionality to autoload configs using the 'use_sections' and 'fail_gracefully' parameter use_sections/fail_gracefully) now works.

###KLogger

#####application/libraries/KLogger.php
+ A project written by Kenny Katzgrau. Modified by Ram Gudavalli.

Authors
-------

**Danny Bowman**

+ http://www.d-bow.com
+ https://github.com/Bowman224

**Patrick Cockwell**
+ https://github.com/pcockwell

**Karan Kurani**

+ http://flavors.me/karankurani

**Ram Gudavalli**


Copyright and license
---------------------

The MIT License
Copyright (c) 2012 GREE, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Licenses for libraries and dependencies:

# Highcharts / Highstock
+ http://shop.highsoft.com/highcharts.html
+ This application may *NOT* be used for a commercial purpose without purchasing the appropriate license from Highsoft.  See above link for more details.

# Everything Else:
+ MIT software License [Backbone, Underscore, Mustache, jQuery, jQuery UI, Modernizr, Chosen, KLogger] - http://www.opensource.org/licenses/MIT
+ Apache License, Version 2.0 [Twitter Bootstrap, Google APIs Client Library for PHP] - http://www.apache.org/licenses/LICENSE-2.0
+ http://codeigniter.com/user_guide/license.html
