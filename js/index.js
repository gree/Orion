// Copyright 2012 GREE Inc. All Rights Reserved.

/**
 * @fileoverview The index.js file contains code for the Orion homepage / Dashboard view pages.
 *
 *      It handles UI interactions to load graph data via AJAX calls, and then parse the result
 *      from the server and create HighCharts graphs in the UI.  The Time module has convenience
 *      functions for time conversions; the Graphs module parses data and creates HighCharts; and
 *      the UI module handles user interactions and makes calls to the server.
 *
 * @author <daniel.bowman@gree.co.jp> Danny Bowman
 * @author <karan.kurani@gree.co.jp> Karan Kurani
 *
 */


/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {};


(function () {

    var _isMobile = false;

    // INDEX namespace for code specific to the dashboard view pages.
    APP.INDEX = {};


    /**
     * APP.INDEX.Time
     * Module with utility functions for manipulating time values.
     */
    APP.INDEX.Time = (function () {

        var ONE_HOUR_IN_MS = 1000 * 60 * 60;

        return {

            getHourDifference: function (fromDate, toDate) {

                // Calculate the difference in milliseconds
                var diffInMs = Math.abs(fromDate.getTime() - toDate.getTime());

                // Convert back to hours and return
                return Math.round(diffInMs / ONE_HOUR_IN_MS);

            },

            offsetForward: function (timestamp, offset) {

                if (offset) {
                    timestamp += Math.abs(offset);
                }
                return timestamp;

            },

            offsetBackward: function (timestamp, offset) {

                if (offset) {
                    timestamp -= (offset * 60 * 60);
                }
                return timestamp;

            }

        };

    }());


    /**
     * APP.INDEX.Graphs
     * Module handling High Charts graph functionality.
     */
    APP.INDEX.Graphs = (function () {

        var _updateTimeoutArray = [],
            _charts = [],
            _canEdit;

        // Crockford's array detection function.
        function _isArray (patrickTraughber) {
            return patrickTraughber && typeof patrickTraughber === 'object' && patrickTraughber.constructor === Array;
        }

        return {

            // Set _canEdit variable if it isn't already set.
            setPermission: function (permission) {

                if (_canEdit === undefined) {
                    _canEdit = permission;
                }

            },

            // Creates graphs for the provided dashboard object from the server.
            create: function (dashboardObject) {

                var categoryName = _.escape(dashboardObject.category_name),
                    dashboardName = _.escape(dashboardObject.dashboard_name),
                    dashboardId = dashboardObject.id,
                    graphs = dashboardObject.graphs,
                    link = '',
                    dashboardHeader = categoryName.toUpperCase(),
                    now,
                    fromVal,
                    untilVal,
                    i,
                    numGraphs,
                    graph,
                    metrics,
                    allMetricSeries,
                    allMetricInfo,
                    k,
                    numMetrics,
                    metric,
                    metricOffset,
                    graphDivId,
                    currentHalf = "left",
                    zoomType = 'x',
                    mouseTracking = true,
                    titleText = 'Metric Value',
                    chart;

                // Reorder graphs on frontend.
                graphs = _.sortBy(graphs, 'order');

                APP.INDEX.UI.setDashboardInfo(categoryName, dashboardName);

                // Call destroy on all previous charts.
                for (var j = 0, numCharts = _charts.length; j < numCharts; j++) {
                    if (typeof _charts[j].destroy === 'function') {
                        _charts[j].destroy();
                    }
                }
                _charts = [];

                // Update UI with new graph information.

                // Update category dropdown to current dashboard.
                $('.dropdown').removeClass('active');
                $('#' + categoryName).parent().addClass('active');

                // Add edit link if user can edit and dashboard exists in database.
                if (_canEdit && dashboardId) {
                    link = '<a href="' + APP.Setup.get_base_path() +
                           'index.php/orion/create_dashboard/' + _.escape(dashboardId) +
                           '" class="btn">Edit</a>';
                }

                if (dashboardName) {
                    dashboardHeader += '<small>' + dashboardName + link + '</small>';
                }
                // Update graph title to current dashboard.
                $('#graph_title').html(dashboardHeader);

                // Reset result DIV.
                $("#graphs").empty().html('<div id="result" ></div>');
                $('#datepickers').show();

                APP.INDEX.UI.setMissingId(!dashboardId);

                if (!dashboardId) {
                    $('#graph_title').addClass('view_metric');
                    // Set datepicker values based on from and until values.
                    now = new Date().getTime();
                    fromVal = new Date(now + (graphs[0].metrics[0].from * 3600000) - 25200000); // Minus 7 hours for toISOString
                    untilVal = new Date(now + (graphs[0].metrics[0].until * 3600000) - 28800000); // Minus 8 hours for toISOString
                    $("#startDate").val(fromVal.toISOString().split('T')[0]);
                    $("#endDate").val(untilVal.toISOString().split('T')[0]);
                } else {
                    $('#graph_title').removeClass('view_metric');
                }

                // Create a HighChart for each graph in the dashboard
                for (i = 0, numGraphs = graphs.length; i < numGraphs; i++) {

                    graph = graphs[i];
                    metrics = graph.metrics;
                    allMetricSeries = [];
                    allMetricInfo = [];

                    // Assemble arrays for each metric in the graph.
                    for (k = 0, numMetrics = metrics.length; k < numMetrics; k++) {

                        metric = metrics[k];
                        metricOffset = null;
                        if (metric.hasOwnProperty('offset')) {
                            metricOffset = metric.offset;
                        }
                        allMetricSeries.push({
                            data: metric.datapoints,
                            name: metric.display_name
                        });
                        allMetricInfo.push({
                            metric_name: metric.metric_name,
                            offset: metricOffset,
                            index: k
                        });

                    }

                    // Create graph container DIV.
                    graphDivId =  _.escape(graph.graph_name) + "." + i;
                    if (graph.is_half_size === undefined) {
                        graph.is_half_size = 0;
                    }
                    if (graph.is_half_size === 0 ) {
                        $("#result").append("<div id='" + graphDivId + "' class='full_size'></div>");
                        currentHalf = "left";
                    } else {
                        $("#result").append("<div id='" + graphDivId + "' class='half_size_" + currentHalf + "'></div>");
                        if (currentHalf === "left") {
                            currentHalf = "right";
                        } else {
                            currentHalf = "left";
                        }
                    }

                    // Disable selection zoom if on a small screen.
                    if (_isMobile) {
                        zoomType = '';
                        mouseTracking = false;
                        titleText = null;
                    }

                    // Create HighChart for the graph in the graph container DIV.
                    chart = new Highcharts.StockChart({
                        chart: {
                            renderTo: graphDivId,
                            type: 'spline',
                            zoomType: zoomType,
                            shadow: true,
                            height: 392,
                            events: {
                                load: function () {
                                    APP.INDEX.Graphs.updateGraph(this, allMetricInfo);
                                }
                            }
                        },
                        loading: {
                            hideDuration: 100
                        },
                        rangeSelector: {
                            enabled: false
                        },
                        title: {
                            text: graph.graph_name,
                            align: 'left',
                            x: 30,
                            y: 20
                        },
                        xAxis: {
                            title: {
                                text: 'Time'
                            },
                            type: 'datetime'
                        },
                        legend: {
                            enabled: true,
                            align: 'right',
                            borderColor: 'black',
                            borderWidth: 2,
                            layout: 'vertical',
                            verticalAlign: 'top',
                            shadow: true,
                            floating: true,
                            x: -1,
                            y: -1,
                            labelFormatter: function () {
                                var name = this.name,
                                    name_array = name.split("."),
                                    name_sliced = name_array.slice(3, (name_array.length)).join("."),
                                    metricObj = _.find(metrics, function(metric) {
                                        return metric.display_name === name;
                                    }),
                                    link = '<a href="' + APP.Setup.get_base_path() +
                                           'index.php/orion/view_metric?metric=' +
                                           metricObj.metric_name + '&from=' + metricObj.from +
                                           '&until=' + metricObj.until +
                                            '" style="color:#0898d9;text-decoration:underline;">[view]</a>';
                                return name_sliced + ' ' + link;
                            }
                        },
                        plotOptions: {
                            spline: {
                                lineWidth: 1,
                                enableMouseTracking: mouseTracking
                            },
                            series: {
                                marker: {
                                    enabled: false,
                                    states: {
                                        hover: {
                                            enabled: true
                                        }
                                    }
                                },
                                enableMouseTracking: mouseTracking
                            }
                        },
                        series: allMetricSeries,
                        yAxis: {
                            title: {
                                text: titleText
                            }
                        }
                    });

                    _charts.push(chart);

                }

                // If on a small screen, append a DIV over top of charts to enable easy touch scrolling.
                if (_isMobile) {
                    $('#graphs').append('<div id="mobile_scroller"></div>');
                    $('#mobile_scroller').height($('#graphs').height());
                }

            },

            // Update graph every 20 seconds by calling updateMetric for each metric.
            updateGraph: function (chart, allMetricInfo) {

                var allMetricSeries = chart.series,
                    i,
                    seriesLen,
                    metricSeries,
                    metricInfo,
                    k,
                    infoLen,
                    callSelf;

                for (i = 0, seriesLen = allMetricSeries.length; i < seriesLen; i++) {

                    metricSeries = allMetricSeries[i];
                    metricInfo = null;

                    for (k = 0, infoLen = allMetricInfo.length; k < infoLen; k++) {
                        if (allMetricInfo[k].index == metricSeries.index ) {
                            metricInfo = allMetricInfo[k];
                            break;
                        }
                    }
                    if (metricSeries.name === "Navigator") {
                        continue;
                    }
                    APP.INDEX.Graphs.updateMetric(metricSeries, metricInfo);

                }

                callSelf = function () {
                    APP.INDEX.Graphs.updateGraph(chart, allMetricInfo);
                };

                _updateTimeoutArray.push(setTimeout(callSelf, 20000));

            },

            // Call server for latest data for the given metric and update chart.
            updateMetric: function (metric_series, metric_info) {

                var lastUpdatedTs = new Date(metric_series.xData[metric_series.xData.length - 1 ]),
                    timeSinceUpdate = APP.INDEX.Time.getHourDifference(new Date(lastUpdatedTs), new Date()),
                    untilTime = APP.INDEX.Time.offsetForward(timeSinceUpdate, metric_info.offset),
                    fromTime = untilTime + 1;

                $.ajax({
                    url: APP.Setup.get_base_path() + "index.php/orion/get_latest_data",
                    type: 'GET',
                    data: {
                        metric_name: metric_info.metric_name,
                        from: fromTime,
                        until: untilTime
                    },
                    success: function (data) {

                        var dataReturned;

                        if (!data) {
                            log("No data");
                            return;
                        }

                        data = $.parseJSON(data);
                        dataReturned = data[0];

                        if (dataReturned.datapoints.length > 0) {

                            var point,

                                // Get the latest datapoint timestamp currently in the series
                                maxOldPointTime = metric_series.xData[metric_series.xData.length - 1 ],

                                // Adjust all the datapoint timestamps by the offset
                                convertedData = _.map(dataReturned.datapoints, function (point) {
                                    point[1] = APP.INDEX.Time.offsetBackward(point[1], metric_info.offset)*1000;
                                    return point;
                                }),

                                // Return any points that have a value and are more recent than maxOldPointTime
                                filteredData = _.filter(convertedData, function (point) {
                                    return point[0] !== undefined && point[0] !== null && point[1] > maxOldPointTime;
                                });

                            // For each point in filteredData, add to the graph and shift it over.
                            for (var i = 0, len = filteredData.length; i < len; i++) {
                                point = filteredData[i];
                                metric_series.addPoint([point[1], point[0]], true, true);
                            }

                        }

                    }
                });

            },

            // Clear array of timeouts for graph updates.
            clearGraphTimeouts: function () {

                if (_isArray(_updateTimeoutArray)) {

                    for (var i = 0, len = _updateTimeoutArray.length; i < len; i++) {
                        clearTimeout(_updateTimeoutArray[i]);
                    }
                    _updateTimeoutArray = [];

                }

            }

        };

    })();


    /**
     * APP.INDEX.UI
     * Module handling user interaction.
     */
    APP.INDEX.UI = (function () {

        var _categoryName,
            _dashboardName,
            _initialDashboard,
            _idMissing = false;

        return {

            setDashboardInfo: function (category, dashboard) {
                _categoryName = category;
                _dashboardName = dashboard;
            },

            setInitialDashboard: function (dashboardJson) {
                _initialDashboard = dashboardJson;
            },

            setMissingId: function (missingId) {
                _idMissing = missingId;
            },

            // Construct URL for dashboard and make AJAX call.
            loadDashboard: function (category, dashboard, from, until) {

                var dashboardUrl = APP.Setup.get_base_path() + 'index.php/orion/index/' + category + '/' + dashboard,
                    progressWidth = 10,
                    progressInterval;

                window.history.pushState({}, "Orion", dashboardUrl);

                APP.INDEX.Graphs.clearGraphTimeouts();

                // Clear UI of previous graph info.

                $('#datepickers').hide();
                $('#server-error').hide();

                if (!from && !until) {
                    $('.datepicker').val("");
                }

                $('#graph_title').html('');
                $("#graphs").empty()
                            .show()
                            .html('<div class="progress progress-striped active progress-info">' +
                                  '<div class="bar" style="width: ' + progressWidth + '%;"></div></div>');

                // Show loading progress bar - note: not tied to any actual progress :)
                progressInterval = window.setInterval(function () {
                    if (progressWidth < 80) {
                        $('.progress > .bar').width((progressWidth += 5) + '%');
                    } else {
                        clearInterval(progressInterval);
                    }
                }, 40);


                // Make server call for dashboard info.
                $.ajax({
                    url: APP.Setup.get_base_path() + 'index.php/orion/get_dashboard_graphs',
                    type: 'GET',
                    data: {
                        dashboard: dashboard,
                        category: category,
                        from: from,
                        until: until
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (progressInterval) {
                            clearInterval(progressInterval);
                        }
                        log('success', data);
                        APP.INDEX.Graphs.create(data);
                    },
                    error: function (error) {
                        if (progressInterval) {
                            clearInterval(progressInterval);
                        }
                        log('error', error);
                        $("#graphs").empty();
                        $('#server-error').html(_.escape(error.responseText)).show();
                    }
                });

            },

            // Initial setup of UI interactions and components.
            init: function () {

                // There's an @media CSS rule in style.css that sets the width of
                //   the is_mobile div to 5 if the screen is less than 768px
                if ($('#is_mobile').width() === 5) {
                    _isMobile = true;
                }

                // Initiate datepicker module for start and end fields.
                $("#startDate").datepicker({
                    dateFormat: 'yy-mm-dd',
                    altFormat: '@',
                    maxDate: "-1d"
                });
                $("#endDate").datepicker({
                    dateFormat: 'yy-mm-dd',
                    altFormat: '@',
                    maxDate: "-0d"
                });

                // Assume datetimes are already in local timezone from server.
                Highcharts.setOptions({
                    global: {
                        useUTC: false
                    }
                });

                // If graph data passed to page, load it in.
                if (_initialDashboard) {
                    $('#graphs').show();
                    APP.INDEX.Graphs.create($.parseJSON(_initialDashboard));
                }

                // Click handler for date picker form submit button.
                $("#submit").click(function () {

                    var currTime = new Date(),
                        startTime = new Date(Date.parse($("#startDate").val() + 'T00:00:00-00:00') +
                                    new Date().getTimezoneOffset() * 60000),
                        endTime = new Date(Date.parse($("#endDate").val() + 'T00:00:00-00:00') +
                                  new Date().getTimezoneOffset() * 60000),
                        startHrDiff = APP.INDEX.Time.getHourDifference(startTime, currTime),
                        endHrDiff = APP.INDEX.Time.getHourDifference(endTime, currTime),
                        url;

                    // TODO - dbow - can probably remove as the datepicker module does this via the UI.
                    // Checks that times are not later than current time.
                    if (startTime > new Date(new Date().getTime() - 1000 * 60 * 60 * 24) || endTime > new Date()) {

                        alert("The start date must be up to and including yesterday, " +
                              "whilst the end date must be up to and including today. " +
                              "Please change the dates and resubmit.");

                    } else if (startHrDiff < endHrDiff){

                        alert("The end date must not before the start date. Please change the dates and resubmit.");

                    } else {

                        // Reload graphs with time period filter.
                        endHrDiff = endHrDiff - 24;
                        if (endHrDiff < 0) {
                            endHrDiff = 0;
                        }

                        if (_idMissing) {
                            // Handling for View Metric graphs
                            url = APP.Setup.get_base_path() +
                                  'index.php/orion/view_metric?metric=' +
                                  _categoryName + '&from=' + startHrDiff*-1 +
                                  '&until=' + endHrDiff*-1;
                            window.location = url;
                        } else {
                            APP.INDEX.UI.loadDashboard(_categoryName, _dashboardName, startHrDiff, endHrDiff);
                        }

                    }

                    // Do not actually submit form.
                    return false;

                });

                // On selecting a graph from a dropdown, call loadDashboard with the graph & dashboard names.
                $(document).on('click', '.dashboard-retrieve', function (e) {

                    var anchor = $(e.target),
                        dashboardName = anchor.text(),
                        categoryName = anchor.parent().parent().siblings('a.dropdown-toggle').text();

                    APP.INDEX.UI.loadDashboard(categoryName, dashboardName);

                });

                // Adding hover triggering for dashboard dropdowns.
                $('.dropdown-toggle').hover(
                    function (e) {
                        $(e.target).click();
                    },
                    function () {
                        // Don't close on exit.
                    }
                );

                // If anywhere outside an open dropdown menu is clicked, hide it.
                $(document).on('click', function (e) {
                    if (!$(e.target).hasClass('.dropdown-menu')) {
                        $('.dropdown-menu').hide();
                    }
                });

            }

        };

    })();


}());
