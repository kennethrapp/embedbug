<?php
header("Content-Type:text/plain;charset-utf-8");

require_once(realpath("../src/Embedbug/Embedbug.php"));

$EBB = new Embedbug\Embedbug();
$EBB->SetUrls(array("http://arstechnica.com"));

/* get all the links on arstechnica */

$result = $EBB->GetTags("http://arstechnica.com", array("meta"));

echo print_r($result,true);
