<?php

require_once(APPPATH . 'models/baseentity.php');

class User extends BaseEntity {

    public static $_db_fields = array(
        "id"                => array("int", "none", false),
        "email"             => array("string", "none", false),
        "perm_create"       => array("int", "none", false),
        "perm_read"         => array("int", "none", false),
        "perm_update"       => array("int", "none", false),
        "perm_delete"       => array("int", "none", false),
        "perm_restricted"   => array("int", "none", false)
    );

    public $id;
    public $email;
    public $perm_create;
    public $perm_read;
    public $perm_update;
    public $perm_delete;
    public $perm_restricted;
}