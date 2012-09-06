<?php

/**
 * manage_links.php
 */

$this->load->view('header');

?>

<div class="container" id="links_container"></div>

<!-- Mustache template for link management -->
<div id="app_template" style="display: none;">

    <div class="row">
        <div class="span12">

            <div class="page-header">
                <h1>Link management
                    <small>Set the links displayed for each category</small>
                </h1>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="span12">
            <div id="error-box" class="alert alert-error" style="display: none;"></div>
        </div>
    </div>

    <div class="row links_controls">

        <div class="span2 offset1">
            <button class="btn btn-info" id="add_link">Add Link</button>
        </div>

        <div class="span7" id="links_form">

        </div>

        <div class="span2">
            <span id="saving-box" class="alert alert-info" style="display: none;">Saving...</span>
            <span id="success-box" class="alert alert-success" style="display: none;"></span>
        </div>

    </div>

    <div class="row">
        <div class="span12" id="links_list">

            <ul id="links">
                <table class="table table-condensed">
                    <thead>
                        <th>Category</th>
                        <th>Display Name</th>
                        <th>Link URL</th>
                        <th></th>
                    </thead>
                    <tbody>
                        <tr class="link_iterator_start"></tr>
                        <tr id="link_{{id}}">
                            <td>{{category_name}}</td>
                            <td>{{display}}</td>
                            <td>{{url}}</td>
                            <td>
                                <span class="link_buttons">
                                    <button class="btn edit_link" id="edit_link_{{id}}">Edit</button>
                                    <button class="btn delete_link" id="delete_link_{{id}}">Delete</button>
                                </span>
                            </td>
                        </tr>
                        <tr class="link_iterator_end"></tr>
                    </tbody>
                </table>
            </ul>

        </div>
    </div>

</div>

<!-- Mustache template for link create/edit form -->
<div id="link_template" style="display: none;">

    <div>

        <form class="form-inline links_form">

            <span class="control-group {{#errors}}error{{/errors}}">
                <input type="text" id="display" class="input-medium" value="{{ display }}" placeholder="Display name">
                <input type="text" id="url" class="input-medium" value="{{ url }}" placeholder="Link URL">
            </span>
            <select id="category_id">
                <?php
                foreach( $categories as $category ) {
                    ?>
                    <option value="<?php echo $category->id; ?>"><?php echo $category->category_name; ?></option>
                    <?php
                }
                ?>
            </select>
            <button type="submit" class="btn">{{buttonText}} link</button>

        </form>

    </div>

</div>



<?php
$this->load->view('footer');
?>

<script>
    APP.LINKS.setCategories('<?php if (isset($categories)) { echo json_encode($categories); } else { echo ''; } ?>');
    APP.LINKS.setLinks('<?php if (isset($links)) { echo json_encode($links); } else { echo ''; } ?>');
</script>

</body>
</html>
