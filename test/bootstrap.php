<?php
define('TEST_ENTERPRISE_ID', 'TestEnterprise');
$_SESSION['enterprise_id'] = TEST_ENTERPRISE_ID;

Clown\Config::init(__DIR__ . '/configs/test/clown.php');

$classLoader = new ClassLoader();
$classLoader->addPrefix('Models', __DIR__);
$classLoader->register();