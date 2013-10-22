<?php

require_once(realpath("src/Embedbug/Embedbug.php"));

$filter = array('url'=>array('filter'=>FILTER_VALIDATE_URL));

$url = filter_input_array(INPUT_POST, $filter);

if(!$url){
	$url = array('url'=>"https://news.ycombinator.com/news");
}
$url_parsed = parse_url($url['url']);

$EmbedBug = new EmbedBug\EmbedBug(
	
	$url['url'],
	
	array(
        'binarytransfer' => 1,
        'buffersize'     => 2056,
        'ssl verifypeer' => false,
        'header'		 => false
	), '</html>', 1024768
);

$EmbedBug->execute();

$Links = $EmbedBug->ExtractTags($url['url'], array('a'));

/* collect the hrefs for out bound links and pass back to EmbedBug. */

$OutboundLinks = array();



if(count($Links) && array_key_exists('a', $Links)){ 

	foreach($Links['a'] as $link){

		if(array_key_exists('href', $link)){ 

			$href = $link['href'];
				
			// make sure the links are prefaced with http and validate as urls.
			if((stripos($href, 'http') === 0) && (filter_var($href, FILTER_VALIDATE_URL))){
				
				$url = parse_url($href);
				
				// ignore inbound links. reverse the strings to also capture subdomains
				if(stripos(strrev($url_parsed['host']), strrev($url['host'])) === FALSE ){ 
					$OutboundLinks[] = $href;
				}
			}
		}
	}

	// this should be a Refeed method 

	$EmbedBug = new EmbedBug\EmbedBug(
		
		$OutboundLinks,
		
		array(
	        'binarytransfer' => 1,
	        'buffersize'     => 2056,
	        'ssl verifypeer' => false,
	        'header'		 => false
		), '</html>', 1024768
	);

	$EmbedBug->execute();

    $Feed = $EmbedBug->ExtractFeed(array(
    	"type" => "website"
    ));

    if(count($Feed)){
    	
    	header("Content-Type:application/json;charset=utf-8");
		die(json_encode($Feed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ));
    }
}
	