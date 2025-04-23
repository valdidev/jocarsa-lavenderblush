<?php
require_once 'db.php';
require_once 'helpers.php';
require_once 'actions.php';
require_once 'ajax.php';

if (!logged_in_user_id()) {
    require 'templates/login.php';
} else {
    require 'templates/main.php';
}