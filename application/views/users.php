<?php

/**
 * users.php
 * Admins can adjust user permissions
 */

$this->load->view('header');

$user = json_decode($this->session->userdata('user'));

?>

<div class="container" id="user_container"></div>

<!-- Mustache template for app page -->

<div id="app_template" style="display: none;">

    <div class="row">
        <div class="span12">

            <div class="page-header">
                <h1>User management
                    <small>Edit user permissions</small>
                </h1>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="span12">

            <table class="table table-striped" id="user_table">

                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                <tr class="user_iterator_start"></tr>
                    <tr id="user_row_{{id}}">
                        <td>{{email}}</td>
                        <td>
                            <select{{disabled}}>
                                <option {{read_selected}}>Read</option>
                                <option {{read_restricted_selected}}>Read Restricted</option>
                                <option {{create_update_selected}}>Create/Update</option>
                                <option {{delete_selected}}>Admin</option>
                            </select>
                        </td>
                        <td class="status_column">{{{ status }}}</td>
                        <td class="action_column">{{#status}}<button class="btn user_update">Update</button>{{/status}}</td>
                    </tr>
                <tr class="user_iterator_end"></tr>
                </tbody>

            </table>

        </div>
    </div>

</div>

<?php
$this->load->view('footer');
?>

<script>
    APP.USERS.setCurrentUser('<?php echo $user->email; ?>');
    APP.USERS.setUserData(<?php echo json_encode($users); ?>);
</script>

</body>
</html>