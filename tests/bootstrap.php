<?php

require_once __DIR__.'/../vendor/autoload.php';

// Disable session cookies for CSRF tests
ini_set("session.use_cookies",0);
ini_set("session.use_only_cookies",0);
ini_set("session.use_trans_sid",1);
@session_start();

