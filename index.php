<?php

// absolute filesystem path to this web root
define('MICKA_DIR', dirname(__FILE__));

// maintenance
if(is_dir(MICKA_DIR . "/stop")) {
	require 'include/templates/maintenance.php';
}

require MICKA_DIR . '/include/micka.php';
