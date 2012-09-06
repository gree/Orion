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

1. Create the orion database


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

CodeIgniter

+ system/core/Controller.php
+++ Added format_orion_config() function to create the orion_config variable for the controllers on load
+++ Modified __construct to call format_orion_config() after getting necessary config variables

+ system/database/DB_active_rec.php
+++ Added on_duplicate() functionality in order to more easily support multiple column keys and update when key violations occur
+++ Code obtained from http://web.archive.org/web/20090221091226/http://codeigniter.com/forums/viewthread/80958/

+ system/database/drivers/mysql/mysql_driver.php
+++ Added _duplicate_insert() functionality similar for same reason as above - this is part of the same change
+++ Code obtained from http://web.archive.org/web/20090221091226/http://codeigniter.com/forums/viewthread/80958/

+ system/core/Loader.php
+++ In _ci_autoloader() added the functionality to autoload configs using the 'use_sections' and 'fail_gracefully' parameter use_sections/fail_gracefully) now works.

KLogger

+ application/libraries/KLogger.php
+++ A project written by Kenny Katzgrau. Modified by Ram Gudavalli.

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

TODO

Licenses for libraries and dependencies:

# Highcharts / Highstock
+ http://shop.highsoft.com/highcharts.html
+ This application may *NOT* be used for a commercial purpose without purchasing the appropriate license from Highsoft.  See above link for more details.

# Everything Else:
+ MIT software License [Backbone, Underscore, Mustache, jQuery, jQuery UI, Modernizr, Chosen, KLogger] - http://www.opensource.org/licenses/MIT
+ Apache License, Version 2.0 [Twitter Bootstrap, Google APIs Client Library for PHP] - http://www.apache.org/licenses/LICENSE-2.0
+ http://codeigniter.com/user_guide/license.html


