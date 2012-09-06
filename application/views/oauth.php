<?php

/**
 * home.php
 * Users can select and view dashboards.
 */


$this->load->view('header');

?>
<?php if(isset($personMarkup)): ?>
    <?php print $personMarkup ?>
    <?php endif ?>
<?php
if(isset($authUrl)) {
    print "<a class='login' href='$authUrl'>Connect Me!</a>";
} else {
    print "<a class='logout' href='?logout'>Logout</a>";
}
?>

<?php
$this->load->view('footer');
?>

</body>
</html>