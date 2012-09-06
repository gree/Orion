// Copyright 2012 GREE Inc. All Rights Reserved.

/**
 * @fileoverview The orion.js file contains code for the Orion dashboard create and edit pages.
 *
 *      It uses Backbone to define Dashboard, Graph, and Metric Models, and GraphCollection and
 *      MetricCollection Collections.  Each Dashboard has a GraphCollection of Graphs, and each Graph
 *      has a MetricCollection of Metrics.
 *
 *      It also defines Views for Dashboard, Graph, and Metric to handle UI rendering.
 *      It uses Mustache templating.
 *
 *      It's a generally straightforward Backbone application, using Models to define data structure for
 *      the server, provide validation, ordering, and initialization.  Models communicate with each other
 *      and with Views mostly via Events.  Views handle the rendering of the data in the Models in the UI
 *      and handle user interactions/events.  The main complexity comes from a) using two levels of nested
 *      Models, which can get a bit complex, and b) Metric rendering in the UI, which is complicated because
 *      of the differences between what the user interacts with and what the data looks like on the server.
 *
 *          Backbone: http://documentcloud.github.com/backbone/
 *          Mustache: https://github.com/janl/mustache.js
 *
 * @author <daniel.bowman@gree.co.jp> Danny Bowman
 *
 */


/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {};


