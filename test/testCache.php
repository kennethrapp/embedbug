<?php header("Content-Type:text/plain;charset-utf-8");

require_once(realpath("../src/Embedbug/Embedbug.php"));

$EBB = new Embedbug\Embedbug();
$EBB->SetUrls(array("http://arstechnica.com"));
$EBB->SetCachePath(realpath("cache"));

/* get all the open graph tags on arstechnica, cache for 60 seconds */
$result = $EBB->Cache(60)->GetXPaths("http://arstechnica.com", array("meta"=>"//meta[contains(@property, 'og:')]"));

echo print_r($result,true);
