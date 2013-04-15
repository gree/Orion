<!doctype html>
<!--[if lt IE 7]> <html class="orion no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="orion no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="orion no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="orion no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title><?php echo $APPLICATION_TITLE; ?></title>
    <meta name="description" content="">
    <meta name="author" content="">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="<?php echo base_url();?>css/bootstrap.css">
    <style>
        body {
            padding-top: 60px;
            padding-bottom: 40px;
        }
    </style>
    <link rel="stylesheet" href="<?php echo base_url();?>css/bootstrap-responsive.css?1">
    <link rel="stylesheet" href="<?php echo base_url();?>css/style.css">

    <link rel="stylesheet" href="<?php echo base_url();?>css/ui-darkness/jquery-ui-1.8.21.custom.css" />
    <link rel="stylesheet" href="<?php echo base_url();?>css/chosen.css" />

    <link rel="shortcut icon" href="<?php echo base_url();?>img/favicon.ico" />

</head>

<body>

<?php $user = json_decode($this->session->userdata('user')); ?>

<!-- Navigation header -->
<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">

            <!-- Main nav bar elements -->
            <div id="nav-navbar-orion">

                <a class="brand" href="<?php echo base_url();?>index.php"><?php echo $APPLICATION_TITLE; ?></a>

                <?php if ($user && $user->perm_create) { ?>
                <ul class="nav">
                    <li><a href="<?php echo base_url();?>index.php/orion/create_dashboard">Create Dashboard</a></li>
                    <?php if ($user && $user->perm_delete) { ?>
                    <li><a href="<?php echo base_url();?>index.php/links">Link Management</a></li>
                    <li><a href="<?php echo base_url();?>index.php/orion/users">User Management</a></li>
                    <?php } ?>
                </ul>
                <?php } ?>

                <!-- Application logo -->
                <div id="nav-logo-orion"></div>

                <ul class="nav pull-right">
                    <?php if ($user) { ?>
                    <li class="login_info">Logged in<?php 
                        if ( $this->session->userdata('name') ){
                            echo " as " . $this->session->userdata('name');
                        }else if ( isset($user->email) && $user->email != '' ){
                            echo " as " . $user->email;
                        }
                    ?></li>
                    <li><a href="<?php echo base_url();?>index.php/authenticate/logout">Log Out</a></li>
                    <?php } else { ?>
                    <li><a href="<?php echo base_url();?>index.php/authenticate?location=<?php echo $location; ?>">Log In</a></li>
                    <?php } ?>
                </ul>

            </div>

        </div>
    </div>
</div>
