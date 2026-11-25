<?php
require 'includes/functions.php';
$_SESSION = [];
session_destroy();
session_start();
flash('You have been logged out.', 'info');
redirect('login.php');
