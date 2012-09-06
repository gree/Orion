<?php

$this->load->view('header');

$previous_url = base_url() . "index.php/" . $original_page;
?>

<div class="hero-unit">
    <h1>Page not found.</h1>
    <p>"Something went wrong..."</p>
</div>

<div class="row">
    <div class="span12">
        <h1 style="text-align: center">
            <a href='<?php echo $previous_url; ?>'>
                <?php echo $message; ?>
            </a>
        </h1>
    </div>
</div>

<?php

$this->load->view('footer');

?>