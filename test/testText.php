<?php
header("Content-Type:text/plain;charset-utf-8");

require_once(realpath("../src/Embedbug/Embedbug.php"));

$EBB = new Embedbug\Embedbug();
$EBB->SetUrls(array("http://arstechnica.com"));

/* get all tags on arstechnical containing the text "nsa" (case insensitive) */

$result = $EBB->GetText("http://arstechnica.com", array("*"), "NSA");

echo print_r($result,true);
