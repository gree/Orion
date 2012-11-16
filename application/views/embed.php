<?php

/**
 * embed.php
 * Embed orion graphs in any external page
 */

$user = json_decode($this->session->userdata('user'));


?>

<div class="container" id="container">


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