<?php
include('../include/application/Csw.php');
include('Reporter.php');

// --- MAIN ---
$reporter = new Reporter();
$q = html_entity_decode($_REQUEST['query']); 
$result = $reporter->run($q);
header("Content-type: application/json; charset=utf-8");
echo json_encode($result);
