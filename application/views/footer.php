<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="<?php echo base_url();?>js/libs/jquery-1.7.2.min.js"><\/script>')</script>

<script src="<?php echo base_url();?>js/libs/jquery-ui-1.8.21.custom.min.js"></script>
<script src="<?php echo base_url();?>js/libs/underscore-min.js"></script>

<?php if (strpos($location, 'index') !== false || strpos($location, 'view_metric') !== false) { ?>

<script src="<?php echo base_url();?>js/libs/highcharts/highstock.js"></script>
<script src="<?php echo base_url();?>js/libs/jquery-ui-timepicker-addon.js"></script>
<script src="<?php echo base_url();?>js/libs/bootstrap/dropdown.js"></script>

<script src="<?php echo base_url();?>js/index.js"></script>

<?php } ?>

<?php if (strpos($location, 'create_dashboard') !== false) { ?>

<script src="<?php echo base_url();?>js/libs/bootstrap/modal.js"></script>
<script src="<?php echo base_url();?>js/libs/bootstrap/transition.js"></script>
<script src="<?php echo base_url();?>js/libs/backbone-min.js"></script>
<script src="<?php echo base_url();?>js/libs/mustache.js"></script>
<script src="<?php echo base_url();?>js/libs/chosen.jquery.min.js"></script>

<script src="<?php echo base_url();?>js/orion.js"></script>

<?php } ?>

<?php if (strpos($location, 'users') !== false) { ?>

<script src="<?php echo base_url();?>js/libs/backbone-min.js"></script>
<script src="<?php echo base_url();?>js/libs/mustache.js"></script>

<script src="<?php echo base_url();?>js/users.js"></script>

<?php } ?>

<?php if (strpos($location, 'links') !== false) { ?>

<script src="<?php echo base_url();?>js/libs/backbone-min.js"></script>
<script src="<?php echo base_url();?>js/libs/mustache.js"></script>

<script src="<?php echo base_url();?>js/links.js"></script>

<?php } ?>

<script src="<?php echo base_url();?>js/plugins.js"></script>
<script src="<?php echo base_url();?>js/script.js"></script>
<script>
    APP.Setup.set_base_path('<?php echo base_url();?>');
</script>

<div id="is_mobile"></div>

