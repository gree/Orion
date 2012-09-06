<?php

/**
 * create_dashboard.php
 * Allows the creation and editing of dashboards
 */

$user = json_decode($this->session->userdata('user'));

$this->load->view('header');
?>

<div class="container" id="container">
    <div class="row">
        <div class="span12" id="dashboard_create_form_container">

        </div>
    </div>
</div>


<!-- Mustache Templates for different creation forms -->

<div id="dashboard_template" class="hidden">

    <form id="create_dashboard_form" class="form-horizontal">

        <input type="hidden" name="id" id="id" value="{{ id }}"></input>

        <fieldset>
            <legend>Create a Dashboard</legend>

            <div class="control-group {{#dashboard_name_error}}error{{/dashboard_name_error}}">
                <label class="control-label" for="dashboard_name">Dashboard Name:</label>
                <div class="controls">
                    <input type="text" id="dashboard_name" class="input-xlarge form-input" value="{{ dashboard_name }}">
                    <span class="help-inline">{{dashboard_name_error}}</span>
                </div>
            </div>

            <div class="control-group {{#category_name_error}}error{{/category_name_error}}">
                <label class="control-label" for="category_name">Category Name:</label>
                <div class="controls">
                    <input type="text" id="category_name" class="input-xlarge form-input" value="{{ category_name }}">
                    <span class="help-inline">{{category_name_error}}</span>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="restricted">Restricted Dashboard</label>
                <div class="controls">
                    <label class="checkbox">
                        <input type="checkbox" id="restricted" class="form-input" {{restricted_output}}>
                        Make the dashboard private?
                    </label>
                </div>
            </div>

            <div class="span10 offset1">

                <div id="graphs"></div>

                <hr />
                <div class="control-group {{#graphs_error}}error{{/graphs_error}}">
                    <span class="help-inline">{{graphs_error}}</span>
                </div>
                <button class="btn btn-warning" id="add_graph">Add graph</button>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Dashboard</button>
                {{#id}}<a href="<?php echo base_url(); ?>index.php/orion/index/{{category_name}}/{{dashboard_name}}" class="btn btn-success">View Dashboard</a>{{/id}}
                <?php if ($user && $user->perm_delete) { ?>
                {{#id}}<a data-toggle="modal" href="#deleteModal" class="btn btn-danger">Delete Dashboard</a>{{/id}}
                <?php } ?>
                <span id="success-box" class="alert alert-success"></span>
                <span id="saving-box" class="alert alert-info">Saving...</span>
                <div id="error-box" class="alert alert-error"></div>
            </div>

            <?php if ($user && $user->perm_delete) { ?>
            <div class="modal hide fade" id="deleteModal">
                <div class="modal-header">
                    <button class="close" data-dismiss="modal">×</button>
                    <h3>Are you sure you want to delete this dashboard?</h3>
                </div>
                <div class="modal-body">
                    <p>If you want to proceed with deleting Dashboard {{id}}, click 'Delete' below.</p>
                </div>
                <div class="modal-footer">
                    <a href="#" id="dashboard_delete" class="btn btn-primary">Delete</a>
                </div>
            </div>
            <?php } ?>

        </fieldset>

    </form>

</div>




<div id="graph_template" class="hidden">

    <hr />

    <span class="graph_name_banner">{{ graph_name}}</span>
    <div class="graph_container well">

        <button class="close graph_close" id="{{ cid }}_graph_close">&times;</button>
        {{^at_bottom}}<button class="btn graph_down">↓</button>{{/at_bottom}}
        {{^at_top}}<button class="btn graph_up">↑</button>{{/at_top}}

        <form class="horizontal-form graph-form">
            <fieldset>

                <input type="hidden" name="{{ cid }}_graph_id" id="{{ cid }}_graph_id" class="graph_id" value="{{ id }}"></input>

                <div class="control-group {{#graph_name_error}}error{{/graph_name_error}}">
                    <label class="control-label" for="{{ cid }}_graph_name">Graph Name:</label>
                    <div class="controls">
                        <input type="text" id="{{ cid }}_graph_name" class="input-xlarge graph_name graph-input" value="{{ graph_name}}">
                        <span class="help-inline">{{graph_name_error}}</span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="{{ cid }}_is_half_size">Half Size</label>
                    <div class="controls">
                        <label class="checkbox">
                            <input type="checkbox" id="{{ cid }}_is_half_size" class="is_half_size graph-input" {{is_half_size_output}}>
                            Make the graph fill only half the page?
                        </label>
                    </div>
                </div>

                <div class="span8 offset1">

                    <div id="metrics_{{ cid }}" class="metrics_div"></div>

                    <div class="control-group {{#metrics_error}}error{{/metrics_error}}">
                        <span class="help-inline">{{metrics_error}}</span>
                    </div>
                    <div class="control-group {{#graph_catchall_error}}error{{/graph_catchall_error}}">
                        <span class="help-inline">{{graph_catchall_error}}</span>
                    </div>

                    <button class="btn btn-info add_metric" id="add_metric_{{ cid }}">Add metric</button>
                    <button class="btn copy_graph">Copy Graph</button>

                </div>


            </fieldset>
        </form>

    </div>

</div>




<div id="metric_template" class="hidden">

    <span class="metric_name_banner">{{ metric_name }}</span>
    <div class="metric_container well">

        <button class="close metric_close" id="{{cid}}_metric_close">&times;</button>

        <form class="horizontal-form metric-form">
            <fieldset>

                <input type="hidden" name="{{ cid }}_metric_id" id="{{ cid }}_metric_id" class="metric_id"></input>

                {{#errors.metric_name}}
                <div class="control-group error">
                    <span class="help-inline">{{errors.metric_name}}</span>
                </div>
                {{/errors.metric_name}}


                <!-- Creates an input field for each object in metricSelectFields -->

                {{#metric_select_fields}}
                <div class="control-group {{#error}}error{{/error}}">
                    <label class="control-label" for="{{ cid }}_metric_name_{{name}}">{{name}}</label>
                    <div class="controls">
                        <select id="{{ cid }}_metric_name_{{name}}" class="metric_name_input {{disabled}}" {{disabled}}>
                            {{^first}}<option>None</option>{{/first}}
                            {{#options}}
                            <option {{selected}}>{{option}}</option>
                            {{/options}}
                        </select>
                        <span class="help-inline">{{error}}</span>
                    </div>
                </div>
                {{/metric_select_fields}}


                <div class="control-group {{#errors.from}}error{{/errors.from}}">
                    <label class="control-label" for="{{ cid }}_metric_from">From</label>
                    <div class="controls">
                        <input type="text" id="{{ cid }}_metric_from" class="input-xlarge metric_from" value="{{ from_val }}">
                        <select id="{{ cid }}_metric_from_units" class="metric_from_units">
                            <option value="1">Hours</option>
                            <option value="24" {{from_days}}>Days</option>
                        </select>
                        <span class="help-inline">{{errors.from}}</span>
                    </div>
                </div>

                <div class="control-group {{#errors.until}}error{{/errors.until}}">
                    <label class="control-label" for="{{ cid }}_metric_until">Until</label>
                    <div class="controls">
                        <input type="text" id="{{ cid }}_metric_until" class="input-xlarge metric_until" value="{{ until_val }}">
                        <select id="{{ cid }}_metric_until_units" class="metric_until_units">
                            <option value="1">Hours</option>
                            <option value="24" {{until_days}}>Days</option>
                        </select>
                        <span class="help-inline">{{errors.until}}</span>
                    </div>
                </div>

                {{^catchAll}}
                <div class="control-group {{#errors.other_period_offsets}}error{{/errors.other_period_offsets}}">
                    <label class="control-label" for="{{ cid }}_metric_other_period">Other Period</label>
                    <div class="controls" id="{{ cid }}_metric_other_period">
                        <select class="metric_other_period">
                            <option value="none" class="selector_none">None</option>
               {{#has_last}}<option value="last" class="selector_last" {{is_last}}>Last Period</option>{{/has_last}}
                            <option value="this" class="selector_this" {{is_this}}>This Period</option>
                        </select>
                        <span class="metric_other_period_controls">
                            {{#is_this}}
                                <input type="text" class="input-small metric_other_period_days"></input>
                                <span class="help-inline">hours ago</span>
                                <span class="help-inline">{{errors.other_period_offsets}}</span>
                            {{/is_this}}
                            {{#is_addable}}<button class="btn metric_other_period_add">Add</button>{{/is_addable}}
                            {{#clear_items}}<button class="btn metric_other_period_clear">Clear</button>{{/clear_items}}
                        </span>
                        <div class="metric_other_periods">
                            {{#other_period_offset_items}}
                            <div id="{{ val }}_op_item" class="other_period_item clearfix">
                                {{ text }}
                                <button class="close metric_other_period_remove">&times;</button>
                            </div>
                            {{/other_period_offset_items}}
                        </div>
                    </div>
                </div>
                {{/catchAll}}

                <div class="span3">
                    <button class="btn copy_metric">Copy Metric</button>
                </div>


            </fieldset>
        </form>

    </div>

    <hr />

</div>

<?php
$this->load->view('footer');
?>

<script>

    // Dashboard create/edit setup and initialization.
    APP.ORION.Views.setMetricPrefix(<?php if (isset($METRIC_PREFIX)) { echo json_encode($METRIC_PREFIX); } else { echo '""'; } ?>);
    APP.ORION.Views.setMetricConfig(<?php if (isset($METRIC_CONFIG)) { echo json_encode($METRIC_CONFIG); } else { echo '""'; } ?>);
    APP.ORION.Views.setMetricArray(<?php if (isset($metric_array)) { echo json_encode($metric_array); } else { echo '""'; } ?>);
    APP.ORION.Models.setDashboardJson(<?php if (isset($dashboard_json)) { echo json_encode($dashboard_json['payload']); } else { echo '""'; } ?>);

    // Initialize models and views.
    $(function () {
        APP.ORION.Models.setup();
        APP.ORION.Views.setup();
    });

</script>

</body>
</html>
