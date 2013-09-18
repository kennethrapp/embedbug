<?php
/* redo this only taking the following params

?url
?tags

*/

require_once(realpath("../src/Embedbug/Embedbug.php"));

$url = "http://arstechnica.com/tech-policy/2013/09/secret-court-declassifies-opinion-providing-rationale-for-metadata-sharing/";

$EmbedBug = new EmbedBug\Embedbug(
	
	$url,
	
	array(
        'binarytransfer' => 1,
        'buffersize'     => 2056,
        'ssl verifypeer' => false,
        'header'		 => false
	), '</head>', 1024768
);

$EmbedBug->execute();

header("Content-Type:text/plain;charset=utf-8");

?>
=======================================================================================================================
Result of a call to Arstechnica, ending at (or near) either the </head> tag or the first Mb.
=======================================================================================================================
<?php 
	echo print_r($EmbedBug,true);
?>
=======================================================================================================================
get curl_info for arstechnica
=======================================================================================================================
<?php
	echo print_r($EmbedBug->GetInfo($url), true); 
?>
=======================================================================================================================
extract <meta> tags
=======================================================================================================================
<?php
	echo print_r($EmbedBug->ExtractTags($url, array('meta')), true);