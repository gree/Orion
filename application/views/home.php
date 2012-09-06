<?php

/**
 * home.php
 * Users can select and view dashboards.
 */

$user = json_decode($this->session->userdata('user'));

$this->load->view('header');

?>

<div class="container" id="container">

    <div class="row">
        <div class="span12">

            <div class="page-header">

                <div id="game-nav">Category</div>
                <div class="subnav">
                    <ul class="nav nav-pills">
                        <?php
                        foreach ($navigation as $category => $dashboards) {
                        ?>

                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $category; ?><b class="caret"></b></a>
                            <ul class="dropdown-menu game" id="<?php echo str_replace(" ", "_", $category); ?>">

                                <?php
                                foreach( $dashboards as $dashboard ) {
                                ?>

                                <li>
                                    <a href="#" class="dashboard-retrieve"><?php echo $dashboard->dashboard_name; ?></a>
                                    <?php if ($user && $user->perm_update) { ?>
                                    <a href='<?php echo base_url();?>index.php/orion/create_dashboard/<?php echo urlencode($dashboard->id); ?>' class='edit_link'>[edit]</a>
                                    <?php } ?>
                                </li>

                                <?php
                                }
                                ?>

                                <?php if (!empty($links[$category])) {
                                    echo '<li class="divider"></li>';
                                } ?>

                                <?php
                                foreach( $links[$category] as $link ) {
                                ?>
                                    <li><a href='<?php echo $link->url; ?>'><?php echo $link->display; ?></a></li>
                                <?php
                                }
                                ?>

                            </ul>
                        </li>

                        <?php
                        }
                        ?>

                    </ul>
                </div>

            </div>

        </div>
    </div>

    <div class="row">

        <div class="span7">
            <div class="page-header">
                <h2 id="graph_title">Select a category and dashboard above</h2>
                <div id="server-error" class="alert alert-error"></div>
            </div>
        </div>

        <div class="span5">
            <div id="datepickers">
                <form id="datepicker_form" class="form-inline">
                    <input type="text" id="startDate" class="datepicker input-medium" placeholder="Start">
                    <input type="text" id="endDate" class="datepicker input-medium" placeholder="End">
                    <input class="btn" type="submit" id="submit" value="Submit" >
                </form>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="span12">

            <div id="graphpane" class="panel">
                <div id="graphs" class="clearfix well"></div>
            </div>

        </div>
    </div>

</div>

<?php
$this->load->view('footer');
?>

<script>

    APP.INDEX.Graphs.setPermission(<?php if ($user && $user->perm_create) { echo '1'; } else { echo '0'; } ?>);
    APP.INDEX.UI.setInitialDashboard('<?php if (isset($dashboard_json)) { echo json_encode($dashboard_json); } else { echo ''; } ?>');

    $(function () {
        APP.Setup.init();
        APP.INDEX.UI.init();
    });

</script>

</body>
</html>