(function () {

    // ORION namespace for code specific to the create/edit dashboard pages.
    APP.ORION = {};

    /**
     * APP.ORION.Models
     * Module with Backbone Models for the application.
     */
    APP.ORION.Models = (function () {

        var _dashboardJson;

        // Checks if given object has any direct properties.
        function _isEmptyObject (obj) {
            var alexRosen;
            for (alexRosen in obj) {
                if (Object.prototype.hasOwnProperty.call(obj, alexRosen)) {
                    return false;
                }
            }
            return true;
        }

        // TODO - dbow - Create a parent Backbone Model to give default properties and methods to all Models.
        // TODO - dbow - Abstract validation and nesting/copying into parent Model.

        return {

            /**
             * APP.ORION.Models.Dashboard
             * Top level Backbone model for a graphite dashboard.  Contains a Collection of Graph objects.
             *
             */
            Dashboard: Backbone.Model.extend({

                // Default attributes for Dashboard.
                defaults: function () {

                    return {
                        hasCatchAll: false,
                        restricted: 0,
                        graphs: new APP.ORION.Models.GraphCollection
                    };

                },

                // Overrides Backbone URLs Based on orion.php (because server is not RESTful):
                url: function (type) {

                    var base_path = APP.Setup.get_base_path(),
                        app_path = 'index.php/orion/',
                        URL_MAP = {
                            create: 'save_dashboard',
                            read: 'get_dashboard_graphs',
                            update: 'save_dashboard',
                            'delete': 'delete_dashboard'
                        };
                    return base_path + app_path + URL_MAP[type];

                },

                // Parses server response after save, setting nested models' attributes to response data.
                parse: function (response) {

                    var data = JSON.parse(response.payload),
                        graphs = this.get('graphs'),
                        metrics,
                        metric;

                    // Sets attributes hashes starting from Metrics objects and working outward.
                    for (var i = 0, lenG = graphs.length; i < lenG; i++) {
                        metrics = graphs.at(i).get('metrics');
                        // Starts by setting response data for metrics attributes to the Metric objects.
                        for (var j = 0, lenM = metrics.length; j < lenM; j++) {
                            metrics.at(j).set(data.graphs[i].metrics[j]);
                        }
                        // Then sets resultant Collection as metrics property of response data.
                        data.graphs[i].metrics = metrics;
                        // Then sets response data for graphs attributes as Graph object.
                        graphs.at(i).set(data.graphs[i]);
                    }

                    // Finally sets resultant Collection as graphs property of response data.
                    data.graphs = graphs;
                    data.graphs.sort();

                    return data;

                },

                // Property that stores error messages when validation fails.
                errors: {},

                // Validate dashboard object params before saving to server.
                validate: function (attrs) {

                    var graphErrors;

                    this.errors = {}; // Reset errors object.

                    if (attrs.id && typeof attrs.id !== 'number') {
                        this.errors.id = 'ID must be an integer';
                    }

                    if (typeof attrs.dashboard_name !== 'string' || attrs.dashboard_name === '') {
                        this.errors.dashboard_name = 'Provide a Dashboard Name';
                    }

                    if (typeof attrs.category_name !== 'string' || attrs.category_name === '') {
                        this.errors.category_name = 'Provide a Category Name';
                    }

                    if ([0, 1].indexOf(parseInt(attrs.restricted, 10)) < 0) {
                        this.errors.restricted = 'Restricted must be 0 or 1';
                    }

                    if (attrs.graphs.length < 1) {
                        this.errors.graphs = 'Dashboard must have at least one graph';
                    }

                    if (attrs.hasCatchAll && attrs.graphs.length > 1) {
                        this.errors.graphs = 'Dashboard can only have one graph when using a .* value';
                    }

                    // Call validation on each Graph object.
                    for (var i = 0, gLen = attrs.graphs.length; i < gLen; i++) {
                        graphErrors = attrs.graphs.at(i).validate(attrs.graphs.at(i).attributes);
                        if (graphErrors) {
                            if (!this.errors.graphs) {
                                this.errors.graphs = {};
                            }
                            this.errors.graphs[graphErrors.cid] = graphErrors.errors;
                        }
                    }

                    // Trigger validationFailed event on Dashboard object if errors object is populated.
                    if (!_isEmptyObject(this.errors)) {
                        this.trigger('validationFailed');
                        return this.errors;
                    }

                },

                // Convenience function to get the number of graphs in the Dashboard's GraphCollection.
                getNumGraphs: function () {
                    return this.get('graphs').length;
                },

                // Iterates through graphs and metrics to determine if any use the .* catch-all value.
                checkCatchAll: function () {
                    var hasCatchAll = false;
                    this.get('graphs').each(function (graph) {
                        graph.catchAll = false;
                        graph.get('metrics').each(function (metric) {
                            if (metric.catchAll) {
                                hasCatchAll = true;
                                graph.catchAll = true;
                            }
                        });
                    });
                    this.set('hasCatchAll', hasCatchAll, {silent: true});
                },

                // Initialization function called when Dashboard object is created.
                initialize: function () {

                    var graphs = this.get('graphs'),
                        graphArray = [];

                    // If data is provided (loading an existing dashboard), create nested Graphs.
                    if (graphs.length > 0) {
                        for (var i = 0, len = graphs.length; i < len; i++) {
                            graphArray.push(new APP.ORION.Models.Graph(graphs[i]));
                        }
                        this.set({
                            graphs: new APP.ORION.Models.GraphCollection(graphArray)
                        }, {
                            error: function (model, errorObject) {
                                log(model, errorObject); // TODO - dbow - better error handling
                            }
                        });
                    }

                }

            }),

            /**
             * APP.ORION.Models.Graph
             * Backbone model for an individual Graph within a Dashboard. Contains a Collection of Metric objects.
             *
             */
            Graph: Backbone.Model.extend({

                // Default attributes for Graph.
                defaults: function () {
                    return {
                        is_half_size: 0,
                        metrics: new APP.ORION.Models.MetricCollection
                    }
                },

                // Property that stores error messages when validation fails.
                errors: {},

                // Validate Graph object params before saving to server.
                validate: function (attrs) {

                    var metricErrors;

                    this.errors = {}; // Reset errors object.

                    if (attrs.id && typeof attrs.id !== 'number') {
                        this.errors.id = 'ID must be an integer';
                    }

                    if (typeof attrs.graph_name !== 'string' || attrs.graph_name === '') {
                        this.errors.graph_name = 'Must provide a Graph Name';
                    }

                    if ([0, 1].indexOf(parseInt(attrs.is_half_size, 10)) < 0) {
                        this.errors.is_half_size = 'Is Half Size must be 0 or 1';
                    }

                    if (attrs.metrics.length < 1) {
                        this.errors.metrics = 'Graph must have at least one metric';
                    }

                    // Call validation on each Metric object.
                    for (var i = 0, gLen = attrs.metrics.length; i < gLen; i++) {
                        metricErrors = attrs.metrics.at(i).validate(attrs.metrics.at(i).attributes);
                        if (metricErrors) {
                            if (!this.errors.metrics) {
                                this.errors.metrics = {};
                            }
                            this.errors.metrics[metricErrors.cid] = metricErrors.errors;
                        }
                        if (this.catchAll && attrs.metrics.at(i).get('metric_name').indexOf('.*') < 0) {
                            this.errors.metric_catchall = 'Graph must have either all .* metrics or none';
                        }
                    }

                    // If errors object is populated, return errors object up to Dashboard's validate method.
                    if (!_isEmptyObject(this.errors)) {
                        return {
                            cid: this.cid,
                            errors: this.errors
                        };
                    }

                },

                // Initialization function called when Graph object is created.
                initialize: function () {

                    var metrics = this.get('metrics'),
                        metricArray = [];

                    // If array of metrics attributes provided, create a Metrics collection and
                    // populate it with new Metric objects based on attributes.
                    if (metrics.length > 0) {
                        for (var i = 0, len = metrics.length; i < len; i++) {
                            var newMetric = new APP.ORION.Models.Metric(metrics[i]);
                            metricArray.push(newMetric);
                        }
                        this.set({
                            metrics: new APP.ORION.Models.MetricCollection(metricArray)
                        }, {silent: true});
                    }

                    // Set order to length of graphs array by default.
                    if (typeof this.get('order') !== 'number') {
                        this.set({order: APP.ORION.Dashboard.getNumGraphs()}, {silent: true});
                    }

                    // Default catchAll boolean.
                    this.catchAll = false;

                }

            }),

            /**
             * APP.ORION.Models.GraphCollection
             * Backbone Collection of Graph Models, set as an attribute of the Dashboard object.
             *
             */
            GraphCollection: Backbone.Collection.extend({

                model: this.Graph,

                // Graphs are sorted based on the order property.
                comparator: function (graph) {
                    return graph.get('order');
                },

                // Moves a Graph from the start index to the end index, shifting other Graphs accordingly.
                reorder: function (start, end) {

                    var moved = this.at(start),
                        i,
                        graph,
                        newOrder;

                    if (end < start) {
                        for (i = end; i < start; i++) {
                            graph = this.at(i);
                            newOrder = graph.get('order') + 1;
                            graph.set({order: newOrder}, {silent: true});
                        }
                    } else {
                        for (i = end; i > start; i--) {
                            graph = this.at(i);
                            newOrder = graph.get('order') - 1;
                            graph.set({order: newOrder}, {silent: true});
                        }
                    }
                    moved.set({order: end}, {silent: true});
                    this.sort();

                },

                // Initialization function to set up graph collection event handlers.
                initialize: function () {

                    var reset;

                    // Remove Graph from collection.
                    this.on('removeModel', function (model) {
                        this.remove(model);
                    });

                    // Function to reset the order property on graph removal.
                    function resetOrders() {
                        this.each(function (graph, index) {
                            graph.set('order', index, {silent: true});
                        });
                    }
                    reset = _.bind(resetOrders, this);
                    this.on('remove', reset);

                    // Copy Graph and insert resulting Graph into collection.
                    this.on('copyModel', function (graph) {

                        var newGraph = JSON.parse(JSON.stringify(graph)),
                            newMetric;

                        // Remove IDs, orders, and metrics
                        delete newGraph.id;
                        delete newGraph.order;
                        delete newGraph.metrics;

                        // If Metric objects are provided, convert them to attribute hashes.
                        if (graph.get('metrics').length !== 0) {

                            // Create an array of metric attribute hashes.
                            newGraph.metrics = [];
                            graph.get('metrics').each(function (metric) {
                                newMetric = JSON.parse(JSON.stringify(metric));
                                delete newMetric.id; // Remove IDs.
                                newGraph.metrics.push(newMetric);
                            });

                        }

                        // Create new Graph based on attributes of old Graph.
                        this.add(new APP.ORION.Models.Graph(newGraph));

                    });

                    // Move Graph up or down in order.
                    this.on('moveModel', function (model, direction) {
                        var start = model.get('order'),
                            end = direction === 'up' ? start - 1 : start + 1;
                        this.reorder(start, end);
                    });

                }

            }),

            /**
             * APP.ORION.Models.Metric
             * Backbone model for an individual Metric within a Graph.
             *
             */
            Metric: Backbone.Model.extend({

                // Default attributes for Metric.
                defaults: function () {
                    return {
                        metric_name: '',
                        other_period_offsets: [0]
                    }
                },

                // Property that stores error messages when validation fails.
                errors: {},

                // Validate Metric object params before saving to server.
                validate: function (attrs) {

                    this.errors = {};  // Reset errors object.

                    if (attrs.id && typeof attrs.id !== 'number') {
                        this.errors.id = 'ID must be an integer';
                    }

                    if (typeof attrs.metric_name !== 'string' || attrs.metric_name === '') {
                        this.errors.metric_name = 'Must provide a Metric Name';
                    }

                    var fieldObj = APP.ORION.Views.parseMetricName(attrs.metric_name),
                        field;

                    for (field in fieldObj) {
                        if (fieldObj.hasOwnProperty(field) && fieldObj[field] === '_') {
                            this.errors[field] = 'Must provide a ' + field;
                        }
                    }

                    if (typeof attrs.until !== 'number' || attrs.from > 0) {
                        this.errors.from = 'From must be a negative number or 0';
                    }

                    if (typeof attrs.until !== 'number' || attrs.until > 0) {
                        this.errors.until = 'Until must be a negative number or 0';
                    }

                    if (attrs.from > attrs.until) {
                        this.errors.from  = 'From must be less than Until';
                        this.errors.until  = 'From must be less than Until';
                    }

                    if (!Array.isArray(attrs.other_period_offsets)) {
                        this.errors.other_period_offsets = 'Other Period must be an array';
                    } else if (attrs.other_period_offsets.length < 1) {
                        this.errors.other_period_offsets = 'Metric must have at least one Other Period';
                    } else {
                        for (var l = 0, oLen = attrs.other_period_offsets.length; l < oLen; l++) {
                            if (attrs.other_period_offsets[l] > 1) {
                                this.errors.other_period_offsets = 'Other period offset must be 0, 1, or a negative number';
                            }
                        }
                    }

                    // If errors object is populated, return errors object up to Graph's validate method.
                    if (!_isEmptyObject(this.errors)) {
                        return {
                            cid: this.cid,
                            errors: this.errors
                        };
                    }

                },

                // Initialization function to set catchAll variable to false.
                initialize: function () {

                    this.catchAll = false;

                }

            }),

            /**
             * APP.ORION.Models.MetricCollection
             * Backbone Collection of Metric Models, set as an attribute of the Graph object.
             *
             */
            MetricCollection: Backbone.Collection.extend({

                model: this.Metric,

                // Initialization function to set up metric collection event handlers.
                initialize: function () {

                    // Remove Metric from collection.
                    this.on('removeModel', function (model) {
                        this.remove(model);
                    });

                    // Copy Metric and insert resulting Metric into collection.
                    this.on('copyModel', function (model) {
                        var newMetric = JSON.parse(JSON.stringify(model));
                        delete newMetric.id; // Remove ID.
                        this.add(new APP.ORION.Models.Metric(newMetric));
                    });

                }

            }),

            setDashboardJson: function (dashboardJsonObj) {
                _dashboardJson = dashboardJsonObj;
            },

            /**
             * APP.ORION.Models.setup
             * Creates a Dashboard object.
             */
            setup: function () {

                if (_dashboardJson) {
                    APP.ORION.Dashboard = new APP.ORION.Models.Dashboard(JSON.parse(_dashboardJson));
                } else {
                    APP.ORION.Dashboard = new APP.ORION.Models.Dashboard;
                }

            }

        };

    }());


    /**
     * APP.ORION.Views
     * Module with Backbone Views for the application.
     */
    APP.ORION.Views = (function () {


        var _metricObject = {}, // Object storing nested metric property select list values.

            // Array of metric name strings. (passed by server)
            METRIC_ARRAY = [],

            // String that appears at the beginning of every metric_name (*optional* - passed by server)
            METRIC_PREFIX = '',

            // Array of metric config objects, parsed into METRIC_SELECT_FIELDS and ORDER_ARRAY (passed by server)
            METRIC_CONFIG = [],

            // Array of fields that the user should specify for each metric
            // in the order they should appear in the UI.
            METRIC_SELECT_FIELDS = [],

            // Array showing the index at which each METRIC_SELECT_FIELD appears
            // in the split metric_name array.
            ORDER_ARRAY =  [],

            // The metric field that appears last in the split metric_name array.
            _lastField = '';


        // Parses the METRIC_CONFIG config object passed from the server.
        function _parseConfig () {

            if (METRIC_CONFIG) {

                var _sortedArray = _.sortBy(METRIC_CONFIG, 'display_order');

                METRIC_SELECT_FIELDS = _.map(_sortedArray, function (field) {
                    return field.name;
                });
                _.each(_sortedArray, function (field) {
                    ORDER_ARRAY.push(_.indexOf(METRIC_CONFIG, field));
                });

                _lastField = METRIC_SELECT_FIELDS[_.indexOf(ORDER_ARRAY, _.max(ORDER_ARRAY))];

            }

        }

        // Parse metrics array into a nested Object based on METRIC_SELECT_FIELDS.
        function _parseMetrics () {

            if (METRIC_ARRAY) {

                var itemObj,
                    uiLen = uiLen = METRIC_SELECT_FIELDS.length;

                /**
                 *  Recursive function to create a nested object based on METRIC_SELECT_FIELDS.
                 *  Example output at end:
                 *    {
                 *      METRIC_SELECT_FIELDS[0]: {
                 *         METRIC_SELECT_FIELDS[1]: {
                 *           METRIC_SELECT_FIELDS[2]: {
                 *             METRIC_SELECT_FIELDS[3]: {
                 *               METRIC_SELECT_FIELDS[4]: {}
                 *             }
                 *           }
                 *         }
                 *      }
                 *    }
                 */
                function _nestObject(index, object) {

                    var fieldtype = METRIC_SELECT_FIELDS[index],
                        currVal = itemObj[fieldtype],
                        wildcard,
                        catchAll;

                    if (!object[currVal]) {
                        object[currVal] = {};
                    }
                    index += 1;

                    // If field in config has allows_wildcard, create a nested wildcard level '*'
                    wildcard = _.find(METRIC_CONFIG, function (field) {
                        return field.name === fieldtype;
                    }).allows_wildcard;
                    if (wildcard) {
                        if (!object['*']) {
                            object['*'] = {};
                        }
                        if (index < uiLen) {
                            _nestObject(index, object['*']);
                        }
                    }

                    // If _lastField has sub fields, generate a .* (catch-all) value in the object.
                    if (fieldtype === _lastField && currVal.indexOf('.') >= 0) {
                        catchAll = currVal.split('.')[0] + '.*';
                        if (!object[catchAll]) {
                            object[catchAll] = {};
                        }
                        if (index < uiLen) {
                            _nestObject(index, object[catchAll]);
                        }
                    }

                    if (index < uiLen) {
                        _nestObject(index, object[currVal]);
                    }
                }

                // Iterate through each string, adding to nested Object.
                _.each(METRIC_ARRAY, function(metricString) {

                    itemObj = APP.ORION.Views.parseMetricName(metricString);

                    if (itemObj) {
                        // _metricObject is the nested Object that is produced.
                        _nestObject(0, _metricObject);
                    }

                });

            }

        }

        return {

            // TODO - dbow - Create a parent Backbone View to give default properties and methods to all Views.
            // TODO - dbow - Abstract Field updates, nested object creation/adding,
            // TODO - dbow - model event listeners, and template object creation into parent View.

            /**
             * APP.ORION.Views.DashboardView
             * Backbone View for the form to create or edit a Dashboard model.
             *
             */
            DashboardView: Backbone.View.extend({

                el: '#dashboard_create_form_container',

                events: {
                    'change .form-input': 'updateField',
                    'click #add_graph': 'createGraph',
                    'click #dashboard_delete': 'deleteDashboard',
                    'submit #create_dashboard_form': 'submitHandler'
                },

                // When form input values change, update the Dashboard model attributes.
                updateField: function (e) {
                    var input = $(e.target),
                        val = input.is(':checkbox') ? input.is(':checked') + 0 : _.escape(input.val());
                    this.model.set(input.attr('id'), val, {silent: true});
                },

                // Create a new Graph object and add it to the graphs Collection of the Dashboard model.
                createGraph: function () {
                    var graphs = this.model.get('graphs');
                    graphs.add(new APP.ORION.Models.Graph);
                    return false;
                },

                // Create a new GraphView for the provided Graph and append it to the #graphs DIV.
                addGraph: function (graph) {
                    var newGraph = new APP.ORION.Views.GraphView({model: graph});
                    this.$('#graphs').append(newGraph.render().el);
                },

                // Call addGraph for all of the Graphs in the Dashboard model's graphs Collection.
                addAllGraphs: function () {
                    this.model.get('graphs').each(this.addGraph, this);
                },

                deleteDashboard: function () {

                    this.model.destroy({
                        success: function (model, response) {
                            log('success', model, response); // TODO - dbow - remove
                            // Redirect on success.
                            window.location = APP.Setup.get_base_path();
                        },
                        error: function (model, errors) {
                            var responseText = '<div>THERE WAS AN ERROR SAVING TO THE SERVER</div>';
                            if (errors.responseText) {
                                this.$('#error-box').html(_.escape(responseText + errors.responseText)).show();
                            }
                            $('#deleteModal').find('button.close').click();
                            log('error', model, errors); // TODO - dbow - remove
                        }
                    });

                },

                // Attempt to save Dashboard to the server (validates first).
                submitHandler: function () {

                    var isNew = this.model.isNew();

                    // Reset validation.
                    this.$('.alert').hide();
                    this.$('.control-group').removeClass('error');
                    this.$('.help-inline').text('');

                    $('.btn').addClass('disabled').attr('disabled', 'disabled');

                    $('#saving-box').show();
                    // Save Dashboard to the server.
                    this.model.save({}, {

                        // Show confirmation alert on success.
                        success: function (model, response) {
                            var responseText = isNew ? 'Dashboard Saved!' : 'Dashboard Updated!',
                                alertBox = $('#success-box');
                            $('.btn').removeClass('disabled').removeAttr('disabled');
                            $('#saving-box').hide();
                            alertBox.text(responseText).show();
                            if (isNew) {
                                window.history.pushState({},
                                                        "Orion",
                                                        APP.Setup.get_base_path() +
                                                        'index.php/orion/create_dashboard/' +
                                                        model.get('id'));
                            }
                            window.setTimeout(function () {
                                alertBox.fadeOut(3000);
                            }, 1000);
                            model.trigger('savedEvent');
                            log('success', model, response); // TODO - dbow - remove
                        },

                        // Show server error message on error.
                        error: function (model, errors) {
                            var responseText = '<div>THERE WAS AN ERROR SAVING TO THE SERVER</div>';
                            $('#saving-box').hide();
                            $('.btn').removeClass('disabled').removeAttr('disabled');
                            if (errors.responseText) {
                                $('#error-box').html(_.escape(responseText + errors.responseText)).show();
                            }
                            log('error', model, errors); // TODO - dbow - remove
                        }

                    });

                    return false;

                },

                // The HTML template for the DashboardView.
                template: function (obj) {
                    var templateHtml = $('#dashboard_template').html()
                                                               .replace('%7B%7B', '{{', 'g')
                                                               .replace('%7D%7D', '}}', 'g');
                    return Mustache.render(templateHtml, obj);
                },

                // Assemble UI data, pass it to the template to create the HTML, and render the HTML.
                render: function () {

                    var modelObj = this.model.toJSON(),
                        reorder,
                        // templateObj is an object that has other UI information that isn't part of the model.
                        // It is merged into the modelObj for the template.
                        templateObj = {
                            restricted_output: function () {
                                if (this.restricted === 1) {
                                    return " checked='checked'";
                                } else {
                                    return "";
                                }
                            },
                            dashboard_name_error: this.model.errors.dashboard_name,
                            category_name_error: this.model.errors.category_name
                        };

                    if (typeof this.model.errors.graphs === 'string') {
                        templateObj.graphs_error = this.model.errors.graphs;
                    }

                    // Set content of DashboardView element to template output and call addAllGraphs.
                    $(this.el).html(this.template(_.extend(modelObj, templateObj)));

                    this.addAllGraphs();

                    // Set up Drag and Drop reordering of graphs on the page.
                    reorder = function (start_pos, end_pos) {
                        this.model.get('graphs').reorder(start_pos, end_pos);
                    };
                    reorder = _.bind(reorder, this);
                    $('#graphs').sortable({
                        start: function(event, ui) {
                            var start_pos = ui.item.index();
                            ui.item.data('start_pos', start_pos);
                        },
                        update: function(event, ui) {
                            var start_pos = ui.item.data('start_pos'),
                                end_pos = $(ui.item).index(),
                                timeout;
                            timeout = window.setTimeout(function () {
                                reorder(start_pos, end_pos);
                                window.clearTimeout(timeout);
                            }, 10);
                        }
                    });

                    $('.metric_name_input:visible').not(":disabled").chosen();

                    return this;

                },

                // Initialization function called when DashboardView is created.
                initialize: function () {

                    // Set up graphs attribute event listeners.

                    // Call render when any Graph is added.
                    this.model.get('graphs').on('add', this.render, this);

                    // Call render when any Graph is removed.
                    this.model.get('graphs').on('remove', this.render, this);

                    // Re-render whenever a reset event is triggered (e.g. by sorting Graphs).
                    this.model.get('graphs').on('reset', this.render, this);


                    // Set up Dashboard Model event listeners.

                    // Re-render HTML after model syncs to server.
                    this.model.on('sync', this.render, this);
                    this.model.on('savedEvent', this.render, this);

                    // Re-render HTML whenever validation fails on Dashboard model.
                    this.model.on('validationFailed', this.render, this);


                    // Initial rendering of DashboardView HTML on creation.
                    this.render();

                }

            }),

            /**
             * APP.ORION.Views.GraphView
             * Backbone View for the form to create or edit a Graph model.
             *
             */
            GraphView: Backbone.View.extend({

                events: {
                    'change .graph-input': 'updateField',
                    'click .graph_close': 'removeGraph',
                    'click .add_metric': 'createMetric',
                    'click .copy_graph': 'copyGraph',
                    'click .graph_down, .graph_up': 'moveGraph'
                },

                className: 'clearfix',

                // When form input values change, update the Graph model attributes.
                updateField: function (e) {
                    var input = $(e.target),
                        val = input.is(':checkbox') ? input.is(':checked') + 0 : _.escape(input.val());
                    this.model.set(input.attr('id').replace(this.model.cid + '_', ''), val, {silent: true});
                    this.render();
                },

                // Remove GraphView object and trigger removeModel event on Graph model.
                removeGraph: function () {
                    this.remove();
                    this.model.trigger('removeModel', this.model);
                },

                // Trigger copyModel event on Graph model.
                copyGraph: function () {
                    this.model.trigger('copyModel', this.model);
                    return false;
                },

                // Determine direction clicked and trigger moveModel event on Graph model.
                moveGraph: function (e) {
                    var direction = $(e.target).hasClass('graph_down') ? 'down' : 'up';
                    this.model.trigger('moveModel', this.model, direction);
                    return false;
                },

                // Create a new Metric object and add it to the metrics Collection of the Graph model.
                createMetric: function () {
                    var metrics = this.model.get('metrics');
                    metrics.add(new APP.ORION.Models.Metric);
                    return false;
                },

                // Create a new MetricView for the provided Metric and append it to the .metrics_div DIV.
                addMetric: function (metric) {
                    var newMetric = new APP.ORION.Views.MetricView({model: metric});
                    this.$('.metrics_div').append(newMetric.render().el);
                    metric.trigger('renderChosen');
                },

                // Call addMetric for all of the Metrics in the Graph model's metrics Collection.
                addAllMetrics: function () {
                    this.model.get('metrics').each(this.addMetric, this);
                },

                // The HTML template for the GraphView.
                template: function (obj) {
                    return Mustache.render($('#graph_template').html(), obj);
                },

                // Assemble UI data, pass it to the template to create the HTML, and render the HTML.
                render: function () {

                    var modelObj = this.model.toJSON(),
                        // templateObj is an object that has other UI information that isn't part of the model.
                        // It is merged into the modelObj for the template.
                        templateObj = {
                            cid: this.model.cid,
                            is_half_size_output: function () {
                                if (this.is_half_size === 1) {
                                    return " checked='checked'";
                                } else {
                                    return "";
                                }
                            },
                            graph_name_error: this.model.errors.graph_name,
                            graph_catchall_error: this.model.errors.metric_catchall
                        };

                    templateObj.at_top = this.model.get('order') === 0;
                    templateObj.at_bottom = this.model.get('order') === APP.ORION.Dashboard.getNumGraphs() - 1;

                    if (typeof this.model.errors.metrics === 'string') {
                        templateObj.metrics_error = this.model.errors.metrics;
                    }

                    // Set content of GraphView element to template output and call addAllMetrics.
                    $(this.el).html(this.template(_.extend(modelObj, templateObj)));

                    this.addAllMetrics();

                    this.model.get('metrics').trigger('renderChosen');

                    return this;

                },

                // Initialization function called when GraphView is created.
                initialize: function () {

                    // Set up metrics attribute event listeners.

                    // Call addMetric when any Metric is added.
                    this.model.get('metrics').on('add', this.addMetric, this);


                    // Set up Graph Model event listeners.

                    // Re-render HTML whenever Graph attributes change.
                    this.model.on('change', this.render, this);

                }

            }),

            /**
             * APP.ORION.Views.MetricView
             * Backbone View for the form to create or edit a Metric model.
             *
             */
            MetricView: Backbone.View.extend({

                events: {
                    'click .metric_close': 'removeMetric',
                    'click .copy_metric': 'copyMetric',
                    'change .metric_name_input': 'updateMetricOptions',
                    'change .metric_from_units, .metric_until_units, .metric_from, .metric_until': 'updateFields',
                    'click .metric_other_period_add': 'addOtherPeriod',
                    'click .metric_other_period_remove': 'removeOtherPeriod',
                    'click .metric_other_period_clear': 'clearOtherPeriods',
                    'change .metric_other_period': 'render'
                },

                className: 'clearfix',

                // Set metric_name to the resulting string from METRIC_SELECT_FIELD inputs.
                updateMetricName: function () {

                    var sel = this.selectedFields,
                        fieldVal,
                        delimiter = '',
                        nameString = METRIC_PREFIX;

                    for (var i = 0, len = ORDER_ARRAY.length; i < len; i++) {
                        fieldVal = sel[_.indexOf(ORDER_ARRAY, i)];
                        nameString += fieldVal ? delimiter + fieldVal : delimiter + '_';
                        delimiter = '.';
                    }

                    this.model.set({metric_name: nameString}, {silent: true});

                    this.model.catchAll = this.model.get('metric_name').indexOf('.*') >= 0;
                    if (this.model.catchAll) {
                        this.model.set('other_period_offsets', [0], {silent: true});
                    }
                    APP.ORION.Dashboard.checkCatchAll();

                    this.render();

                },

                // Populates the metricSelectFields array for each of the selectors in the UI
                // based on currently selected options, and available fields in _metricObject.
                updateMetricOptions: function (e, changedVal, selectedVal) {

                    var metrics = this.metricSelectFields,
                        changed = typeof changedVal === 'string' ? changedVal : e.target.id.replace(this.model.cid + '_metric_name_', ''),
                        selected = selectedVal || changedVal.selected,
                        changeIndex = METRIC_SELECT_FIELDS.indexOf(changed),
                        index = 0,
                        sel;

                    this.selectedFields = this.selectedFields.slice(0, changeIndex);

                    // If 'None' is selected, act as if the next level up was selected.
                    if (selected === 'None') {
                        changeIndex -= 1;
                        selected = this.selectedFields[changeIndex];
                    } else {
                        this.selectedFields.push(selected);
                    }

                    sel = this.selectedFields;

                    // Recursive function that populates the UI
                    // fieldObject is the relevant nested level of _metricObject
                    function _updateSelector(fieldObject) {

                        var element = METRIC_SELECT_FIELDS[index],
                            uiObject = _.find(metrics, function (uiObj) { return uiObj.name === element; }),
                            elementObj = uiObject.options,
                            oldSelectUpdate = false,
                            newSelectUpdate = false,
                            selectedObj;

                        // If this is the field updated, change currently selected value.
                        if (index === changeIndex) {

                            for (var i = 0, len = elementObj.length; i < len; i++) {
                                if (elementObj[i].selected !== '') {
                                    elementObj[i].selected = '';
                                    oldSelectUpdate = true;
                                }
                                if (elementObj[i].option === selected) {
                                    elementObj[i].selected = 'selected';
                                    newSelectUpdate = true;
                                }
                                // Break early if old and new updates have been made.
                                if (oldSelectUpdate && newSelectUpdate) {
                                    break;
                                }
                            }

                        // Empty fields after the currently updated one.
                        } else if (index > changeIndex) {
                            uiObject.options = [];

                            // If this is the next field after the one updated, populate its options.
                            if (index === (changeIndex + 1)) {
                                uiObject.disabled = '';
                                for (var field in fieldObject) {
                                    if (fieldObject.hasOwnProperty(field)) {
                                        uiObject.options.push({
                                            option: field,
                                            selected: ''
                                        });
                                    }
                                }

                            // Otherwise disable it.
                            } else {
                                uiObject.disabled = 'disabled';
                            }

                        }

                        // If current index is within the fields selected, go to next level of fieldObject.
                        if (index <= sel.length) {
                            selectedObj = fieldObject[sel[index]];
                        }
                        index += 1;

                        // If there are still fields to process, call _updateSelector again.
                        if (index < METRIC_SELECT_FIELDS.length) {
                            _updateSelector(selectedObj);
                        }

                    }

                    _updateSelector(_metricObject);

                    this.updateMetricName();

                },

                // When from or until input values change, update the Metric model attributes.
                updateFields: function () {

                    var from = parseInt(this.$('.metric_from').val(), 10) *
                               parseInt(this.$('.metric_from_units').val(), 10),
                        until = parseInt(this.$('.metric_until').val(), 10) *
                                parseInt(this.$('.metric_until_units').val(), 10);
                    if (isNaN(from)) {
                        from = '';
                    }
                    if (isNaN(until)) {
                        until = '';
                    }
                    this.model.set({
                        from: from,
                        until: until
                    }, {silent: true});

                },

                // Add an integer to other_period_offsets attribute based on what user selects.
                addOtherPeriod: function () {

                    var currOffsets = this.model.get('other_period_offsets'),
                        selector = this.$('.metric_other_period'),
                        newVal;
                    if (currOffsets[0] === 0) {
                        currOffsets = [];
                    }
                    if (selector.val() === 'last') {
                        newVal = 1;
                    } else {
                        newVal = parseInt(this.$('.metric_other_period_days').val(), 10) * -1;
                        if (isNaN(newVal) || newVal >= 0) {
                            this.model.errors.other_period_offsets = 'Offset must be a positive number greater than 0';
                            this.render();
                            return false;
                        }
                    }
                    currOffsets.push(newVal);
                    this.model.set('other_period_offsets', currOffsets, {silent: true});
                    this.render();
                    return false;

                },

                // Remove offset integer from other_period_offsets array.
                removeOtherPeriod: function (e) {

                    var periodVal = parseInt($(e.target).parent().attr('id').split('_op_')[0], 10),
                        currOffsets = this.model.get('other_period_offsets');
                    currOffsets.splice(currOffsets.indexOf(periodVal), 1);
                    if (currOffsets.length === 0) {
                        currOffsets = [0];
                    }
                    this.model.set('other_period_offsets', currOffsets, {silent: true});
                    this.render();
                    return false;

                },

                // Reset other_period_offsets array to [0].
                clearOtherPeriods: function () {
                    this.model.set('other_period_offsets', [0], {silent: true});
                    this.render();
                    return false;
                },

                // Trigger copyModel event on Metric model.
                copyMetric: function () {
                    this.model.trigger('copyModel', this.model);
                    return false;
                },

                // Remove MetricView object and trigger removeModel event on Metric model.
                removeMetric: function () {
                    this.remove();
                    this.model.trigger('removeModel', this.model);
                },

                callChosen: function () {
                    this.$('.metric_name_input:visible').not(":disabled").chosen();
                },

                // The HTML template for the MetricView.
                template: function (obj) {
                    return Mustache.render($('#metric_template').html(), obj);
                },

                // Assemble UI data, pass it to the template to create the HTML, and render the HTML.
                render: function () {

                    var modelObj = this.model.toJSON(),
                        selector = this.$('.metric_other_period').val(),
                        offsets = modelObj.other_period_offsets,
                        other_period_offset_item,
                        // templateObj is an object that has other UI information that isn't part of the model.
                        // It is merged into the modelObj for the template.
                        templateObj = {
                            cid: this.model.cid,
                            metric_select_fields: this.metricSelectFields,
                            is_last: false,
                            is_this: false,
                            has_last: true,
                            clear_items: false,
                            is_addable: false,
                            other_period_offset_items: [],
                            errors: this.model.errors,
                            from_days: function () {
                                if (this.from % 24 === 0) {
                                    return 'selected';
                                }
                            },
                            until_days: function () {
                                if (this.until % 24 === 0) {
                                    return 'selected';
                                }
                            },
                            from_val: function () {
                                if (this.from % 24 === 0) {
                                    return this.from / 24;
                                } else {
                                    return this.from;
                                }
                            },
                            until_val: function () {
                                if (this.until % 24 === 0) {
                                    return this.until / 24;
                                } else {
                                    return this.until;
                                }
                            },
                            catchAll: this.model.catchAll
                        };

                    for (var i = 0, selectField; i < templateObj.metric_select_fields.length; i++) {
                        selectField = templateObj.metric_select_fields[i];
                        selectField.error = this.model.errors[selectField.name];
                    }

                    // templateObj property adjustments related to existing offset periods added.
                    if (offsets[0] !== 0) {
                        for (var j = 0, len = offsets.length; j < len; j++) {
                            other_period_offset_item = {val: offsets[j]};
                            if (offsets[j] === 1) {
                                other_period_offset_item.text = 'Last Period';
                                templateObj.has_last = false;
                            } else {
                                other_period_offset_item.text = 'This Period ' + (offsets[j]*-1) + ' hours ago';
                            }
                            templateObj.other_period_offset_items.push(other_period_offset_item);
                        }
                        templateObj.clear_items = true;
                    }

                    // templateObj property adjustments related to the currently selected period.
                    if (selector === 'this') {
                        templateObj.is_this = 'selected="selected"';
                        templateObj.is_addable = true;
                    } else if (selector === 'last') {
                        templateObj.is_last = 'selected="selected"';
                        if (templateObj.has_last) {
                            templateObj.is_addable = true;
                        }
                    }

                    // Set content of MetricView element to template output
                    $(this.el).html(this.template(_.extend(modelObj, templateObj)));

                    // Undo other_period_offset error after rendering.
                    if (this.model.errors.other_period_offsets) {
                        delete this.model.errors.other_period_offsets;
                    }

                    this.callChosen();

                    return this;

                },

                // Initialization function called when MetricView is created.
                initialize: function () {

                    // Set up Metric Model event listeners.

                    // Re-render HTML whenever Metric attributes change.
                    this.model.on('change', this.render, this);

                    this.model.on('renderChosen', this.callChosen, this);


                    // Set up metric select field values.

                    // Platform options populated and first option selected by default.
                    var selected,
                        sel = 'selected',
                        i,
                        uiLen,
                        fieldObj;

                    /**
                     * Array for handling dynamic metric_name fields in UI.  Looks like:
                     *   [
                     *     {
                     *       name: string,
                     *       order: integer,
                     *       options: [ { option: string, selected: string } ],
                     *       disabled: string,
                     *       first: boolean
                     *     }
                     *   ]
                     *
                     */
                    this.metricSelectFields = [];
                    this.selectedFields = [];

                    for (i = 0, uiLen = METRIC_SELECT_FIELDS.length; i < uiLen; i++) {
                        this.metricSelectFields.push({
                            name: METRIC_SELECT_FIELDS[i],
                            order: i,
                            options : [],
                            disabled: 'disabled',
                            first: i === 0
                        });
                    }

                    i = 0;
                    fieldObj = this.metricSelectFields[i];
                    fieldObj.disabled = '';
                    for (var field1 in _metricObject) {
                        if (_metricObject.hasOwnProperty(field1)) {
                            if (sel) {
                                this.selectedFields.push(field1);
                                selected = field1;
                            }
                            fieldObj.options.push({
                                option: field1,
                                selected: sel
                            });
                            sel = '';
                        }
                    }

                    // Game options populated for the selected first field.
                    i += 1;
                    fieldObj = this.metricSelectFields[i];
                    fieldObj.disabled = '';
                    for (var field in _metricObject[selected]) {
                        if (_metricObject[selected].hasOwnProperty(field)) {
                            fieldObj.options.push({
                                option: field,
                                selected: ''
                            });
                        }
                    }

                    // If object already has selected properties, populate them
                    var metric_name = this.model.get('metric_name');
                    if (metric_name) {
                        var itemObj = APP.ORION.Views.parseMetricName(metric_name),
                            uiField;
                        for (var j = 0; j < uiLen; j++) {
                            uiField = METRIC_SELECT_FIELDS[j];
                            if (itemObj[uiField] && itemObj[uiField] !== '_') {
                                this.selectedFields.push(itemObj[uiField]);
                                this.updateMetricOptions(null, uiField, itemObj[uiField]);
                            }
                        }
                    }

                }

            }),

            /**
             * APP.ORION.Views.parseMetricName
             *
             * Each metric String represents the nesting in Graphite for that table, with folder name strings
             * separated by periods (e.g. 'plants.vegetables.legumes.peanuts')
             *
             * This function removes a METRIC_PREFIX (if any), splits the name on periods, and then
             * creates an object with METRIC_SELECT_FIELDS as keys, and values extracted from the string
             * based on ORDER_ARRAY.
             *
             */
            parseMetricName: function (metricString) {

                var shortString = METRIC_PREFIX ? metricString.replace(METRIC_PREFIX, '') : metricString,
                    metricParts = shortString.split('.'),
                    numParts = metricParts.length,
                    numFields = METRIC_SELECT_FIELDS.length,
                    field,
                    fieldIndex,
                    itemObj = {};

                for (var i = 0; i < numFields; i++) {
                    field = METRIC_SELECT_FIELDS[i];
                    fieldIndex = ORDER_ARRAY[i];
                    // Return undefined if string doesn't have all the parts defined in the config.
                    if (!metricParts[fieldIndex]) {
                        return;
                    }
                    itemObj[field] = metricParts[fieldIndex];
                }

                // Concatenate any remaining parts after all fields are defined.
                if (numParts > numFields) {

                    while (i < numParts) {
                        itemObj[_lastField] += '.' + metricParts[i];
                        i++;
                    }
                }

                return itemObj;

            },

            // Setter function for METRIC_PREFIX
            setMetricPrefix: function (metricPrefix) {
                if (metricPrefix && metricPrefix !== '.') {
                    METRIC_PREFIX = metricPrefix;
                }
            },

            // Setter function for METRIC_CONFIG
            setMetricConfig: function (metricConfigObj) {
                METRIC_CONFIG = metricConfigObj;
            },

            // Setter function for METRIC_ARRAY
            setMetricArray: function (metricArray) {
                METRIC_ARRAY = JSON.parse(metricArray);
            },

            /**
             * APP.ORION.Views.setup
             * Parse the metrics config and array and create a DashboardView.
             */
            setup: function () {

                _parseConfig();
                _parseMetrics();

                // Create a view based on the top-level Dashboard object
                APP.ORION.DashboardView = new APP.ORION.Views.DashboardView({
                    model: APP.ORION.Dashboard
                });

            }

        };

    }());


}());

