<?php
/**
 * version 20121221
 * 
 */


dibi::disconnect();

$protokol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on' ? "https" : "http");
$serverPort = $_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT'];

if (MICKA_URL != '') {
	$url = isset($redirectUrl) ? $redirectUrl : '';
	header("Location: $protokol://" . MICKA_URL . $url, true, 303);
} else {
	$url = isset($redirectUrl) && $redirectUrl != ''
			? $_SERVER['SERVER_NAME'] . $serverPort . $redirectUrl
			: $_SERVER['SERVER_NAME'] . $serverPort . substr(htmlspecialchars($_SERVER['PHP_SELF']), 0, strrpos($_SERVER['PHP_SELF'], '/'));
	header("Location: $protokol://$url", true, 303);
}
exit;

