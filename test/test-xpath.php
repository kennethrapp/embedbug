
<?php
header("Content-Type:text/plain;charset-utf-8");

require_once(realpath("../src/Embedbug/Embedbug.php"));

if(array_key_exists('url', $_GET) && filter_var($_GET['url'], FILTER_VALIDATE_URL)){
	$url = $_GET['url'];
}
else{ 
	$url = "http://arstechnica.com/";
}

$Embedbug = new Embedbug\Embedbug($url, array(
	'binarytransfer' => 1,
	'buffersize'     => 2056,
	'ssl verifypeer' => false,
	'header'		 => false
),  "</body>", 1024868);

$Embedbug->Execute();

//echo print_r($Embedbug, true);


$Feed = $Embedbug->ExtractPaths($url, array("//meta[contains(@property,'og:')]"));

echo print_r($Feed,true);
