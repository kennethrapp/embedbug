
<?php
header("Content-Type:text/plain;charset-utf-8");

require_once(realpath("../src/Embedbug/Embedbug.php"));

if(array_key_exists('url', $_GET) && filter_var($_GET['url'], FILTER_VALIDATE_URL)){
	$url = $_GET['url'];
}
else{ 
	$url = "http://arstechnica.com/";
}

$tag=null;

if(array_key_exists('tag', $_GET)){
	if(is_array($_GET['tag'])){ 
		$tag = array_filter($_GET['tag']);
	}
	else $tag=(array)$_GET['tag'];
}
else{
	$tag=array("meta");
}

$Embedbug = new Embedbug\Embedbug($url, array(
	'binarytransfer' => 1,
	'buffersize'     => 2056,
	'ssl verifypeer' => false,
	'header'		 => false
),  "</body>", 1024868);

$Embedbug->Execute();

//echo print_r($Embedbug, true);



$Feed = $Embedbug->ExtractTags($url, $tag, true);

echo print_r($Feed,true);
