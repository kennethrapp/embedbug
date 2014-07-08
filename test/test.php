<xmp><?php

require_once(realpath("../src/Embedbug/Embedbug.php"));

$url = "http://arstechnica.com/";


$Embedbug = new Embedbug\Embedbug($url, array(
	'binarytransfer' => 1,
	'buffersize'     => 2056,
	'ssl verifypeer' => false,
	'header'		 => false
),  "</body>", 1024868);

$Embedbug->Execute();

echo print_r($Embedbug, true);
