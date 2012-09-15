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
