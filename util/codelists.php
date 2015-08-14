<?php

$lang = htmlspecialchars($_REQUEST["language"]);
if(strlen($lang)!=3) $lang = "eng";

header("Content-type: application/xml; charset=utf-8");
readfile("../include/xsl/codelists_".$lang.".xml");